<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_no',
        'client_id',
        'title',
        'items',
        'subtotal',
        'tax',
        'discount',
        'total',
        'status',
        'due_date',
        'notes',
        'created_by',
        'bank_accounts',

        // Payment Settings
        'card_payment_active',
        'bank_account_allowed',
        'invoice_attachment',
        'stripe_payment_url',
    ];

    protected $casts = [
        'items' => 'array',
        'due_date' => 'date',
'bank_accounts' => 'array',
        'card_payment_active' => 'boolean',
        'bank_account_allowed' => 'boolean',
    ];

    protected $appends = [
        'invoice_attachment_url',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function getInvoiceAttachmentUrlAttribute()
    {
        return $this->invoice_attachment
            ? asset('storage/' . $this->invoice_attachment)
            : null;
    }
}
