<table width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td width="50%" style="vertical-align: top;">
            <img src="{{ public_path('images/logo-email.png') }}" height="20">
            <p style="color: #6b7280; font-size: 10px; margin-top: 6px;">Healthcare Compliance Portal</p>
        </td>
        <td width="50%" style="text-align: right; vertical-align: top;">
            <h1 style="color: #12304f; font-size: 18px; margin: 0;">Payment Receipt</h1>
            <p style="color: #6b7280; font-size: 10px; margin-top: 6px;">
                Order #{{ $order->id }}<br>
                Receipt #{{ str_pad((string) $order->id, 6, '0', STR_PAD_LEFT) }}<br>
                {{ $order->paid_at?->format('F j, Y') ?? '—' }}
            </p>
        </td>
    </tr>
</table>

<div style="border-top: 2px solid #009bde; margin-top: 16px;"></div>

<table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 20px;">
    <tr>
        <td width="50%" style="vertical-align: top;">
            <p style="color: #6b7280; font-size: 9px; text-transform: uppercase; margin: 0;">Billed To</p>
            <p style="color: #12304f; font-size: 12px; font-weight: bold; margin: 4px 0 0;">{{ $order->user->name }}</p>
            <p style="color: #6b7280; font-size: 10px; margin: 2px 0 0;">{{ $order->user->email }}</p>
        </td>
        <td width="50%" style="text-align: right; vertical-align: top;">
            <p style="color: #6b7280; font-size: 9px; text-transform: uppercase; margin: 0;">Payment Status</p>
            <p style="color: #0f7a4f; font-size: 10px; font-weight: bold; margin: 4px 0 0;">PAID &middot; SIMULATED</p>
        </td>
    </tr>
</table>

<table width="100%" cellpadding="6" cellspacing="0" style="margin-top: 20px; border: 1px solid #e5e7eb;">
    <tr style="background-color: #12304f; color: #ffffff;">
        <td style="font-size: 11px; font-weight: bold;">Description</td>
        <td style="font-size: 11px; font-weight: bold; text-align: right;">Amount</td>
    </tr>
    <tr style="border-bottom: 1px solid #e5e7eb;">
        <td style="font-size: 10px; color: #374151;">{{ $order->package?->name ?? 'Compliance Package' }} — Annual Subscription</td>
        <td style="font-size: 10px; color: #374151; text-align: right;">${{ number_format((float) ($order->original_price ?? $order->amount_paid), 2) }}</td>
    </tr>
    @if($order->discount_code)
    <tr style="border-bottom: 1px solid #e5e7eb;">
        <td style="font-size: 10px; color: #374151;">Discount ({{ $order->discount_code }})</td>
        <td style="font-size: 10px; color: #374151; text-align: right;">-${{ number_format((float) $order->discount_amount, 2) }}</td>
    </tr>
    @endif
    <tr style="background-color: #f9fafb;">
        <td style="font-size: 11px; font-weight: bold; color: #12304f;">Total Paid</td>
        <td style="font-size: 11px; font-weight: bold; color: #12304f; text-align: right;">${{ number_format((float) $order->amount_paid, 2) }}</td>
    </tr>
</table>

<div style="border-top: 1px solid #e5e7eb; margin-top: 30px; padding-top: 12px; text-align: center;">
    <p style="font-size: 9px; color: #6b7280; margin: 0;"><strong style="color: #374151;">{{ config('app.name') }}</strong> &bull; Thank you for your business.</p>
    <p style="font-size: 9px; color: #6b7280; margin: 4px 0 0;">This receipt was generated on {{ now()->format('F j, Y') }} and serves as confirmation of payment.</p>
</div>
