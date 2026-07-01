<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;

use App\Models\SecureDocument;
use App\Models\SecureDocumentView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Inertia\Inertia;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class SecureDocumentController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        return Inertia::render('Archive/Index', [
            'documents' => SecureDocument::with('book')->orderBy('created_at', 'desc')->get(),
            'can_upload' => $request->user()->can('upload_secure_pdf'),
        ]);
    }

    public function create()
    {
        $this->authorize('create', SecureDocument::class);

        return Inertia::render('Archive/Form', [
            'books' => \App\Models\Book::orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function show(SecureDocument $document, Request $request)
    {
        abort_unless($document->isAccessibleBy($request->user()), 403);

        return Inertia::render('Archive/Reader', [
            'secureDocument' => array_merge($document->toArray(), [
                'watermark_text' => sprintf('%s - %s', $request->user()->name, now()->format('Y-m-d H:i')),
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', SecureDocument::class);

        $request->validate([
            'title' => 'required|string|max:255',
            'pdf'   => 'required|file|mimes:pdf|max:51200', // 50 MB
            'book_id' => 'nullable|exists:books,id',
            'visibility' => 'in:role_gated,document_gated,public_authenticated',
        ]);

        $disk = 'archive';
        $relativePath = sprintf('books/%s/%s.pdf', now()->format('Y'), Str::uuid());
        
        Storage::disk($disk)->put($relativePath, file_get_contents($request->file('pdf')));
        
        $absPath = Storage::disk($disk)->path($relativePath);
        $sha = hash_file('sha256', $absPath);

        SecureDocument::create([
            'title'             => $request->title,
            'disk'              => $disk,
            'path'              => $relativePath,
            'original_filename' => $request->file('pdf')->getClientOriginalName(),
            'size_bytes'        => filesize($absPath),
            'mime'              => 'application/pdf',
            'sha256'            => $sha,
            'book_id'           => $request->book_id ?? null,
            'uploaded_by'       => $request->user()->id,
            'visibility'        => $request->visibility ?? 'role_gated',
        ]);

        return redirect()->route('archive.index')->with('success', 'Document uploaded successfully.');
    }

    public function destroy(SecureDocument $document)
    {
        $this->authorize('delete', $document);

        Storage::disk($document->disk)->delete($document->path);
        $document->delete();

        return back()->with('success', 'Document deleted successfully.');
    }

    public function stream(Request $request, SecureDocument $document)
    {
        abort_unless($document->isAccessibleBy($request->user()), 403);

        // log the view (one row per session-open)
        SecureDocumentView::create([
            'secure_document_id' => $document->id,
            'user_id'            => $request->user()->id,
            'ip'                 => $request->ip(),
            'user_agent'         => substr($request->userAgent() ?? '', 0, 500),
        ]);

        $absPath = Storage::disk($document->disk)->path($document->path);
        abort_unless(is_file($absPath), 404);

        return response()->stream(function () use ($absPath) {
            $stream = fopen($absPath, 'rb');
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type'           => 'application/pdf',
            'Content-Length'         => filesize($absPath),
            'Cache-Control'          => 'private, no-store, max-age=0',
            'Content-Disposition'    => 'inline',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy'        => 'no-referrer',
        ]);
    }

    public function heartbeat(Request $r, SecureDocument $document)
    {
        abort_unless($document->isAccessibleBy($r->user()), 403);

        SecureDocumentView::where('secure_document_id', $document->id)
            ->where('user_id', $r->user()->id)
            ->latest('opened_at')->first()
            ?->update([
                'last_seen_at' => now(),
                'pages_viewed' => DB::raw('GREATEST(pages_viewed, '.((int)$r->integer('page')).')'),
            ]);

        return response()->json(['status' => 'ok']);
    }

    public function qr(SecureDocument $document)
    {
        $url = route('archive.show', $document->id);
        $svg = QrCode::format('svg')
            ->size(300)
            ->errorCorrection('M')
            ->generate($url);

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'inline; filename="archive-'.$document->id.'.svg"',
        ]);
    }
}
