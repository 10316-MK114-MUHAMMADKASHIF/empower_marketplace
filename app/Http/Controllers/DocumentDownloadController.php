<?php

namespace App\Http\Controllers;

use App\Enums\DocumentStatus;
use App\Models\GeneratedDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentDownloadController extends Controller
{
    public function show(Request $request, GeneratedDocument $document): StreamedResponse
    {
        if ($document->order->user_id !== $request->user()->id) {
            abort(403);
        }

        if ($document->status !== DocumentStatus::Completed) {
            abort(404, 'Document not yet available.');
        }

        $format = $request->query('format', 'pdf');

        if ($format === 'docx' && $document->docx_storage_path) {
            $storagePath = $document->docx_storage_path;
            $filename = $document->document_type->value.'.docx';
            $mime = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        } else {
            $storagePath = $document->pdf_storage_path;
            $filename = $document->document_type->value.'.pdf';
            $mime = 'application/pdf';
        }

        if (! $storagePath || ! Storage::disk('local')->exists($storagePath)) {
            abort(404);
        }

        return Storage::disk('local')->download($storagePath, $filename, ['Content-Type' => $mime]);
    }
}
