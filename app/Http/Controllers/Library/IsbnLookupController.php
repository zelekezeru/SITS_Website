<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;

use App\Services\IsbnLookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IsbnLookupController extends Controller
{
    public function __invoke(Request $request, IsbnLookupService $service): JsonResponse
    {
        $request->validate([
            'isbn' => 'required|string|min:10|max:17',
        ]);

        $result = $service->lookup($request->isbn);

        if (! $result) {
            return response()->json([
                'success' => false,
                'message' => 'No book found for this ISBN.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $result,
        ]);
    }
}
