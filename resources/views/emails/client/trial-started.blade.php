<x-mail::message :message="$message ?? null">
# Your Free Trial Has Started

Your free trial of **{{ $order->package?->name ?? 'your compliance package' }}** is now active — no
payment has been charged today.

<x-mail::panel>
**Order #:** {{ $order->id }}<br>
**Trial Ends:** {{ $order->trial_ends_at?->format('F j, Y') }}<br>
**Card on File:** ending in {{ $order->card_last_four ?? '····' }}<br>
**Price After Trial:** ${{ number_format((float) $order->original_price, 2) }}/{{ ($order->billing_cycle ?? \App\Enums\BillingCycle::Annual)->period() }}
</x-mail::panel>

Before your trial ends, we'll email you a reminder. You can confirm your subscription or cancel at
any time from your portal dashboard — no charge will happen until you confirm.

<x-mail::button :url="route('portal')">
Go to My Portal
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
