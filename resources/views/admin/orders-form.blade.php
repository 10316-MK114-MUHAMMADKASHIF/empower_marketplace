<x-layouts.app title="Edit Order">
    @include('admin._nav', ['active' => 'orders'])
    <livewire:admin.order-form :order="$order" />
</x-layouts.app>
