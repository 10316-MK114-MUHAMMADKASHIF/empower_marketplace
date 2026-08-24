<x-mail::message>
# Your Documents Are Ready

The following compliance document{{ $documents->count() > 1 ? 's have' : ' has' }} been reviewed and approved, and{{ $documents->count() > 1 ? ' are' : ' is' }} now available in your portal:

<x-mail::panel>
@foreach($documents as $document)
&bull; {{ $document->document_type->label() }}{{ $document->oshaLocation ? ' — '.$document->oshaLocation->name : '' }}<br>
@endforeach
</x-mail::panel>

<x-mail::button :url="route('portal')">
View My Documents
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
