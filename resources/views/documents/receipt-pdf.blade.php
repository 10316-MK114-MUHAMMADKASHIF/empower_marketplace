<table width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td>
            <img src="{{ public_path('images/logo-email.png') }}" height="24">
        </td>
    </tr>
</table>

<table width="100%" cellpadding="10" cellspacing="0" style="margin-top: 16px;">
    <tr>
        <td colspan="2" style="background-color: #1a9bd7;">
            <span style="color: #ffffff; font-size: 22px; font-weight: bold;">ORDER FORM</span>
        </td>
    </tr>
    <tr>
        <td width="50%" style="background-color: #1a9bd7; vertical-align: top;">
            <p style="color: #ffffff; font-size: 11px; font-weight: bold; margin: 0;">{{ $practice?->name ?: $order->user->name }}</p>
            <p style="color: #ffffff; font-size: 10px; margin: 4px 0 0;">{{ $order->user->email }}</p>
        </td>
        <td width="50%" style="background-color: #1a9bd7; text-align: right; vertical-align: top;">
            <p style="color: #ffffff; font-size: 9px; margin: 0;">Order #: {{ $order->id }}</p>
            <p style="color: #ffffff; font-size: 9px; margin: 4px 0 0;">Payment Date: {{ $order->paid_at?->format('F j, Y') ?? '—' }}</p>
            @if($order->next_bill_date)
            <p style="color: #ffffff; font-size: 9px; margin: 4px 0 0;">Next Renewal: {{ $order->next_bill_date->format('F j, Y') }}</p>
            @endif
            <p style="color: #ffffff; font-size: 9px; margin: 4px 0 0;">Receipt #: {{ str_pad((string) $order->id, 6, '0', STR_PAD_LEFT) }}</p>
            <p style="color: #ffffff; font-size: 9px; margin: 4px 0 0;">Created By: Admin</p>
        </td>
    </tr>
</table>

<table width="100%" cellpadding="7" cellspacing="0" style="margin-top: 16px; border: 1px solid #cbd5e1;">
    <tr>
        <td colspan="2" style="font-size: 11px; font-weight: bold; color: #12304f; border-bottom: 1px solid #cbd5e1;">Order Details</td>
    </tr>
    <tr style="border-bottom: 1px solid #e5e7eb;">
        <td width="45%" style="font-size: 9px; color: #6b7280;">Package:</td>
        <td style="font-size: 9px; color: #1a6fb0;">{{ $order->package?->name ?? 'Compliance Package' }}</td>
    </tr>
    @if($practice?->address)
    <tr style="border-bottom: 1px solid #e5e7eb;">
        <td style="font-size: 9px; color: #6b7280;">Practice Address:</td>
        <td style="font-size: 9px; color: #1a6fb0;">{{ $practice->address }}</td>
    </tr>
    @endif
    <tr style="border-bottom: 1px solid #e5e7eb;">
        <td style="font-size: 9px; color: #6b7280;">Billing Cycle:</td>
        <td style="font-size: 9px; color: #1a6fb0;">{{ ($order->billing_cycle ?? \App\Enums\BillingCycle::Annual)->label() }}</td>
    </tr>
    <tr>
        <td style="font-size: 9px; color: #6b7280;">Payment Status:</td>
        <td style="font-size: 9px; color: #0f7a4f; font-weight: bold;">PAID &middot; SIMULATED</td>
    </tr>
</table>

<p style="margin-top: 18px; margin-bottom: 6px; font-size: 12px; font-weight: bold; color: #12304f;">Products &amp; Services</p>

<table width="100%" cellpadding="6" cellspacing="0">
    <tr style="border-bottom: 2px solid #1a9bd7;">
        <td style="font-size: 10px; font-weight: bold; color: #173045;">Item</td>
        <td style="font-size: 10px; font-weight: bold; color: #173045; text-align: right;">Amount</td>
    </tr>
    <tr style="border-bottom: 1px solid #1a9bd7;">
        <td style="font-size: 10px; color: #374151;">{{ $order->package?->name ?? 'Compliance Package' }} ({{ ($order->billing_cycle ?? \App\Enums\BillingCycle::Annual)->label() }})</td>
        <td style="font-size: 10px; color: #374151; text-align: right;">${{ number_format((float) ($order->original_price ?? $order->amount_paid), 2) }}</td>
    </tr>
    @if($order->discount_code)
    <tr style="border-bottom: 1px solid #1a9bd7;">
        <td style="font-size: 10px; color: #374151;">Discount ({{ $order->discount_code }})</td>
        <td style="font-size: 10px; color: #374151; text-align: right;">-${{ number_format((float) $order->discount_amount, 2) }}</td>
    </tr>
    @endif
    <tr>
        <td style="font-size: 11px; font-weight: bold; color: #12304f;">Total Paid</td>
        <td style="font-size: 11px; font-weight: bold; color: #12304f; text-align: right;">${{ number_format((float) $order->amount_paid, 2) }}</td>
    </tr>
</table>

<p style="margin-top: 14px; margin-bottom: 0; font-size: 10px; font-weight: bold; color: #1f2937;">Terms</p>
<p style="margin-top: 4px; margin-bottom: 0; font-size: 8px; color: #1a6fb0; line-height: 1.3;">If applicable and quoted above, fees for Professional Services and/or Integration Services are due
    immediately and prior to CareCloud commencing any work. If applicable, Lab interface fees, if quoted at $0
    on this Order Form are subject to approval by Client's lab(s). If Client's lab(s) do not approve Client's
    interface(s), Client will have the option to cover the cost of the Lab Interface or proceed without a Lab
    Interface. Any products, work or services beyond the scope of any Overview are not included in this Order
    Form and will be quoted on a separate SOW or addendum.</p>
<p style="margin-top: 5px; margin-bottom: 0; font-size: 8px; color: #1a6fb0; line-height: 1.3;">By signing this Order Form, Client agrees to pay the fees as quoted above for the period of the Initial Term
    and, as applicable, any Renewal Term. This Order Form is governed by CareCloud Inc.'s Master Service
    Agreement ("MSA") and Business Associate Agreement ("BAA") found at
    [<a href="https://carecloud.app.box.com/v/carecloudmsa314">https://carecloud.app.box.com/v/carecloudmsa314</a>] and the Product Specific Terms ("PST") found at
    [<a href="https://carecloud.app.box.com/v/productterms">https://carecloud.app.box.com/v/productterms</a>]. The MSA, BAA, and PST are incorporated by reference and
    are made a part hereof. This Order Form may be superseded by a subsequent order form executed by Client
    and CareCloud.</p>

<p style="margin-top: 8px; margin-bottom: 0; font-size: 11px; font-weight: bold; color: #12304f;">Questions? Contact me</p>
<p style="margin-top: 4px; margin-bottom: 0; font-size: 9px; color: #374151;">support@empowerhci.com</p>
