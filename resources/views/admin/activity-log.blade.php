<x-layouts.app title="Activity Log">
    @include('admin._nav', ['active' => 'activity-log'])
    <livewire:admin.activity-log-list />
</x-layouts.app>
