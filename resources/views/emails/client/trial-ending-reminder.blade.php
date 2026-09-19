<x-mail::message :message="$message ?? null">
# Your Free Trial Is Ending Soon

Your free trial of **{{ $order->package?->name ?? 'your compliance package' }}** ends on
**{{ $order->trial_ends_at?->format('F j, Y') }}**.

To keep uninterrupted access, confirm your subscription and we'll charge the card on file
(ending in {{ $order->card_last_four ?? '····' }}) **${{ number_format((float) $order->original_price, 2) }}**.
If we don't hear from you by then, your subscription will be cancelled automatically and no charge
will be made.

<x-mail::button :url="route('portal')">
Manage My Subscription
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
