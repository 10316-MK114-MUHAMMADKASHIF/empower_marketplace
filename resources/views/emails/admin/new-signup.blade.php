<x-mail::message>
# New Signup

A new user just created an account.

<x-mail::panel>
**Name:** {{ $user->name }}<br>
**Email:** {{ $user->email }}<br>
**Signed Up At:** {{ $user->created_at?->format('F j, Y g:i A') }}
</x-mail::panel>

<x-mail::button :url="route('admin.dashboard')">
View in Admin Panel
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
