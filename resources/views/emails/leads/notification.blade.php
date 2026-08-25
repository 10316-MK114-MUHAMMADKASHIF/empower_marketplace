<x-mail::message :message="$message ?? null">
# New Lead Received

A new contact/quote request just came in{{ $lead->package_interest ? ' for the '.ucfirst($lead->package_interest).' package' : '' }}.

<x-mail::panel>
**Name:** {{ $lead->name }}<br>
**Email:** {{ $lead->email }}<br>
**Phone:** {{ $lead->phone }}<br>
@if($lead->package_interest)
**Package interest:** {{ ucfirst($lead->package_interest) }}<br>
@endif
**Message:**<br>
{{ $lead->message }}
</x-mail::panel>

<x-mail::button :url="route('admin.leads')">
View in Admin Panel
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
