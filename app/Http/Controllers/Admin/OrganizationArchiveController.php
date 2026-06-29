<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ArchiveResourceType;
use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Organization;
use App\Support\DocumentUploader;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Institution-wide archive: images, files of any type and web links that
 * don't belong to a specific employee or department. Always hangs off the
 * single Organization row — the documentable type/id are never taken from
 * client input.
 */
class OrganizationArchiveController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::enum(ArchiveResourceType::class)],
            'file_path' => ['nullable', 'string'],
            'file' => ['nullable', 'file', 'max:51200'],
        ]);

        if (! $request->hasFile('file') && ! $request->filled('file_path')) {
            return redirect()->back()->withErrors(['file' => 'A file upload or web link is required.']);
        }

        $organization = Organization::firstOrFail();

        DocumentUploader::store(
            title: $data['name'],
            documentableType: Organization::class,
            documentableId: $organization->id,
            file: $request->file('file'),
            filePath: $data['file_path'] ?? null,
            uploadedBy: $request->user()->id,
            category: $data['category'],
        );

        return redirect()->back()->with('success', 'Resource added to the organization archive.');
    }

    public function destroy(Document $document)
    {
        abort_unless($document->documentable_type === Organization::class, 404);

        $document->delete();

        return redirect()->back()->with('success', 'Resource removed from the organization archive.');
    }
}
