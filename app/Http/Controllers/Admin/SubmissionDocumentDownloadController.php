<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IntakeSubmission;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmissionDocumentDownloadController extends Controller
{
    public function show(IntakeSubmission $submission, int $index): StreamedResponse
    {
        $documents = $submission->admin_documents ?? [];
        $document = $documents[$index] ?? null;

        if (! is_array($document)) {
            abort(404);
        }

        $storagePath = $document['storage_path'] ?? null;

        if (! is_string($storagePath) || ! Storage::disk('local')->exists($storagePath)) {
            abort(404);
        }

        $filename = $document['original_filename'] ?? basename($storagePath);
        $mimeType = $document['mime_type'] ?? null;
        $headers = is_string($mimeType) ? ['Content-Type' => $mimeType] : [];

        return Storage::disk('local')->download($storagePath, $filename, $headers);
    }
}
