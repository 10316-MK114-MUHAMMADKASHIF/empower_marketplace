<x-mail::message>
# Reset Your Password

Hi {{ $user->name }},

We received a request to reset the password for your {{ config('app.name') }} account. Click the button below to choose a new one.

<x-mail::button :url="$resetUrl">
Reset Password
</x-mail::button>

This password reset link will expire in {{ config('auth.passwords.users.expire') }} minutes.

If you did not request a password reset, no further action is required — your password will remain unchanged.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
