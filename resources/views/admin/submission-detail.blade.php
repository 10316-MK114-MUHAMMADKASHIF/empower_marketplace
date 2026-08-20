<x-layouts.app title="Review Submission">
    @include('admin._nav', ['active' => 'submissions'])
    <livewire:admin.submission-detail :submission="$submission" />
</x-layouts.app>
