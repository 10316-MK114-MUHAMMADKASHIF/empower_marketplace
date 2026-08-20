<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IntakeUpload;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IntakeUploadDownloadController extends Controller
{
    public function show(IntakeUpload $upload): StreamedResponse
    {
        if (! Storage::disk('local')->exists($upload->storage_path)) {
            abort(404);
        }

        return Storage::disk('local')->download(
            $upload->storage_path,
            $upload->original_filename,
            ['Content-Type' => $upload->mime_type]
        );
    }
}
