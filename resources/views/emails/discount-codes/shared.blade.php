<x-mail::message :message="$message ?? null">
# A discount code for you

Hi{{ $recipientName ? ' '.$recipientName : '' }},

@if($discountCode->type === \App\Enums\DiscountType::Percentage)
Use the code below to save {{ $discountCode->percentage }}% on your {{ config('app.name') }} package.
@else
Use the code below to get a {{ $discountCode->trial_days }}-day free trial of your {{ config('app.name') }} package.
@endif

<x-mail::panel>
**Code:** {{ $discountCode->code }}
@if($discountCode->expires_at)
<br>**Expires:** {{ $discountCode->expires_at->format('M j, Y') }}
@endif
</x-mail::panel>

Enter this code at checkout to apply it to your order.

<x-mail::button :url="$homeUrl">
View Packages
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
