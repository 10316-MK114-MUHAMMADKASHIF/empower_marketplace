<x-mail::message>
# Thanks, {{ $lead->name }}!

We've received your message and a member of our team will be in touch shortly{{ $lead->package_interest ? ' about the **'.ucfirst($lead->package_interest).'** package' : '' }}.

Here's a copy of what you sent us:

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

If anything above isn't quite right, just reply to this email and let us know.

<x-mail::button :url="route('home')">
Visit {{ config('app.name') }}
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
