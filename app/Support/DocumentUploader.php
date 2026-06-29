<?php

namespace App\Support;

use App\Models\Document;
use Illuminate\Http\UploadedFile;

/**
 * Shared "upload a file or register a web link" logic for the document
 * archive (employee/department/organization). Mirrors the original inline
 * logic in Admin\AdminCrudController::storeDocument, which is left untouched.
 */
class DocumentUploader
{
    public static function store(
        string $title,
        string $documentableType,
        int $documentableId,
        ?UploadedFile $file,
        ?string $filePath,
        ?int $uploadedBy,
        ?string $category = null,
    ): Document {
        $path = $filePath;
        $mime = null;
        $size = null;

        if ($file) {
            $filename = time().'_'.str_replace(' ', '_', $file->getClientOriginalName());
            $file->move(public_path('uploads/docs'), $filename);
            $path = 'uploads/docs/'.$filename;
            $mime = $file->getClientMimeType();
            $size = $file->getSize();
        }

        return Document::create([
            'title' => $title,
            'path' => $path,
            'mime' => $mime,
            'size' => $size,
            'category' => $category,
            'documentable_type' => $documentableType,
            'documentable_id' => $documentableId,
            'uploaded_by' => $uploadedBy,
        ]);
    }
}
