<x-layouts.app title="{{ isset($discountCode) ? 'Edit Discount Code' : 'New Discount Code' }}">
    @include('admin._nav', ['active' => 'discount-codes'])
    <livewire:admin.discount-code-form :discount-code="$discountCode ?? null" />
</x-layouts.app>
