<x-mail::message :message="$message ?? null">
# Subscription Renewed — Thank You

Your subscription for **{{ $order->package?->name ?? 'your compliance package' }}** has been
renewed. A copy of your receipt is attached to this email for your records.

<x-mail::panel>
**Order #:** {{ $order->id }}<br>
**Amount Charged:** ${{ number_format((float) $order->amount_paid, 2) }}<br>
**Card:** ending in {{ $order->card_last_four ?? '····' }}<br>
**Date:** {{ $order->paid_at?->format('F j, Y') ?? now()->format('F j, Y') }}<br>
**Next Renewal:** {{ $order->next_bill_date?->format('F j, Y') }}
</x-mail::panel>

<x-mail::button :url="route('portal')">
Go to My Portal
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
