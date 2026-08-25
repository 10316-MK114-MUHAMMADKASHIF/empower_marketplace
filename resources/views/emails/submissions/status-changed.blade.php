@php($isApproved = $submission->status === \App\Enums\IntakeSubmissionStatus::Approved)
<x-mail::message :message="$message ?? null">
@if($isApproved)
# Your Intake Documents Have Been Approved

Good news — the intake documents you submitted for **{{ $submission->order?->package?->name ?? 'your compliance package' }}** have been reviewed and approved. We're now generating your compliance documents.
@else
# Action Needed on Your Intake Documents

We reviewed the intake documents you submitted for **{{ $submission->order?->package?->name ?? 'your compliance package' }}** and found a few things that need to be corrected before we can move forward.

<x-mail::panel>
{{ $submission->reviewer_notes }}
</x-mail::panel>

Please log in to your portal, make the corrections, and resubmit.
@endif

<x-mail::button :url="route('portal')">
Go to My Portal
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
