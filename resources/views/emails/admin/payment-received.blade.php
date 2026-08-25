<x-mail::message :message="$message ?? null">
# New Payment Received

A client just completed payment for the **{{ $order->package?->name ?? 'Compliance Package' }}** package.

<x-mail::panel>
**Client:** {{ $order->user?->name }}<br>
**Email:** {{ $order->user?->email }}<br>
**Amount Paid:** ${{ number_format((float) $order->amount_paid, 2) }}<br>
**Order #:** {{ $order->id }}
</x-mail::panel>

<x-mail::button :url="route('admin.dashboard')">
View in Admin Panel
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
