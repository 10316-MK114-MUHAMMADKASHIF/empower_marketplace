<?php

use App\Enums\IntakeSubmissionStatus;
use App\Enums\OrderStatus;
use App\Models\ActivityLog;
use App\Models\IntakeSubmission;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public int $submissionId;

    public string $reviewerNotes = '';

    public function mount(IntakeSubmission $submission): void
    {
        $this->submissionId = $submission->id;
        $this->reviewerNotes = $submission->reviewer_notes ?? '';
    }

    #[Computed]
    public function submission(): IntakeSubmission
    {
        return IntakeSubmission::with([
            'order.package',
            'order.user.practice.oshaLocations',
            'intakeUploads',
            'reviewer',
        ])->findOrFail($this->submissionId);
    }

    public function startReview(): void
    {
        $submission = $this->submission;

        if ($submission->status !== IntakeSubmissionStatus::Submitted) {
            return;
        }

        $submission->update(['status' => IntakeSubmissionStatus::UnderReview]);

        ActivityLog::record(
            'submission.under_review',
            "Submission for order #{$submission->order_id} moved to under review.",
            user: auth()->user(),
            order: $submission->order,
            subject: $submission,
        );

        unset($this->submission);
    }

    public function approve(): void
    {
        $submission = $this->submission;

        $submission->update([
            'status' => IntakeSubmissionStatus::Approved,
            'reviewer_notes' => null,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $submission->order->update(['status' => OrderStatus::Approved]);

        ActivityLog::record(
            'submission.approved',
            "Submission for order #{$submission->order_id} approved.",
            user: auth()->user(),
            order: $submission->order,
            subject: $submission,
        );

        unset($this->submission);
    }

    public function reject(): void
    {
        $this->validate([
            'reviewerNotes' => 'required|string|max:2000',
        ], [
            'reviewerNotes.required' => 'Please explain what needs to be fixed before rejecting.',
        ]);

        $submission = $this->submission;

        $submission->update([
            'status' => IntakeSubmissionStatus::Rejected,
            'reviewer_notes' => $this->reviewerNotes,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        ActivityLog::record(
            'submission.rejected',
            "Submission for order #{$submission->order_id} rejected.",
            user: auth()->user(),
            order: $submission->order,
            subject: $submission,
            metadata: ['reviewer_notes' => $this->reviewerNotes],
        );

        unset($this->submission);
    }
};
?>

<div class="space-y-4" x-data="{ confirmAction: null }">
    <a href="{{ route('admin.submissions') }}" wire:navigate class="text-sm font-semibold text-[#1a7aad] hover:underline">&larr; Back to submissions</a>

    @php $submission = $this->submission; $practice = $submission->order?->user?->practice; @endphp

    <div class="bg-white border border-empower-border rounded-[1.25rem] shadow-[0_18px_50px_rgba(10,32,55,0.08)] p-5">
        <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
            <div>
                <h2 class="text-lg font-semibold text-navy">{{ $practice?->name ?: 'Unnamed practice' }}</h2>
                <p class="text-sm text-empower-muted">{{ $submission->order?->user?->email }} &middot; {{ $submission->order?->package?->name }}</p>
            </div>
            @php
                $badgeClasses = match($submission->status) {
                    IntakeSubmissionStatus::Approved => 'bg-[#dff7f0] text-[#0f7a4f]',
                    IntakeSubmissionStatus::Rejected => 'bg-[#fde2e2] text-[#a53b3b]',
                    IntakeSubmissionStatus::UnderReview => 'bg-[#fff3cd] text-[#9a6700]',
                    default => 'bg-[#edf2f7] text-empower-muted',
                };
            @endphp
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-extrabold uppercase tracking-wider {{ $badgeClasses }}">
                {{ str_replace('_', ' ', $submission->status->value) }}
            </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div><span class="text-empower-muted">Address</span><br><span class="text-empower-text">{{ $practice?->address ?: '—' }}</span></div>
            <div><span class="text-empower-muted">NPI Number</span><br><span class="text-empower-text">{{ $practice?->npi_number ?: '—' }}</span></div>
            <div><span class="text-empower-muted">Specialty</span><br><span class="text-empower-text">{{ $practice?->specialty ?: '—' }}</span></div>
            <div><span class="text-empower-muted">Billable Providers</span><br><span class="text-empower-text">{{ $practice?->billable_providers_count ?: '—' }}</span></div>
        </div>

        @if($practice?->oshaLocations->isNotEmpty())
            <div class="mt-4 pt-4 border-t border-empower-border">
                <p class="text-xs font-extrabold uppercase tracking-wider text-empower-muted mb-2">OSHA Locations</p>
                <div class="flex flex-wrap gap-2">
                    @foreach($practice->oshaLocations as $loc)
                        <span class="inline-flex items-center rounded-full bg-page px-3 py-1 text-xs font-semibold text-navy">{{ $loc->name }}</span>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <div class="bg-white border border-empower-border rounded-[1.25rem] shadow-[0_18px_50px_rgba(10,32,55,0.08)] p-5">
        <h3 class="text-sm font-semibold text-navy mb-3">Uploaded Forms</h3>
        @forelse($submission->intakeUploads as $upload)
            <div class="flex items-center justify-between gap-3 py-2.5 border-b border-empower-border last:border-b-0">
                <div>
                    <p class="text-sm font-semibold text-empower-text">{{ $upload->original_filename }}</p>
                    <p class="text-xs text-empower-muted">{{ $upload->upload_type->value }} &middot; {{ $upload->fileSizeForHumans() }} &middot; AI extraction: {{ $upload->ai_extraction_status->value }}</p>
                </div>
                <a href="{{ route('admin.uploads.download', $upload) }}" class="text-xs font-bold text-[#1a7aad] hover:underline">Download</a>
            </div>
        @empty
            <p class="text-sm text-empower-muted italic">No files uploaded.</p>
        @endforelse
    </div>

    @if($submission->status === IntakeSubmissionStatus::Rejected && $submission->reviewer_notes)
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <p class="font-semibold mb-0.5">Reviewer notes sent to client:</p>
            <p>{{ $submission->reviewer_notes }}</p>
        </div>
    @endif

    @if(in_array($submission->status, [IntakeSubmissionStatus::Submitted, IntakeSubmissionStatus::UnderReview]))
        <div class="bg-white border border-empower-border rounded-[1.25rem] shadow-[0_18px_50px_rgba(10,32,55,0.08)] p-5">
            <h3 class="text-sm font-semibold text-navy mb-3">Review Decision</h3>

            @if($submission->status === IntakeSubmissionStatus::Submitted)
                <button wire:click="startReview" class="mb-4 text-xs font-bold text-[#1a7aad] hover:underline">Mark as Under Review</button>
            @endif

            <div class="mb-4">
                <label class="block text-sm font-semibold text-[#31465b] mb-1.5">Reviewer notes (required to reject)</label>
                <textarea wire:model="reviewerNotes" rows="3" placeholder="Explain what the practice needs to fix…"
                    class="w-full rounded-xl border border-empower-border bg-page px-4 py-2.5 text-sm text-empower-text focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition"></textarea>
                @error('reviewerNotes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="button" x-on:click="confirmAction = 'approve'"
                    class="inline-flex items-center gap-1 rounded bg-accent px-5 py-2 text-sm font-bold text-navy-dark hover:bg-accent-dark transition-colors">
                    Approve
                </button>
                <button type="button" x-on:click="confirmAction = 'reject'"
                    class="inline-flex items-center gap-1 rounded border border-red-300 px-5 py-2 text-sm font-bold text-red-700 hover:bg-red-50 transition-colors">
                    Reject
                </button>
            </div>
        </div>
    @endif

    {{-- Approve/Reject confirmation modal --}}
    <div x-show="confirmAction !== null" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
        <div class="w-full max-w-sm bg-white rounded-[1.25rem] shadow-xl p-6" x-on:click.outside="confirmAction = null">
            <h3 class="text-base font-semibold text-navy mb-2"
                x-text="confirmAction === 'approve' ? 'Approve this submission?' : 'Reject this submission?'"></h3>
            <p class="text-sm text-empower-muted mb-5"
                x-text="confirmAction === 'approve' ? 'Documents will be unlocked for the client.' : 'The client will be asked to re-upload based on your reviewer notes.'"></p>
            <div class="flex justify-end gap-3">
                <button type="button" x-on:click="confirmAction = null"
                    class="rounded-lg border border-empower-border px-4 py-2 text-sm font-semibold text-empower-muted hover:bg-page transition-colors">
                    Cancel
                </button>
                <button type="button"
                    x-on:click="confirmAction === 'approve' ? $wire.approve() : $wire.reject(); confirmAction = null"
                    x-bind:class="confirmAction === 'approve' ? 'bg-accent text-navy-dark hover:bg-accent-dark' : 'bg-red-600 text-white hover:bg-red-700'"
                    class="inline-flex items-center gap-1 rounded px-5 py-2 text-sm font-bold transition-colors">
                    <span x-text="confirmAction === 'approve' ? 'Approve' : 'Reject'"></span>
                </button>
            </div>
        </div>
    </div>
</div>
