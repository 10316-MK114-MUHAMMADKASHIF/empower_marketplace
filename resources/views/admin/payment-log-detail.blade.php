<x-layouts.app title="Payment Log">
    @include('admin._nav', ['active' => 'payment-logs'])
    <livewire:admin.payment-log-detail :payment-log="$paymentLog" />
</x-layouts.app>
