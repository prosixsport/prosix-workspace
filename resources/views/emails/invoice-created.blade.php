<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->invoice_no }}</title>
</head>

<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,sans-serif;color:#111;">
    <div style="max-width:780px;margin:0 auto;padding:30px 14px;">

        <div style="background:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.08);">

            <!-- PREMIUM HEADER -->
            <div style="background:linear-gradient(135deg,#111827,#1f2937);padding:32px;color:#ffffff;">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td align="left" valign="top">
                            <div style="width:62px;height:62px;background:#ffffff;color:#111827;border-radius:16px;text-align:center;line-height:62px;font-size:28px;font-weight:900;">
                                P
                            </div>

                            <h1 style="margin:22px 0 0;font-size:42px;font-family:Georgia,serif;font-weight:500;">
                                Invoice
                            </h1>

                            <p style="margin:8px 0 0;color:#d1d5db;font-size:14px;">
                                Prosixflow Professional Billing
                            </p>
                        </td>

                        <td align="right" valign="top" style="font-size:13px;color:#d1d5db;">
                            <p style="margin:0 0 8px;">
                                <strong style="color:#fff;">Invoice No.</strong><br>
                                {{ $invoice->invoice_no }}
                            </p>

                            <p style="margin:0 0 8px;">
                                <strong style="color:#fff;">Date</strong><br>
                                {{ $invoice->created_at ? $invoice->created_at->format('d M Y') : '-' }}
                            </p>

                            <p style="margin:0;">
                                <strong style="color:#fff;">Status</strong><br>
                                {{ ucfirst($invoice->status) }}
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

            <div style="padding:42px;">

                <!-- BILLING INFO -->
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td align="left" valign="top" style="font-size:13px;">
                            <div style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:14px;padding:16px;">
                                <strong style="font-size:14px;">Billed To</strong>
                                <p style="margin:8px 0 4px;font-weight:bold;">{{ $invoice->client->name ?? '-' }}</p>
                                <p style="margin:4px 0;color:#374151;">{{ $invoice->client->email ?? '-' }}</p>
                                <p style="margin:4px 0;color:#374151;">{{ $invoice->client->phone ?? '-' }}</p>
                                <p style="margin:4px 0;color:#374151;">{{ $invoice->client->address ?? '-' }}</p>
                            </div>
                        </td>

                        <td width="25"></td>

                        <td align="left" valign="top" style="font-size:13px;">
                            <div style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:14px;padding:16px;">
                                <strong style="font-size:14px;">Invoice Details</strong>
                                <p style="margin:8px 0 4px;"><strong>Invoice:</strong> {{ $invoice->invoice_no }}</p>
                                <p style="margin:4px 0;"><strong>Due Date:</strong> {{ $invoice->due_date ? $invoice->due_date->format('d M Y') : '-' }}</p>
                                <p style="margin:4px 0;"><strong>Status:</strong> {{ ucfirst($invoice->status) }}</p>
                            </div>
                        </td>
                    </tr>
                </table>

                <!-- ITEMS -->
                <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:34px;border-collapse:collapse;">
                    <thead>
                        <tr>
                            <th align="left" style="padding:14px 0;border-bottom:2px solid #111;font-size:13px;">Item</th>
                            <th align="center" style="padding:14px 0;border-bottom:2px solid #111;font-size:13px;">Qty</th>
                            <th align="right" style="padding:14px 0;border-bottom:2px solid #111;font-size:13px;">Unit Price</th>
                            <th align="right" style="padding:14px 0;border-bottom:2px solid #111;font-size:13px;">Total</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($invoice->items ?? [] as $item)
                            <tr>
                                <td style="padding:15px 0;border-bottom:1px solid #e5e7eb;font-size:13px;">
                                    {{ $item['description'] ?? '-' }}
                                </td>

                                <td align="center" style="padding:15px 0;border-bottom:1px solid #e5e7eb;font-size:13px;">
                                    {{ $item['quantity'] ?? 0 }}
                                </td>

                                <td align="right" style="padding:15px 0;border-bottom:1px solid #e5e7eb;font-size:13px;">
                                    Rs {{ number_format($item['price'] ?? 0, 2) }}
                                </td>

                                <td align="right" style="padding:15px 0;border-bottom:1px solid #e5e7eb;font-size:13px;font-weight:bold;">
                                    Rs {{ number_format(($item['quantity'] ?? 0) * ($item['price'] ?? 0), 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <!-- TOTALS -->
                <table width="290" align="right" cellpadding="0" cellspacing="0" style="margin-top:24px;font-size:13px;">
                    <tr>
                        <td style="padding:7px 0;color:#4b5563;">Subtotal</td>
                        <td align="right" style="padding:7px 0;">
                            <strong>Rs {{ number_format($invoice->subtotal, 2) }}</strong>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:7px 0;color:#4b5563;">Tax</td>
                        <td align="right" style="padding:7px 0;">
                            <strong>Rs {{ number_format($invoice->tax, 2) }}</strong>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:7px 0;color:#4b5563;">Discount</td>
                        <td align="right" style="padding:7px 0;">
                            <strong>Rs {{ number_format($invoice->discount, 2) }}</strong>
                        </td>
                    </tr>

                    <tr>
                        <td style="background:#111827;color:#fff;padding:16px 18px;font-size:20px;font-weight:900;border-radius:10px 0 0 10px;">
                            Total
                        </td>

                        <td align="right" style="background:#111827;color:#fff;padding:16px 18px;font-size:20px;font-weight:900;border-radius:0 10px 10px 0;">
                            Rs {{ number_format($invoice->total, 2) }}
                        </td>
                    </tr>
                </table>

                <div style="clear:both;"></div>

                <!-- PAYMENT STATUS BOX -->
                <div style="margin-top:34px;padding:18px;background:#f8fafc;border-left:5px solid #111827;border-radius:12px;font-size:13px;">
                    <strong>Payment Status:</strong>
                    {{ strtoupper($invoice->status) }}
                </div>

                <!-- CARD PAYMENT BUTTON -->
                @if($invoice->card_payment_active && $invoice->stripe_payment_url)
                    <div style="margin-top:26px;text-align:center;">
                        <a href="{{ $invoice->stripe_payment_url }}"
                           target="_blank"
                           style="background:#111827;color:#ffffff;padding:16px 34px;text-decoration:none;border-radius:12px;font-weight:900;font-size:15px;display:inline-block;">
                            Pay Securely by Card
                        </a>
                    </div>
                @endif

                <!-- PAYMENT METHODS -->
                @if($invoice->card_payment_active || $invoice->bank_account_allowed || $invoice->invoice_attachment_url)
                    <div style="margin-top:30px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:16px;padding:20px;font-size:13px;">
                        <h3 style="margin:0 0 14px;font-size:18px;">
                            Payment Methods
                        </h3>

                        @if($invoice->card_payment_active)
                            <div style="background:#ecfdf5;border:1px solid #bbf7d0;padding:14px;border-radius:12px;margin-bottom:12px;">
                                <strong>Card Payment Allowed</strong>
                                <p style="margin:6px 0 0;color:#166534;">
                                    You can pay this invoice securely using card payment.
                                </p>
                            </div>
                        @endif

                        @if($invoice->bank_account_allowed)
                            <div style="background:#eff6ff;border:1px solid #bfdbfe;padding:14px;border-radius:12px;margin-bottom:12px;">
                                <strong>Bank Transfer Allowed</strong>
                                <p style="margin:6px 0 0;color:#1d4ed8;">
                                    You can pay this invoice through bank transfer using the allowed bank details below.
                                </p>
                            </div>

                            @if(!empty($invoice->bank_accounts))
                                @foreach($invoice->bank_accounts as $bank)
                                    <div style="background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;padding:14px;margin-top:10px;">
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td width="46" valign="top">
                                                    @if(!empty($bank['logo']))
                                                        <img src="{{ $bank['logo'] }}"
                                                             style="width:38px;height:38px;border-radius:10px;object-fit:contain;border:1px solid #e5e7eb;padding:4px;background:#f9fafb;">
                                                    @else
                                                        <div style="width:38px;height:38px;border-radius:10px;background:#111827;color:#fff;text-align:center;line-height:38px;font-weight:bold;">
                                                            {{ strtoupper(substr($bank['bank_name'] ?? 'B', 0, 1)) }}
                                                        </div>
                                                    @endif
                                                </td>

                                                <td valign="top" style="font-size:13px;">
                                                    <strong>{{ $bank['bank_name'] ?? 'Bank' }}</strong>
                                                    <p style="margin:5px 0;">Account Title: {{ $bank['account_title'] ?? '-' }}</p>
                                                    <p style="margin:5px 0;">Account Number: {{ $bank['account_number'] ?? '-' }}</p>
                                                    <p style="margin:5px 0;">IBAN: {{ $bank['iban'] ?? '-' }}</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                @endforeach
                            @endif
                        @endif

                        @if($invoice->invoice_attachment_url)
                            <div style="background:#ffffff;border:1px solid #e5e7eb;padding:14px;border-radius:12px;margin-top:12px;">
                                <strong>Attachment Available</strong>
                                <p style="margin:8px 0 0;">
                                    <a href="{{ $invoice->invoice_attachment_url }}" target="_blank" style="color:#2563eb;font-weight:bold;">
                                        View / Download File
                                    </a>
                                </p>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- NOTES -->
                @if($invoice->notes)
                    <div style="margin-top:24px;padding:16px;background:#fffbeb;border:1px solid #fde68a;border-radius:12px;font-size:13px;">
                        <strong>Notes:</strong>
                        <p style="margin:8px 0 0;">{{ $invoice->notes }}</p>
                    </div>
                @endif

                <!-- THANK YOU -->
                <div style="margin-top:54px;font-family:cursive;font-size:42px;color:#111827;transform:rotate(-5deg);">
                    Thank You!
                </div>

                <!-- FOOTER -->
                <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:26px;border-top:1px solid #e5e7eb;padding-top:18px;">
                    <tr>
                        <td align="left" valign="top" style="font-size:12px;color:#6b7280;">
                            <strong style="color:#111827;">Prosixflow</strong><br>
                            Work Management & Billing
                        </td>

                        <td align="right" valign="top" style="font-family:Georgia,serif;font-size:18px;">
                            <strong>Prosixflow</strong>
                            <p style="margin:5px 0;font-size:12px;font-family:Arial,sans-serif;color:#6b7280;">
                                Professional Invoice
                            </p>
                        </td>
                    </tr>
                </table>

            </div>
        </div>
    </div>
</body>
</html>
