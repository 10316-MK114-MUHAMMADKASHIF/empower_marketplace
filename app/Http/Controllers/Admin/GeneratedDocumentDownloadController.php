<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeneratedDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GeneratedDocumentDownloadController extends Controller
{
    public function show(Request $request, GeneratedDocument $document): StreamedResponse
    {
        if ($request->query('source') === 'custom') {
            abort_unless($document->hasCustomDocument(), 404);

            $storagePath = $document->custom_storage_path;
            $filename = $document->custom_original_filename ?? $document->document_type->value;
            $mime = Storage::disk('local')->mimeType($storagePath) ?: 'application/octet-stream';
        } else {
            $storagePath = $document->pdf_storage_path ?? $document->docx_storage_path;

            abort_unless($storagePath, 404);

            $isDocx = str_ends_with($storagePath, '.docx');
            $filename = $document->document_type->value.($isDocx ? '.docx' : '.pdf');
            $mime = $isDocx
                ? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                : 'application/pdf';
        }

        if (! Storage::disk('local')->exists($storagePath)) {
            abort(404);
        }

        return Storage::disk('local')->download($storagePath, $filename, ['Content-Type' => $mime]);
    }
}
