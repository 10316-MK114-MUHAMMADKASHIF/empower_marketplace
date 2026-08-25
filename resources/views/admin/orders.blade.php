<x-layouts.app title="Orders">
    @include('admin._nav', ['active' => 'orders'])
    <livewire:admin.order-list />
</x-layouts.app>
