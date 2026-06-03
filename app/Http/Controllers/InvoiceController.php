<?php

namespace App\Http\Controllers;

use App\Mail\InvoiceCreatedMail;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Stripe\Stripe;
use Stripe\Checkout\Session;

class InvoiceController extends Controller
{
    public function index()
    {
        return Invoice::with('client')->latest()->get();
    }

    public function store(Request $request)
    {
        if (is_string($request->items)) {
            $request->merge(['items' => json_decode($request->items, true)]);
        }

        if (is_string($request->bank_accounts)) {
            $request->merge(['bank_accounts' => json_decode($request->bank_accounts, true)]);
        }

        $data = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'title' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'card_payment_active' => 'nullable|boolean',
            'bank_account_allowed' => 'nullable|boolean',
            'bank_accounts' => 'nullable|array',
            'invoice_attachment' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx|max:5120',
        ]);

        $subtotal = $this->calculateSubtotal($data['items']);
        $tax = $data['tax'] ?? 0;
        $discount = $data['discount'] ?? 0;
        $total = ($subtotal + $tax) - $discount;
        $invoiceNo = $this->generateInvoiceNo();

        $attachment = null;
        if ($request->hasFile('invoice_attachment')) {
            $attachment = $request->file('invoice_attachment')->store('invoice-attachments', 'public');
        }

        $cardActive = $request->boolean('card_payment_active');
        $bankAllowed = $request->boolean('bank_account_allowed');

        $stripePaymentUrl = $cardActive
            ? $this->createStripeCheckoutUrl($total, $invoiceNo)
            : null;

        $invoice = Invoice::create([
            'invoice_no' => $invoiceNo,
            'client_id' => $data['client_id'],
            'title' => $data['title'] ?? null,
            'items' => $data['items'],
            'subtotal' => $subtotal,
            'tax' => $tax,
            'discount' => $discount,
            'total' => $total,
            'status' => 'unpaid',
            'due_date' => $data['due_date'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => $request->user()->id,
            'card_payment_active' => $cardActive,
            'bank_account_allowed' => $bankAllowed,
            'bank_accounts' => $data['bank_accounts'] ?? [],
            'invoice_attachment' => $attachment,
            'stripe_payment_url' => $stripePaymentUrl,
        ]);

        $invoice->load('client');

        if ($invoice->client && $invoice->client->email) {
            Mail::to($invoice->client->email)->send(new InvoiceCreatedMail($invoice));
        }

        return response()->json($invoice, 201);
    }

    public function show(Invoice $invoice)
    {
        return $invoice->load('client');
    }

    public function update(Request $request, Invoice $invoice)
    {
        if (is_string($request->items)) {
            $request->merge(['items' => json_decode($request->items, true)]);
        }

        if (is_string($request->bank_accounts)) {
            $request->merge(['bank_accounts' => json_decode($request->bank_accounts, true)]);
        }

        $data = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'title' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'status' => 'nullable|string',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'card_payment_active' => 'nullable|boolean',
            'bank_account_allowed' => 'nullable|boolean',
            'bank_accounts' => 'nullable|array',
            'invoice_attachment' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx|max:5120',
        ]);

        $subtotal = $this->calculateSubtotal($data['items']);
        $tax = $data['tax'] ?? 0;
        $discount = $data['discount'] ?? 0;
        $total = ($subtotal + $tax) - $discount;

        $attachment = $invoice->invoice_attachment;
        if ($request->hasFile('invoice_attachment')) {
            $attachment = $request->file('invoice_attachment')->store('invoice-attachments', 'public');
        }

        $cardActive = $request->boolean('card_payment_active');
        $bankAllowed = $request->boolean('bank_account_allowed');

        $stripePaymentUrl = $invoice->stripe_payment_url;

        if ($cardActive && !$stripePaymentUrl) {
            $stripePaymentUrl = $this->createStripeCheckoutUrl($total, $invoice->invoice_no);
        }

        if (!$cardActive) {
            $stripePaymentUrl = null;
        }

        $invoice->update([
            'client_id' => $data['client_id'],
            'title' => $data['title'] ?? null,
            'items' => $data['items'],
            'subtotal' => $subtotal,
            'tax' => $tax,
            'discount' => $discount,
            'total' => $total,
            'status' => $data['status'] ?? $invoice->status,
            'due_date' => $data['due_date'] ?? null,
            'notes' => $data['notes'] ?? null,
            'card_payment_active' => $cardActive,
            'bank_account_allowed' => $bankAllowed,
            'bank_accounts' => $data['bank_accounts'] ?? [],
            'invoice_attachment' => $attachment,
            'stripe_payment_url' => $stripePaymentUrl,
        ]);

        return response()->json($invoice->load('client'));
    }

    public function destroy(Invoice $invoice)
    {
        $invoice->delete();

        return response()->json([
            'message' => 'Invoice deleted successfully',
        ]);
    }

    private function calculateSubtotal(array $items): float
    {
        $subtotal = 0;

        foreach ($items as $item) {
            $subtotal += ($item['quantity'] ?? 0) * ($item['price'] ?? 0);
        }

        return $subtotal;
    }

    private function generateInvoiceNo(): string
    {
        $nextId = Invoice::max('id') + 1;
        return 'INV-' . date('Y') . '-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
    }

    private function createStripeCheckoutUrl($amount, $invoiceNo)
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $session = Session::create([
            'payment_method_types' => ['card'],
            'mode' => 'payment',
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => 'Invoice ' . $invoiceNo,
                    ],
                    'unit_amount' => max(100, intval($amount * 100)),
                ],
                'quantity' => 1,
            ]],
            'success_url' => url('/invoices?payment=success'),
            'cancel_url' => url('/invoices?payment=cancel'),
        ]);

        return $session->url;
    }
}
