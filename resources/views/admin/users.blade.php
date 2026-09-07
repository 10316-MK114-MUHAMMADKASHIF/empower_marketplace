<x-layouts.app title="Users">
    @include('admin._nav', ['active' => 'users'])
    <livewire:admin.user-list />
</x-layouts.app>
