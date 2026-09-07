<x-layouts.app title="{{ isset($user) ? 'Edit User' : 'New User' }}">
    @include('admin._nav', ['active' => 'users'])
    <livewire:admin.user-form :user="$user ?? null" />
</x-layouts.app>
