<x-mail::message :message="$message ?? null">
# New Order Form

A new order was just placed. Its Order Form is attached as a PDF, built from the practice and user details for account setup. It is billed to the client's current form of payment.

<x-mail::panel>
**Practice:** {{ $order->user->practice?->name ?: $order->user->name }}<br>
**Subscribed by:** {{ $order->user->name }} ({{ $order->user->email }})<br>
**Package:** {{ $order->package?->name ?? 'Compliance Package' }} ({{ ($order->billing_cycle ?? \App\Enums\BillingCycle::Annual)->label() }})<br>
**Order #:** {{ $order->id }}
</x-mail::panel>

This order will also appear in the next daily Finance report, with the sheet and its order form.

<x-mail::button :url="route('admin.orders')">
View Orders
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
