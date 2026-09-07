<x-layouts.app title="Payment Logs">
    @include('admin._nav', ['active' => 'payment-logs'])
    <livewire:admin.payment-log-list />
</x-layouts.app>
