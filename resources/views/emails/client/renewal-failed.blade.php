<x-mail::message :message="$message ?? null">
@if($cancelled)
# Your Subscription Has Been Cancelled

We were unable to charge the card on file for **{{ $order->package?->name ?? 'your compliance package' }}**
after several attempts, so your subscription has been cancelled. No further charges will be made.

Documents already generated remain available to download from your portal, but new document
generation is no longer available on this subscription.

Changed your mind, or has your card been updated? You're welcome to start a new subscription any time.
@else
# We Couldn't Process Your Renewal Payment

We were unable to charge the card on file (ending in {{ $order->card_last_four ?? '····' }}) for
**{{ $order->package?->name ?? 'your compliance package' }}**{{ $order->last_renewal_error ? ": {$order->last_renewal_error}" : '.' }}

We'll automatically retry over the next several days. To avoid any interruption, please update your
payment details as soon as possible.
@endif

<x-mail::button :url="route('portal')">
Manage My Subscription
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
