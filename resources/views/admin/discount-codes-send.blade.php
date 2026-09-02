<x-layouts.app title="Send Discount Code">
    @include('admin._nav', ['active' => 'discount-codes'])
    <livewire:admin.discount-code-send :discount-code="$discountCode" />
</x-layouts.app>
