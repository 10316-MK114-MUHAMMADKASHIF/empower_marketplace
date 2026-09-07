<x-mail::message :message="$message ?? null">
# Payment Received — Thank You

We've received your payment for **{{ $order->package?->name ?? 'your compliance package' }}**. A copy of your receipt is attached to this email for your records.

<x-mail::panel>
**Order #:** {{ $order->id }}<br>
@if($order->discount_code)
**Discount ({{ $order->discount_code }}):** -${{ number_format((float) $order->discount_amount, 2) }}<br>
@endif
**Amount Paid:** ${{ number_format((float) $order->amount_paid, 2) }}<br>
**Date:** {{ $order->paid_at?->format('F j, Y') ?? now()->format('F j, Y') }}
</x-mail::panel>

Next, head to your portal to confirm your practice details so we can start preparing your compliance documents.

<x-mail::button :url="route('portal')">
Go to My Portal
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
