<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class AttachmentController extends Controller
{
    /**
     * Serve a private attachment file
     * Only authenticated users can download attachments
     */
    public function show(Request $request, $filename)
    {
        // Verify user is authenticated
        if (!auth()->check()) {
            abort(403, 'Unauthorized access to attachment');
        }

        $path = 'ticket_attachments/' . $filename;

        // Check if file exists
        if (!Storage::disk('local')->exists($path)) {
            Log::warning('Attachment file not found', [
                'filename' => $filename,
                'path' => $path,
                'user_id' => auth()->id()
            ]);
            abort(404, 'Attachment not found');
        }

        // Get file content and mime type
        $file = Storage::disk('local')->get($path);
        $mimeType = Storage::disk('local')->mimeType($path);

        Log::info('Attachment accessed', [
            'filename' => $filename,
            'user_id' => auth()->id(),
            'user_email' => auth()->user()->email,
            'size' => strlen($file)
        ]);

        // Return file response with proper headers
        return response($file, 200)
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', 'inline; filename="' . basename($filename) . '"');
    }
}

