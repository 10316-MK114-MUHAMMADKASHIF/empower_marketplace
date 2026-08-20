<x-layouts.app title="{{ isset($package) ? 'Edit Package' : 'New Package' }}">
    @include('admin._nav', ['active' => 'packages'])
    <livewire:admin.package-form :package="$package ?? null" />
</x-layouts.app>
