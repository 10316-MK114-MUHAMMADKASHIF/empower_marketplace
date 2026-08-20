@php
    use App\Enums\IntakeSubmissionStatus;
    use App\Models\GeneratedDocument;
    use App\Models\IntakeSubmission;
    use App\Models\Lead;
    use App\Models\Order;

    $pendingReview = IntakeSubmission::whereIn('status', [
        IntakeSubmissionStatus::Submitted,
        IntakeSubmissionStatus::UnderReview,
    ])->count();
    $totalOrders = Order::count();
    $staleDocuments = GeneratedDocument::where('is_stale', true)->count();
    $newLeads = Lead::where('is_contacted', false)->count();
@endphp

<x-layouts.app title="Admin Dashboard">
    <div class="space-y-4">
        @include('admin._nav', ['active' => 'dashboard'])

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach([
                ['Pending Review', $pendingReview, route('admin.submissions')],
                ['Total Orders', $totalOrders, null],
                ['Stale Documents', $staleDocuments, route('admin.documents')],
                ['New Leads', $newLeads, route('admin.leads')],
            ] as [$label, $value, $link])
                @php $card = '<div class="text-xs font-extrabold uppercase tracking-wider text-empower-muted mb-2">'.$label.'</div><div class="text-3xl font-extrabold text-navy">'.$value.'</div>'; @endphp
                @if($link)
                    <a href="{{ $link }}" wire:navigate
                        class="block bg-white border border-empower-border rounded-[1.25rem] shadow-[0_18px_50px_rgba(10,32,55,0.08)] p-5 hover:border-navy/40 transition-colors">
                        {!! $card !!}
                    </a>
                @else
                    <div class="block bg-white border border-empower-border rounded-[1.25rem] shadow-[0_18px_50px_rgba(10,32,55,0.08)] p-5">
                        {!! $card !!}
                    </div>
                @endif
            @endforeach
        </div>
    </div>
</x-layouts.app>
