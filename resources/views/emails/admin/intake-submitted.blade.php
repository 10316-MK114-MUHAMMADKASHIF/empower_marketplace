<x-mail::message :message="$message ?? null">
# Intake Documents Submitted

A client has submitted intake documents for review.

<x-mail::panel>
**Client:** {{ $submission->order?->user?->name }}<br>
**Email:** {{ $submission->order?->user?->email }}<br>
**Practice:** {{ $submission->order?->user?->practice?->name ?: 'Not yet named' }}<br>
**Package:** {{ $submission->order?->package?->name ?? 'Compliance Package' }}<br>
**Order #:** {{ $submission->order_id }}<br>
**Submitted At:** {{ $submission->submitted_at?->format('F j, Y g:i A') }}
</x-mail::panel>

<x-mail::button :url="route('admin.submissions.show', $submission)">
Review Submission
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
