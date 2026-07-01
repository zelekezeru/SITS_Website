<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;

use App\Models\Fine;
use App\Services\Payments\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class MyFinesController extends Controller
{
    public function index(Request $request, PaymentService $payments)
    {
        $user = $request->user();

        $fines = Fine::where('user_id', $user->id)
            ->with(['loan.copy.book', 'payments'])
            ->orderByRaw("CASE WHEN status = 'open' THEN 0 ELSE 1 END")
            ->latest()
            ->paginate(15);

        $outstanding = (float) Fine::where('user_id', $user->id)
            ->outstanding()
            ->sum(DB::raw('amount - paid_amount'));

        return Inertia::render('Library/Fines/My', [
            'fines'           => $fines,
            'outstanding'     => round($outstanding, 2),
            'currency'        => config('library.currency_symbol'),
            'onlineAvailable' => $payments->isOnlineAvailable(),
        ]);
    }
}
