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
use App\Models\IntakeUpload;
use App\Models\Order;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public int $submissionId;

    public string $reviewerNotes = '';

    /** Set when an action succeeded but its client notification email failed to send. */
    public ?string $notice = null;

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
            ->with(['reviewedBy', 'intakeUpload'])
            ->orderBy('document_type')
            ->get()
            ->map(function (GeneratedDocument $document) use ($uploadedTypes) {
                $linkedType = $document->document_type->linkedQuestionnaireType();
                $document->showsCustomUploadSlot = $linkedType === null || in_array($linkedType, $uploadedTypes, true);

                return $document;
            });
    }

    /** "Approve Selected" only becomes clickable once every document still awaiting a
     *  decision (not yet approved, not stale) has a delivery source checked — partial
     *  selections stay disabled so nothing gets approved by accident before it's ready. */
    #[Computed]
    public function allReviewableDocumentsSelected(): bool
    {
        $reviewable = $this->documentsForReview->filter(
            fn (GeneratedDocument $document) => ! $document->isApproved() && ! $document->is_stale
        );

        if ($reviewable->isEmpty()) {
            return false;
        }

        return $reviewable->every(fn (GeneratedDocument $document) => in_array($document->id, $this->selectedDocumentIds, true));
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

    public function deleteIntakeUpload(int $uploadId): void
    {
        $submission = $this->submission;

        $upload = IntakeUpload::where('id', $uploadId)
            ->where('intake_submission_id', $submission->id)
            ->firstOrFail();

        if ($upload->storage_path) {
            Storage::disk('local')->delete($upload->storage_path);
        }

        $filename = $upload->original_filename;
        $upload->delete();

        ActivityLog::record(
            'upload.deleted',
            "{$filename} was deleted from order #{$submission->order_id} by an admin.",
            user: auth()->user(),
            order: $submission->order,
        );

        unset($this->submission);
    }

    public function deleteSubmission(): void
    {
        $submission = $this->submission;
        $orderId = $submission->order_id;

        foreach ($submission->intakeUploads as $upload) {
            if ($upload->storage_path) {
                Storage::disk('local')->delete($upload->storage_path);
            }
        }

        $submission->delete();

        ActivityLog::record('submission.deleted', "Intake submission for order #{$orderId} was deleted by an admin.", user: auth()->user());

        $this->redirect(route('admin.submissions'), navigate: true);
    }

    public function revokeApproval(int $documentId): void
    {
        $submission = $this->submission;

        $document = GeneratedDocument::where('id', $documentId)
            ->where('order_id', $submission->order_id)
            ->firstOrFail();

        if (! $document->isApproved()) {
            return;
        }

        $document->update(['reviewed_at' => null, 'reviewed_by' => null, 'revoked_at' => now()]);

        $this->selectedDocumentIds = array_values(array_diff($this->selectedDocumentIds, [$documentId]));

        ActivityLog::record(
            'document.approval_revoked',
            "Approval for {$document->document_type->label()} was revoked for order #{$document->order_id}.",
            user: auth()->user(),
            order: $document->order,
            subject: $document,
        );

        unset($this->documentsForReview);
    }

    public function deleteGeneratedDocument(int $documentId): void
    {
        $submission = $this->submission;

        $document = GeneratedDocument::where('id', $documentId)
            ->where('order_id', $submission->order_id)
            ->firstOrFail();

        foreach ([$document->pdf_storage_path, $document->docx_storage_path, $document->custom_storage_path] as $path) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
        }

        $label = $document->document_type->label();
        $document->delete();

        $this->selectedDocumentIds = array_values(array_diff($this->selectedDocumentIds, [$documentId]));

        ActivityLog::record('document.deleted', "{$label} was deleted from order #{$submission->order_id} by an admin.", user: auth()->user(), order: $submission->order);

        unset($this->documentsForReview);
    }

    /** Undoes an accidental rejection — clears the reviewer notes and puts it back under review. */
    public function reopen(): void
    {
        $submission = $this->submission;

        if ($submission->status !== IntakeSubmissionStatus::Rejected) {
            return;
        }

        $submission->update([
            'status' => IntakeSubmissionStatus::UnderReview,
            'reviewer_notes' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);

        ActivityLog::record(
            'submission.reopened',
            "Submission for order #{$submission->order_id} reopened for review after being rejected.",
            user: auth()->user(),
            order: $submission->order,
            subject: $submission,
        );

        $this->reviewerNotes = '';

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

        // Approving the submission is the decision a client-facing "you're all set" hinges on —
        // finalize every document that's already ready to go so it doesn't silently sit at
        // "Pending Review" (and undownloadable) just because nobody separately clicked
        // "Approve Selected" for it too. This step never emails on its own — the client is
        // notified once, below, for the submission as a whole.
        $readyDocuments = GeneratedDocument::where('order_id', $submission->order_id)
            ->get()
            ->filter(fn (GeneratedDocument $document) => $document->canBeApproved());

        $this->selectedDocumentIds = [];
        $this->finalizeDocumentApprovals($submission->order, $readyDocuments);

        try {
            Mail::to($submission->order->user->email)->send(new ClientSubmissionStatusMail($submission));
        } catch (\Throwable $e) {
            report($e);
            $this->notice = 'Submission approved, but the client notification email failed to send.';
        }

        // One consolidated "your documents are ready" email listing every ready document for
        // this order — not just ones newly finalized above — since documents approved earlier
        // via "Approve Selected" no longer emailed on their own and still need to be told about.
        $allReadyDocuments = GeneratedDocument::where('order_id', $submission->order_id)
            ->get()
            ->filter(fn (GeneratedDocument $document) => $document->isReady());

        if ($allReadyDocuments->isNotEmpty()) {
            try {
                Mail::to($submission->order->user->email)->send(new ClientDocumentsApprovedMail($submission->order, $allReadyDocuments));
            } catch (\Throwable $e) {
                report($e);
                $this->notice = trim(($this->notice ? $this->notice.' ' : '').'Submission approved, but the documents-ready email failed to send.');
            }
        }

        unset($this->submission);
    }

    /** Marks each document approved — shared by both the submission-level "Approve" action
     *  and the per-document "Approve Selected" action. Does NOT email the client: the client
     *  is notified once, when the submission itself is approved (see approve()), not every
     *  time a document gets picked and approved along the way. */
    private function finalizeDocumentApprovals(Order $order, Collection $documents): void
    {
        if ($documents->isEmpty()) {
            return;
        }

        foreach ($documents as $document) {
            $document->update(['reviewed_at' => now(), 'reviewed_by' => auth()->id(), 'revoked_at' => null]);
        }

        ActivityLog::record(
            'documents.approved',
            "{$documents->count()} document(s) approved for order #{$order->id}.",
            user: auth()->user(),
            order: $order,
            metadata: ['document_types' => $documents->map(fn ($d) => $d->document_type->value)->all()],
        );

        unset($this->documentsForReview);
    }

    /** Livewire calls this automatically as soon as a file finishes uploading into
     *  customDocumentFiles.{documentId} — no separate "Upload" click needed. */
    public function updatedCustomDocumentFiles($value, $key): void
    {
        $this->uploadCustomDocument((int) $key);
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

        $wasApproved = $document->isApproved();

        $file = $this->customDocumentFiles[$documentId];
        $storagePath = $file->store("private/compliance/{$document->order_id}/custom", 'local');

        $document->update([
            'custom_storage_path' => $storagePath,
            'custom_original_filename' => $file->getClientOriginalName(),
            'delivery_source' => DocumentDeliverySource::Custom,
            'reviewed_at' => null,
            'reviewed_by' => null,
            'revoked_at' => $wasApproved ? now() : $document->revoked_at,
        ]);

        // Uploading a custom file is itself the admin's choice to skip the
        // AI-generated version, so it should also mark the document selected
        // for the next "Approve Selected" batch — no extra click required.
        if (! in_array($documentId, $this->selectedDocumentIds, true)) {
            $this->selectedDocumentIds[] = $documentId;
        }

        ActivityLog::record(
            'document.custom_uploaded',
            "Custom {$document->document_type->label()} uploaded for order #{$document->order_id}.",
            user: auth()->user(),
            order: $document->order,
            subject: $document,
        );

        unset($this->customDocumentFiles[$documentId], $this->documentsForReview);
    }

    public function deleteCustomDocument(int $documentId): void
    {
        $submission = $this->submission;

        $document = GeneratedDocument::where('id', $documentId)
            ->where('order_id', $submission->order_id)
            ->firstOrFail();

        if (! $document->hasCustomDocument()) {
            return;
        }

        Storage::disk('local')->delete($document->custom_storage_path);

        // If the custom file was the one set to deliver, falling back to the
        // AI-generated version means any prior approval no longer reflects
        // what will actually be sent — revoke it so it's reviewed again.
        $wasActiveDeliverySource = $document->delivery_source === DocumentDeliverySource::Custom;
        $revokes = $wasActiveDeliverySource && $document->isApproved();

        $document->update([
            'custom_storage_path' => null,
            'custom_original_filename' => null,
            'delivery_source' => DocumentDeliverySource::AiGenerated,
            'reviewed_at' => $wasActiveDeliverySource ? null : $document->reviewed_at,
            'reviewed_by' => $wasActiveDeliverySource ? null : $document->reviewed_by,
            'revoked_at' => $revokes ? now() : $document->revoked_at,
        ]);

        $this->selectedDocumentIds = array_values(array_diff($this->selectedDocumentIds, [$documentId]));

        ActivityLog::record(
            'document.custom_deleted',
            "Custom {$document->document_type->label()} removed for order #{$document->order_id}.",
            user: auth()->user(),
            order: $document->order,
            subject: $document,
        );

        unset($this->documentsForReview);
    }

    /**
     * Fires when the admin checks the "AI-Generated File" or "Custom File" box
     * for a document. Setting a delivery source now doubles as selecting the
     * document for the next bulk approval — checking the already-selected
     * source again deselects it, matching normal checkbox toggle behavior.
     */
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

        if (in_array($documentId, $this->selectedDocumentIds, true) && $document->delivery_source === $deliverySource) {
            $this->selectedDocumentIds = array_values(array_diff($this->selectedDocumentIds, [$documentId]));

            return;
        }

        $wasApproved = $document->isApproved();

        $document->update([
            'delivery_source' => $deliverySource,
            'reviewed_at' => null,
            'reviewed_by' => null,
            'revoked_at' => $wasApproved ? now() : $document->revoked_at,
        ]);

        if (! in_array($documentId, $this->selectedDocumentIds, true)) {
            $this->selectedDocumentIds[] = $documentId;
        }

        unset($this->documentsForReview);
    }

    /** Approves the checked documents without emailing the client — that happens once, when
     *  the admin approves the submission as a whole (see approve()), not at every document
     *  pick along the way. */
    public function approveSelectedDocuments(): void
    {
        $submission = $this->submission;

        $documents = GeneratedDocument::whereIn('id', $this->selectedDocumentIds)
            ->where('order_id', $submission->order_id)
            ->get()
            ->filter(fn (GeneratedDocument $document) => $document->canBeApproved());

        $this->selectedDocumentIds = [];
        $this->finalizeDocumentApprovals($submission->order, $documents);
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

        try {
            Mail::to($submission->order->user->email)->send(new ClientSubmissionStatusMail($submission));
        } catch (\Throwable $e) {
            report($e);
            $this->notice = 'Submission rejected, but the client notification email failed to send.';
        }

        unset($this->submission);
    }
};
?>

