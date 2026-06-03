<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->invoice_no }}</title>
</head>

<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,sans-serif;color:#111;">
    <div style="max-width:760px;margin:0 auto;padding:30px 14px;">
        <div style="background:#fff;padding:54px;border-radius:4px;">

            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td align="left" valign="top">
                        <div style="width:52px;height:52px;background:#111;color:#fff;border-radius:15px;text-align:center;line-height:52px;font-size:24px;font-weight:900;">
                            P
                        </div>
                    </td>

                    <td align="right" valign="top">
                        <h1 style="margin:0 0 22px;font-size:44px;font-family:Georgia,serif;font-weight:500;">
                            Invoice
                        </h1>
                        <p style="margin:3px 0;color:#555;font-size:13px;">
                            Invoice No. {{ $invoice->invoice_no }}
                        </p>
                        <p style="margin:3px 0;color:#555;font-size:13px;">
                            {{ $invoice->created_at ? $invoice->created_at->format('d M Y') : '' }}
                        </p>
                    </td>
                </tr>
            </table>

            <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:54px;">
                <tr>
                    <td align="left" valign="top" style="font-size:13px;">
                        <strong>Billed to:</strong>
                        <p style="margin:5px 0;">{{ $invoice->client->name ?? '-' }}</p>
                        <p style="margin:5px 0;">{{ $invoice->client->email ?? '-' }}</p>
                        <p style="margin:5px 0;">{{ $invoice->client->phone ?? '-' }}</p>
                        <p style="margin:5px 0;">{{ $invoice->client->address ?? '-' }}</p>
                    </td>

                    <td align="right" valign="top" style="font-size:13px;">
                        <p style="margin:5px 0;"><strong>Invoice No.</strong> {{ $invoice->invoice_no }}</p>
                        <p style="margin:5px 0;">
                            <strong>Due Date:</strong>
                            {{ $invoice->due_date ? $invoice->due_date->format('d M Y') : '-' }}
                        </p>
                        <p style="margin:5px 0;"><strong>Status:</strong> {{ ucfirst($invoice->status) }}</p>
                    </td>
                </tr>
            </table>

            <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:34px;border-collapse:collapse;">
                <thead>
                    <tr>
                        <th align="left" style="padding:12px 0;border-bottom:2px solid #111;font-size:13px;">Item</th>
                        <th align="center" style="padding:12px 0;border-bottom:2px solid #111;font-size:13px;">Quantity</th>
                        <th align="right" style="padding:12px 0;border-bottom:2px solid #111;font-size:13px;">Unit Price</th>
                        <th align="right" style="padding:12px 0;border-bottom:2px solid #111;font-size:13px;">Total</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($invoice->items ?? [] as $item)
                        <tr>
                            <td style="padding:14px 0;border-bottom:1px solid #999;font-size:13px;">
                                {{ $item['description'] ?? '-' }}
                            </td>
                            <td align="center" style="padding:14px 0;border-bottom:1px solid #999;font-size:13px;">
                                {{ $item['quantity'] ?? 0 }}
                            </td>
                            <td align="right" style="padding:14px 0;border-bottom:1px solid #999;font-size:13px;">
                                Rs {{ number_format($item['price'] ?? 0, 2) }}
                            </td>
                            <td align="right" style="padding:14px 0;border-bottom:1px solid #999;font-size:13px;">
                                Rs {{ number_format(($item['quantity'] ?? 0) * ($item['price'] ?? 0), 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <table width="270" align="right" cellpadding="0" cellspacing="0" style="margin-top:22px;font-size:13px;">
                <tr>
                    <td style="padding:6px 0;">Subtotal</td>
                    <td align="right" style="padding:6px 0;"><strong>Rs {{ number_format($invoice->subtotal, 2) }}</strong></td>
                </tr>
                <tr>
                    <td style="padding:6px 0;">Tax</td>
                    <td align="right" style="padding:6px 0;"><strong>Rs {{ number_format($invoice->tax, 2) }}</strong></td>
                </tr>
                <tr>
                    <td style="padding:6px 0;">Discount</td>
                    <td align="right" style="padding:6px 0;"><strong>Rs {{ number_format($invoice->discount, 2) }}</strong></td>
                </tr>
                <tr>
                    <td style="background:#111;color:#fff;padding:14px 18px;font-size:20px;font-weight:900;">Total</td>
                    <td align="right" style="background:#111;color:#fff;padding:14px 18px;font-size:20px;font-weight:900;">
                        Rs {{ number_format($invoice->total, 2) }}
                    </td>
                </tr>
            </table>

            <div style="clear:both;"></div>

            @if($invoice->card_payment_active && $invoice->stripe_payment_url)
                <div style="margin-top:30px;text-align:center;">
                    <a href="{{ $invoice->stripe_payment_url }}"
                       target="_blank"
                       style="background:#111;color:#fff;padding:14px 26px;text-decoration:none;border-radius:8px;font-weight:bold;display:inline-block;">
                        Pay by Card
                    </a>
                </div>
            @endif

            @if($invoice->bank_account_allowed || $invoice->invoice_attachment_url)
                <div style="margin-top:24px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:12px;padding:14px;font-size:13px;">
                    <strong>Payment & Attachment</strong>

                    @if($invoice->bank_account_allowed)
                        <p style="margin:8px 0;">Bank Payment: Allowed</p>
                    @endif

                    @if($invoice->invoice_attachment_url)
                        <p style="margin:8px 0;">
                            <strong>Attachment:</strong>
                            <a href="{{ $invoice->invoice_attachment_url }}" target="_blank">
                                View / Download File
                            </a>
                        </p>
                    @endif
                </div>
            @endif

            <div style="margin-top:62px;font-family:cursive;font-size:42px;color:#222;transform:rotate(-6deg);">
                Thank You!
            </div>

            <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:28px;">
                <tr>
                    <td align="left" valign="top" style="font-size:12px;">
                        <strong>Payment Information</strong>

                        @if($invoice->card_payment_active)
                            <p style="margin:5px 0;">Card payment is allowed.</p>
                        @endif

                        @if($invoice->bank_account_allowed)
                            <p style="margin:5px 0;">Bank account payment is allowed.</p>
                        @endif

                        @if(!$invoice->card_payment_active && !$invoice->bank_account_allowed)
                            <p style="margin:5px 0;">Payment details will be added here.</p>
                        @endif
                    </td>

                    <td align="right" valign="top" style="font-family:Georgia,serif;font-size:18px;">
                        <strong>Prosixflow</strong>
                        <p style="margin:5px 0;font-size:12px;font-family:Arial,sans-serif;">Work Management</p>
                    </td>
                </tr>
            </table>

            @if($invoice->notes)
                <p style="margin-top:22px;font-size:13px;">
                    <strong>Notes:</strong> {{ $invoice->notes }}
                </p>
            @endif

        </div>
    </div>
</body>
</html>
