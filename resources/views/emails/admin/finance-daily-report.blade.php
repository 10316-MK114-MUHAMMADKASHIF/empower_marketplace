<x-mail::message :message="$message ?? null">
# Daily Subscription Report — {{ $reportDate->format('M j, Y') }}

{{ $rowCount }} new subscription{{ $rowCount === 1 ? '' : 's' }} and cancellation{{ $rowCount === 1 ? '' : 's' }} from {{ $reportDate->format('l, F j') }} {{ $rowCount === 1 ? 'is' : 'are' }} in the attached CSV.
@if($orderFormCount > 0)

The {{ $orderFormCount }} Order Form{{ $orderFormCount === 1 ? '' : 's' }} for the new subscription{{ $orderFormCount === 1 ? '' : 's' }} {{ $orderFormCount === 1 ? 'is' : 'are' }} attached as PDF{{ $orderFormCount === 1 ? '' : 's' }}, one per order (`order-form-<order #>.pdf`). Each uses the practice and user details for account setup.
@endif

Apply each new subscription to the practice's current form of payment, and mark it processed in the admin Orders list once done.

<x-mail::button :url="route('admin.orders')">
View Orders
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