<div class="space-y-4" x-data="{
    confirmAction: null,
    confirmDocumentId: null,
    confirmUploadId: null,
    modalText: {
        approve: { title: 'Approve this submission?', body: 'The client can then continue to their document dashboard. Generated documents still need to be individually reviewed and approved below before they are visible to the client.', label: 'Approve', danger: false },
        reject: { title: 'Reject this submission?', body: 'The client will be asked to re-upload based on your reviewer notes.', label: 'Reject', danger: true },
        deleteCustom: { title: 'Remove this custom file?', body: 'This cannot be undone. The AI-generated file will be delivered instead unless a new custom file is uploaded.', label: 'Remove', danger: true },
        reopen: { title: 'Reopen this submission for review?', body: 'This clears the rejection and reviewer notes, and puts the submission back under review.', label: 'Reopen', danger: false },
        approveDocuments: { title: 'Approve the selected document(s)?', body: 'The client will be emailed once approved.', label: 'Approve', danger: false },
        revokeApproval: { title: 'Revoke approval for this document?', body: 'It goes back to pending review and the client will no longer be able to download it until it is approved again.', label: 'Revoke', danger: true },
        deleteDocument: { title: 'Delete this document?', body: 'This permanently deletes the generated document and any custom file uploaded for it. This cannot be undone.', label: 'Delete', danger: true },
        deleteUpload: { title: 'Delete this uploaded file?', body: 'This permanently deletes the file the client uploaded. This cannot be undone.', label: 'Delete', danger: true },
        deleteSubmission: { title: 'Delete this intake submission?', body: 'This permanently deletes the submission and every file the client uploaded for it. This cannot be undone.', label: 'Delete', danger: true },
    },
}">
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.submissions') }}" wire:navigate class="text-sm font-semibold text-[#1a7aad] hover:underline">&larr; Back to submissions</a>
        <button type="button" x-on:click="confirmAction = 'deleteSubmission'" class="text-xs font-bold text-red-600 hover:underline">Delete Submission</button>
    </div>

    @if($notice)
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 flex items-start justify-between gap-3">
            <span>{{ $notice }}</span>
            <button type="button" wire:click="$set('notice', null)" class="text-amber-600 hover:text-amber-800 font-bold leading-none">&times;</button>
        </div>
    @endif

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
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.uploads.download', $upload) }}" class="text-xs font-bold text-[#1a7aad] hover:underline">Download</a>
                    <button type="button" x-on:click="confirmAction = 'deleteUpload'; confirmUploadId = {{ $upload->id }}"
                        class="text-xs font-bold text-red-600 hover:underline">Delete</button>
                </div>
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
                    <button type="button" x-on:click="confirmAction = 'approveDocuments'"
                        @disabled(! $this->allReviewableDocumentsSelected)
                        title="{{ $this->allReviewableDocumentsSelected ? '' : 'Select a file for every document below to enable this' }}"
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
                    @php
                        // A document can only be checked into a box while it's awaiting a decision —
                        // once approved or marked outdated, the boxes go read-only (see below).
                        $reviewable = ! $document->isApproved() && ! $document->is_stale;
                        $aiSelected = $reviewable && in_array($document->id, $this->selectedDocumentIds, true) && $document->delivery_source === DocumentDeliverySource::AiGenerated;
                        $customSelected = $reviewable && in_array($document->id, $this->selectedDocumentIds, true) && $document->delivery_source === DocumentDeliverySource::Custom;
                    @endphp
                    <div class="rounded-xl border border-empower-border p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3 mb-2">
                            <div>
                                <p class="text-sm font-semibold text-empower-text">
                                    {{ $document->document_type->label() }}{{ $document->oshaLocation ? ' — '.$document->oshaLocation->name : '' }}{{ $document->intakeUpload ? ' — '.$document->intakeUpload->original_filename : '' }}
                                </p>
                                @if($document->isApproved())
                                    <p class="text-xs text-empower-muted">Approved by {{ $document->reviewedBy?->name ?? 'admin' }} &middot; {{ $document->reviewed_at->format('M j, Y') }} &middot; delivered the {{ $document->delivery_source === DocumentDeliverySource::Custom ? 'custom' : 'AI-generated' }} file</p>
                                @elseif($document->status === DocumentStatus::Failed && $document->hasCustomDocument())
                                    <p class="text-xs text-[#9a6700]">AI generation failed — a custom file will be delivered instead.</p>
                                @endif
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[0.65rem] font-extrabold uppercase tracking-wider {{ $badge[1] }}">{{ $badge[0] }}</span>
                                @if($document->isApproved())
                                    <button type="button" x-on:click="confirmAction = 'revokeApproval'; confirmDocumentId = {{ $document->id }}"
                                        class="text-xs font-bold text-red-600 hover:underline">Revoke</button>
                                @endif
                                <button type="button" x-on:click="confirmAction = 'deleteDocument'; confirmDocumentId = {{ $document->id }}"
                                    class="text-xs font-bold text-red-600 hover:underline">Delete</button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="rounded-lg border {{ $aiSelected ? 'border-accent ring-1 ring-accent' : 'border-empower-border' }} bg-[#f8fafc] p-3">
                                @if($reviewable && ($document->pdf_storage_path || $document->docx_storage_path))
                                    <label class="flex items-center gap-2 cursor-pointer mb-2">
                                        <input type="checkbox" wire:click="setDeliverySource({{ $document->id }}, 'ai_generated')" @checked($aiSelected) class="h-4 w-4 rounded border-empower-border text-accent focus:ring-accent">
                                        <span class="text-[0.65rem] font-extrabold uppercase tracking-wider text-empower-muted">AI-Generated File</span>
                                    </label>
                                @else
                                    <p class="text-[0.65rem] font-extrabold uppercase tracking-wider text-empower-muted mb-2">AI-Generated File</p>
                                @endif
                                @if($document->pdf_storage_path || $document->docx_storage_path)
                                    <a href="{{ route('admin.generated-documents.download', ['document' => $document->id, 'source' => 'ai']) }}" class="text-xs font-bold text-[#1a7aad] hover:underline">Download AI-Generated File</a>
                                @else
                                    <p class="text-xs text-empower-muted">Not yet generated.</p>
                                @endif
                            </div>

                            <div class="rounded-lg border {{ $customSelected ? 'border-accent ring-1 ring-accent' : 'border-empower-border' }} bg-[#f8fafc] p-3">
                                @if($reviewable && $document->hasCustomDocument())
                                    <label class="flex items-center gap-2 cursor-pointer mb-2">
                                        <input type="checkbox" wire:click="setDeliverySource({{ $document->id }}, 'custom')" @checked($customSelected) class="h-4 w-4 rounded border-empower-border text-accent focus:ring-accent">
                                        <span class="text-[0.65rem] font-extrabold uppercase tracking-wider text-empower-muted">Custom File</span>
                                    </label>
                                @else
                                    <p class="text-[0.65rem] font-extrabold uppercase tracking-wider text-empower-muted mb-2">Custom File</p>
                                @endif
                                @if($document->hasCustomDocument())
                                    <div class="flex items-center gap-3 mb-2">
                                        <a href="{{ route('admin.generated-documents.download', ['document' => $document->id, 'source' => 'custom']) }}" class="text-xs font-bold text-[#1a7aad] hover:underline">Download Custom File</a>
                                        @if($reviewable)
                                            <button type="button" x-on:click="confirmAction = 'deleteCustom'; confirmDocumentId = {{ $document->id }}"
                                                class="text-xs font-bold text-red-600 hover:underline">
                                                Remove
                                            </button>
                                        @endif
                                    </div>
                                @endif

                                @if($document->showsCustomUploadSlot)
                                    <div class="flex flex-wrap items-center gap-2">
                                        <input wire:model="customDocumentFiles.{{ $document->id }}" type="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                            wire:loading.attr="disabled" wire:target="customDocumentFiles.{{ $document->id }}"
                                            class="block text-xs text-[#5d6e7f] file:mr-3 file:py-1 file:px-3 file:rounded file:border-0 file:text-xs file:font-bold file:bg-[#12304f] file:text-white hover:file:bg-[#0a2037] cursor-pointer">
                                        <span wire:loading wire:target="customDocumentFiles.{{ $document->id }}" class="text-xs font-semibold text-empower-muted">Uploading…</span>
                                    </div>
                                    @error("customDocumentFiles.{$document->id}") <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                @elseif(! $document->hasCustomDocument())
                                    <p class="text-xs text-empower-muted">No custom file uploaded.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if($submission->status === IntakeSubmissionStatus::Rejected)
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            @if($submission->reviewer_notes)
                <p class="font-semibold mb-0.5">Reviewer notes sent to client:</p>
                <p class="mb-3">{{ $submission->reviewer_notes }}</p>
            @endif
            <p class="text-xs text-red-600 mb-2">Rejected by mistake? Reopening clears the reviewer notes and puts it back under review.</p>
            <button type="button" x-on:click="confirmAction = 'reopen'"
                class="inline-flex items-center gap-1 rounded border border-red-300 bg-white px-4 py-1.5 text-xs font-bold text-red-700 hover:bg-red-100 transition-colors">
                Reopen for Review
            </button>
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
                    :disabled="!$wire.reviewerNotes || !$wire.reviewerNotes.trim()"
                    :class="(!$wire.reviewerNotes || !$wire.reviewerNotes.trim()) ? 'opacity-50 cursor-not-allowed' : 'hover:bg-red-50'"
                    class="inline-flex items-center gap-1 rounded border border-red-300 px-5 py-2 text-sm font-bold text-red-700 transition-colors">
                    Reject
                </button>
            </div>
        </div>
    @endif

    {{-- Shared confirmation modal — text/label/danger-styling per action is looked up from modalText so
         adding a new confirmAction doesn't require touching a giant per-attribute ternary chain. --}}
    <div x-show="confirmAction !== null" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
        <div class="w-full max-w-sm bg-white rounded-[1.25rem] shadow-xl p-6" x-on:click.outside="confirmAction = null">
            <h3 class="text-base font-semibold text-navy mb-2" x-text="modalText[confirmAction]?.title"></h3>
            <p class="text-sm text-empower-muted mb-5" x-text="modalText[confirmAction]?.body"></p>
            <div class="flex justify-end gap-3">
                <button type="button" x-on:click="confirmAction = null"
                    class="rounded-lg border border-empower-border px-4 py-2 text-sm font-semibold text-empower-muted hover:bg-page transition-colors">
                    Cancel
                </button>
                <button type="button"
                    x-on:click="(confirmAction === 'approve' ? $wire.approve()
                        : confirmAction === 'reject' ? $wire.reject()
                        : confirmAction === 'deleteCustom' ? $wire.deleteCustomDocument(confirmDocumentId)
                        : confirmAction === 'reopen' ? $wire.reopen()
                        : confirmAction === 'approveDocuments' ? $wire.approveSelectedDocuments()
                        : confirmAction === 'revokeApproval' ? $wire.revokeApproval(confirmDocumentId)
                        : confirmAction === 'deleteDocument' ? $wire.deleteGeneratedDocument(confirmDocumentId)
                        : confirmAction === 'deleteUpload' ? $wire.deleteIntakeUpload(confirmUploadId)
                        : $wire.deleteSubmission()
                    ).then(() => confirmAction = null).catch(() => {})"
                    x-bind:class="modalText[confirmAction]?.danger ? 'bg-red-600 text-white hover:bg-red-700' : 'bg-accent text-navy-dark hover:bg-accent-dark'"
                    class="inline-flex items-center gap-1 rounded px-5 py-2 text-sm font-bold transition-colors">
                    <span x-text="modalText[confirmAction]?.label"></span>
                </button>
            </div>
        </div>
    </div>
</div>
