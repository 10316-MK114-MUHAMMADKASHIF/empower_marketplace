<x-mail::message :message="$message ?? null">
# Your Free Trial Has Ended

@if($reason === 'client_requested')
As requested, your free trial of **{{ $order->package?->name ?? 'your compliance package' }}** has
been cancelled. No payment has been charged.
@else
Your free trial of **{{ $order->package?->name ?? 'your compliance package' }}** ended without a
confirmed subscription, so it's been cancelled automatically. No payment has been charged.
@endif

Documents already generated during your trial remain available to download from your portal, but
new document generation is no longer available on this subscription.

Changed your mind? You're welcome to start a new subscription any time.

<x-mail::button :url="route('portal')">
Go to My Portal
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
