<?php

use App\Enums\DocumentDeliverySource;
use App\Enums\DocumentStatus;
use App\Enums\IntakeSubmissionStatus;
use App\Enums\OrderStatus;
use App\Mail\ClientDocumentsApprovedMail;
use App\Mail\ClientSubmissionStatusMail;
use App\Models\ActivityLog;
use App\Models\GeneratedDocument;
use App\Models\IntakeSubmission;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public int $submissionId;

    public string $reviewerNotes = '';

    /** Keyed by GeneratedDocument id. */
    public array $customDocumentFiles = [];

    /** GeneratedDocument ids checked for bulk approval. */
    public array $selectedDocumentIds = [];

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

    /** Every generated document for this submission's order, paired with whether its
     *  custom-upload slot should be shown (only when linked to a questionnaire the
     *  client actually uploaded, or when it has no questionnaire link at all). */
    #[Computed]
    public function documentsForReview(): Collection
    {
        $submission = $this->submission;
        $uploadedTypes = $submission->intakeUploads->map(fn ($u) => $u->upload_type)->all();

        return GeneratedDocument::where('order_id', $submission->order_id)
            ->with('reviewedBy')
            ->orderBy('document_type')
            ->get()
            ->map(function (GeneratedDocument $document) use ($uploadedTypes) {
                $linkedType = $document->document_type->linkedQuestionnaireType();
                $document->showsCustomUploadSlot = $linkedType === null || in_array($linkedType, $uploadedTypes, true);

                return $document;
            });
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

        Mail::to($submission->order->user->email)->send(new ClientSubmissionStatusMail($submission));

        unset($this->submission);
    }

    public function uploadCustomDocument(int $documentId): void
    {
        $submission = $this->submission;

        $document = GeneratedDocument::where('id', $documentId)
            ->where('order_id', $submission->order_id)
            ->firstOrFail();

        $this->validate([
            "customDocumentFiles.{$documentId}" => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ]);

        $file = $this->customDocumentFiles[$documentId];
        $storagePath = $file->store("private/compliance/{$document->order_id}/custom", 'local');

        $document->update([
            'custom_storage_path' => $storagePath,
            'custom_original_filename' => $file->getClientOriginalName(),
            'delivery_source' => DocumentDeliverySource::Custom,
            'reviewed_at' => null,
            'reviewed_by' => null,
        ]);

        ActivityLog::record(
            'document.custom_uploaded',
            "Custom {$document->document_type->label()} uploaded for order #{$document->order_id}.",
            user: auth()->user(),
            order: $document->order,
            subject: $document,
        );

        unset($this->customDocumentFiles[$documentId], $this->documentsForReview);
    }

    public function setDeliverySource(int $documentId, string $source): void
    {
        $submission = $this->submission;
        $deliverySource = DocumentDeliverySource::from($source);

        $document = GeneratedDocument::where('id', $documentId)
            ->where('order_id', $submission->order_id)
            ->firstOrFail();

        if ($deliverySource === DocumentDeliverySource::Custom && ! $document->hasCustomDocument()) {
            return;
        }

        $document->update([
            'delivery_source' => $deliverySource,
            'reviewed_at' => null,
            'reviewed_by' => null,
        ]);

        unset($this->documentsForReview);
    }

    public function approveSelectedDocuments(): void
    {
        $submission = $this->submission;

        $documents = GeneratedDocument::whereIn('id', $this->selectedDocumentIds)
            ->where('order_id', $submission->order_id)
            ->get()
            ->filter(fn (GeneratedDocument $document) => $document->canBeApproved());

        if ($documents->isEmpty()) {
            $this->selectedDocumentIds = [];

            return;
        }

        foreach ($documents as $document) {
            $document->update(['reviewed_at' => now(), 'reviewed_by' => auth()->id()]);
        }

        ActivityLog::record(
            'documents.approved',
            "{$documents->count()} document(s) approved for order #{$submission->order_id}.",
            user: auth()->user(),
            order: $submission->order,
            metadata: ['document_types' => $documents->map(fn ($d) => $d->document_type->value)->all()],
        );

        Mail::to($submission->order->user->email)->send(new ClientDocumentsApprovedMail($submission->order, $documents));

        $this->selectedDocumentIds = [];
        unset($this->documentsForReview);
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

        Mail::to($submission->order->user->email)->send(new ClientSubmissionStatusMail($submission));

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

    @if($this->documentsForReview->isNotEmpty())
        <div class="bg-white border border-empower-border rounded-[1.25rem] shadow-[0_18px_50px_rgba(10,32,55,0.08)] p-5">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-1">
                <h3 class="text-sm font-semibold text-navy">Document Review</h3>
                @if($this->documentsForReview->contains(fn ($d) => $d->canBeApproved()))
                    <button type="button" wire:click="approveSelectedDocuments" wire:confirm="Approve the selected document(s)? The client will be emailed once approved."
                        @disabled(empty($this->selectedDocumentIds))
                        class="inline-flex items-center gap-1 rounded bg-accent px-4 py-1.5 text-xs font-bold text-navy-dark hover:bg-accent-dark transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        Approve Selected
                    </button>
                @endif
            </div>
            <p class="text-xs text-empower-muted mb-4">Review each AI-generated document. If it's correct, select it and approve. If it's wrong, upload a corrected file below and choose which version to deliver.</p>

            <div class="space-y-4">
                @foreach($this->documentsForReview as $document)
                    @php
                        $badge = match(true) {
                            $document->is_stale => ['Outdated', 'bg-[#fde2e2] text-[#a53b3b]'],
                            $document->isApproved() => ['Approved', 'bg-[#dff7f0] text-[#0f7a4f]'],
                            $document->status === DocumentStatus::Completed => ['Pending Review', 'bg-[#edf2f7] text-empower-muted'],
                            $document->status === DocumentStatus::Failed => ['Failed', 'bg-[#fde2e2] text-[#a53b3b]'],
                            default => ['Generating', 'bg-[#fff3cd] text-[#9a6700]'],
                        };
                    @endphp
                    <div class="rounded-xl border border-empower-border p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3 mb-2">
                            <div class="flex items-start gap-3">
                                @if($document->canBeApproved())
                                    <input type="checkbox" wire:model.live="selectedDocumentIds" value="{{ $document->id }}" class="mt-1 h-4 w-4 rounded border-empower-border text-accent focus:ring-accent">
                                @endif
                                <div>
                                    <p class="text-sm font-semibold text-empower-text">
                                        {{ $document->document_type->label() }}{{ $document->oshaLocation ? ' — '.$document->oshaLocation->name : '' }}
                                    </p>
                                    @if($document->isApproved())
                                        <p class="text-xs text-empower-muted">Approved by {{ $document->reviewedBy?->name ?? 'admin' }} &middot; {{ $document->reviewed_at->format('M j, Y') }}</p>
                                    @elseif($document->status === DocumentStatus::Failed && $document->hasCustomDocument())
                                        <p class="text-xs text-[#9a6700]">AI generation failed — a custom file will be delivered instead.</p>
                                    @endif
                                </div>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[0.65rem] font-extrabold uppercase tracking-wider {{ $badge[1] }}">{{ $badge[0] }}</span>
                        </div>

                        <div class="flex flex-wrap items-center gap-3 text-xs mb-3">
                            @if($document->pdf_storage_path || $document->docx_storage_path)
                                <a href="{{ route('admin.generated-documents.download', ['document' => $document->id, 'source' => 'ai']) }}" class="font-bold text-[#1a7aad] hover:underline">Download AI-Generated File</a>
                            @endif
                            @if($document->hasCustomDocument())
                                <a href="{{ route('admin.generated-documents.download', ['document' => $document->id, 'source' => 'custom']) }}" class="font-bold text-[#1a7aad] hover:underline">Download Custom File</a>
                            @endif
                        </div>

                        @if($document->hasCustomDocument())
                            <div class="flex items-center gap-4 mb-3 text-xs">
                                <span class="font-semibold text-empower-muted">Deliver to client:</span>
                                <label class="flex items-center gap-1.5 cursor-pointer">
                                    <input type="radio" wire:change="setDeliverySource({{ $document->id }}, 'ai_generated')" @checked($document->delivery_source === DocumentDeliverySource::AiGenerated) name="delivery-{{ $document->id }}" class="text-accent focus:ring-accent">
                                    AI-Generated
                                </label>
                                <label class="flex items-center gap-1.5 cursor-pointer">
                                    <input type="radio" wire:change="setDeliverySource({{ $document->id }}, 'custom')" @checked($document->delivery_source === DocumentDeliverySource::Custom) name="delivery-{{ $document->id }}" class="text-accent focus:ring-accent">
                                    Custom
                                </label>
                            </div>
                        @endif

                        @if($document->showsCustomUploadSlot)
                            <div class="flex flex-wrap items-center gap-2">
                                <input wire:model="customDocumentFiles.{{ $document->id }}" type="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                    class="block text-xs text-[#5d6e7f] file:mr-3 file:py-1 file:px-3 file:rounded file:border-0 file:text-xs file:font-bold file:bg-[#12304f] file:text-white hover:file:bg-[#0a2037] cursor-pointer">
                                <button type="button" wire:click="uploadCustomDocument({{ $document->id }})"
                                    class="text-xs font-bold text-[#1a7aad] hover:underline">
                                    Upload {{ $document->hasCustomDocument() ? 'Replacement' : 'Custom File' }}
                                </button>
                            </div>
                            @error("customDocumentFiles.{$document->id}") <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

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
                x-text="confirmAction === 'approve' ? 'The client can then continue to their document dashboard. Generated documents still need to be individually reviewed and approved below before they are visible to the client.' : 'The client will be asked to re-upload based on your reviewer notes.'"></p>
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
