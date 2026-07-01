<?php

namespace App\Http\Controllers\Library\Legacy;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Jobs\LegacyImportJob;
use App\Exports\LibraryWorkbookExport;
use App\Exports\TemplatesExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class LegacyImportController extends Controller
{
    public function index()
    {
        $this->authorize('manage_legacy_data');
        
        return Inertia::render('Library/Admin/Legacy/Index', [
            'recentImports' => [], // You could pull from a separate activity log table
        ]);
    }

    public function export(Request $request)
    {
        $this->authorize('manage_legacy_data');
        
        $type = $request->query('type', 'current');
        $filename = $type === 'template' ? 'library_template.xlsx' : 'library_state.xlsx';

        if ($type === 'template') {
            return Excel::download(new TemplatesExport(), $filename);
        }

        return Excel::download(new LibraryWorkbookExport(), $filename);
    }

    public function store(Request $request)
    {
        $this->authorize('manage_legacy_data');

        $request->validate([
            'workbook' => 'required|file|mimes:xlsx,xls|max:51200',
            'sheet'    => 'required|in:Books,Copies,Users,All',
            'commit'   => 'sometimes|boolean',
        ]);

        $path = $request->file('workbook')->store('legacy-imports');
        
        LegacyImportJob::dispatch(
            Storage::path($path), 
            $request->input('sheet'), 
            $request->boolean('commit'), 
            $request->user()->id
        );

        return back()->with('success', 'Import queued — refresh in a moment to see results.');
    }
}
