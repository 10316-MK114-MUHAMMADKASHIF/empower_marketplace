<x-layouts.app title="{{ isset($lead) ? 'Edit Lead' : 'New Lead' }}">
    @include('admin._nav', ['active' => 'leads'])
    <livewire:admin.lead-form :lead="$lead ?? null" />
</x-layouts.app>
