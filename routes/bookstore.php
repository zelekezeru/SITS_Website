<?php

use App\Http\Controllers\Bookstore\BookDispatchController;
use App\Http\Controllers\Bookstore\BookPaymentController;
use App\Http\Controllers\Bookstore\BookRequestController;
use App\Http\Controllers\Bookstore\BookReturnController;
use App\Http\Controllers\Bookstore\BookTitleController;
use App\Http\Controllers\Bookstore\CenterController;
use App\Http\Controllers\Bookstore\DashboardController;
use App\Http\Controllers\Bookstore\LabelController;
use App\Http\Controllers\Bookstore\PaymentBypassController;
use App\Http\Controllers\Bookstore\PipelineController;
use App\Http\Controllers\Bookstore\PrintRunController;
use App\Http\Controllers\Bookstore\ReportController;
use App\Http\Controllers\Bookstore\ScanController;
use App\Http\Controllers\Bookstore\ShelfController;
use App\Http\Controllers\Bookstore\ShelfSectionController;
use App\Http\Controllers\Bookstore\StockAuditController;
use App\Http\Controllers\Bookstore\StockController;
use App\Http\Controllers\Bookstore\StoreRoomController;
use App\Http\Controllers\Bookstore\StudyModeController;
use Illuminate\Support\Facades\Route;

/*
|------------------------------------------------------------------------------
| Bookstore — printed course-book inventory and distribution
|------------------------------------------------------------------------------
| Mounted under "/bookstore" with the "bookstore." name prefix. Separate from
| the Library ILS routes: the ILS lends individually accessioned copies, this
| module moves bulk-printed stock. See docs/books-inventory-system.md.
|
| Permissions are the segregated set seeded by BookstorePermissionsSeeder — in
| particular verify_book_request, verify_book_payment and approve_book_request
| are three different grants, so no single account can walk a request from
| submission to dispatch.
*/

Route::middleware(['auth', 'permission:view_bookstore'])
    ->prefix('bookstore')
    ->name('bookstore.')
    ->group(function () {

        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // The shared board. Open to every bookstore viewer on purpose: layered
        // approval only works if stakeholders can watch the queue move.
        Route::get('/pipeline', [PipelineController::class, 'index'])->name('pipeline');

        // ── Scanning ───────────────────────────────────────────────────────
        Route::get('/scan', [ScanController::class, 'index'])->name('scan.index');
        Route::post('/scan/lookup', [ScanController::class, 'lookup'])->name('scan.lookup');
        Route::get('/scan/{hash}', [ScanController::class, 'resolve'])->name('scan.resolve');

        // ── QR labels ──────────────────────────────────────────────────────
        Route::get('/labels/sheet', [LabelController::class, 'sheet'])->name('labels.sheet');
        Route::get('/labels/{type}/{id}/png', [LabelController::class, 'png'])->name('labels.png');

        // ── Catalogue ──────────────────────────────────────────────────────
        Route::get('/titles', [BookTitleController::class, 'index'])->name('titles.index');
        Route::get('/titles/{title}', [BookTitleController::class, 'show'])->name('titles.show');

        Route::middleware('permission:manage_book_titles')->group(function () {
            Route::get('/titles/{title}/edit', [BookTitleController::class, 'edit'])->name('titles.edit');
            Route::post('/titles', [BookTitleController::class, 'store'])->name('titles.store');
            Route::put('/titles/{title}', [BookTitleController::class, 'update'])->name('titles.update');
            Route::delete('/titles/{title}', [BookTitleController::class, 'destroy'])->name('titles.destroy');
            Route::get('/titles-create', [BookTitleController::class, 'create'])->name('titles.create');

            Route::get('/study-modes', [StudyModeController::class, 'index'])->name('study-modes.index');
            Route::post('/study-modes', [StudyModeController::class, 'store'])->name('study-modes.store');
            Route::put('/study-modes/{studyMode}', [StudyModeController::class, 'update'])->name('study-modes.update');
            Route::delete('/study-modes/{studyMode}', [StudyModeController::class, 'destroy'])->name('study-modes.destroy');
        });

        // ── Store rooms → shelves → sections ───────────────────────────────
        Route::get('/stores', [StoreRoomController::class, 'index'])->name('stores.index');
        Route::get('/stores/{store}', [StoreRoomController::class, 'show'])->name('stores.show');
        Route::get('/sections/{section}', [ShelfSectionController::class, 'show'])->name('sections.show');

        Route::middleware('permission:manage_store_rooms')->group(function () {
            Route::post('/stores', [StoreRoomController::class, 'store'])->name('stores.store');
            Route::put('/stores/{store}', [StoreRoomController::class, 'update'])->name('stores.update');
            Route::delete('/stores/{store}', [StoreRoomController::class, 'destroy'])->name('stores.destroy');

            Route::post('/stores/{store}/shelves', [ShelfController::class, 'store'])->name('shelves.store');
            Route::put('/shelves/{shelf}', [ShelfController::class, 'update'])->name('shelves.update');
            Route::delete('/shelves/{shelf}', [ShelfController::class, 'destroy'])->name('shelves.destroy');

            Route::post('/shelves/{shelf}/sections', [ShelfSectionController::class, 'store'])->name('sections.store');
            Route::put('/sections/{section}', [ShelfSectionController::class, 'update'])->name('sections.update');
            Route::delete('/sections/{section}', [ShelfSectionController::class, 'destroy'])->name('sections.destroy');
        });

        // ── Stock ──────────────────────────────────────────────────────────
        Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
        Route::get('/stock/low', [StockController::class, 'lowStock'])->name('stock.low');
        Route::get('/stock/{title}/bin-card', [StockController::class, 'binCard'])->name('stock.bin-card');

        Route::middleware('permission:manage_book_stock')->group(function () {
            Route::post('/stock/transfer', [StockController::class, 'transfer'])->name('stock.transfer');
            Route::post('/stock/adjust', [StockController::class, 'adjust'])->name('stock.adjust');
        });

        // ── Print runs ─────────────────────────────────────────────────────
        Route::middleware('permission:manage_print_runs')->group(function () {
            Route::get('/print-runs', [PrintRunController::class, 'index'])->name('print-runs.index');
            Route::get('/print-runs/create', [PrintRunController::class, 'create'])->name('print-runs.create');
            Route::post('/print-runs', [PrintRunController::class, 'store'])->name('print-runs.store');
        });

        // ── Distribution centres ───────────────────────────────────────────
        Route::get('/centers', [CenterController::class, 'index'])->name('centers.index');
        Route::get('/centers/{center}', [CenterController::class, 'show'])->name('centers.show');

        Route::middleware('permission:manage_centers')->group(function () {
            Route::post('/centers', [CenterController::class, 'store'])->name('centers.store');
            Route::put('/centers/{center}', [CenterController::class, 'update'])->name('centers.update');
            Route::delete('/centers/{center}', [CenterController::class, 'destroy'])->name('centers.destroy');
        });

        // ── Requests: one predictable journey ──────────────────────────────
        Route::get('/requests', [BookRequestController::class, 'index'])->name('requests.index');
        Route::get('/requests/{bookRequest}', [BookRequestController::class, 'show'])->name('requests.show');

        Route::middleware('permission:request_books')->group(function () {
            Route::get('/requests-create', [BookRequestController::class, 'create'])->name('requests.create');
            Route::post('/requests', [BookRequestController::class, 'store'])->name('requests.store');
            Route::get('/requests/{bookRequest}/edit', [BookRequestController::class, 'edit'])->name('requests.edit');
            Route::put('/requests/{bookRequest}', [BookRequestController::class, 'update'])->name('requests.update');
            Route::post('/requests/{bookRequest}/submit', [BookRequestController::class, 'submit'])->name('requests.submit');
            Route::post('/requests/{bookRequest}/cancel', [BookRequestController::class, 'cancel'])->name('requests.cancel');
        });

        Route::post('/requests/{bookRequest}/verify', [BookRequestController::class, 'verify'])
            ->middleware('permission:verify_book_request')->name('requests.verify');

        Route::post('/requests/{bookRequest}/verify-payment', [BookRequestController::class, 'verifyPayment'])
            ->middleware('permission:verify_book_payment')->name('requests.verify-payment');

        Route::post('/requests/{bookRequest}/approve', [BookRequestController::class, 'approve'])
            ->middleware('permission:approve_book_request')->name('requests.approve');

        Route::post('/requests/{bookRequest}/reject', [BookRequestController::class, 'reject'])->name('requests.reject');
        Route::post('/requests/{bookRequest}/confirm', [BookRequestController::class, 'confirmReceipt'])->name('requests.confirm');

        // ── Payments ───────────────────────────────────────────────────────
        Route::post('/requests/{bookRequest}/payments', [BookPaymentController::class, 'store'])->name('payments.store');
        Route::get('/payments/{payment}/receipt', [BookPaymentController::class, 'receipt'])->name('payments.receipt');

        Route::middleware('permission:verify_book_payment')->group(function () {
            Route::get('/payments', [BookPaymentController::class, 'index'])->name('payments.index');
            Route::post('/payments/{payment}/verify', [BookPaymentController::class, 'verify'])->name('payments.verify');
            Route::post('/payments/{payment}/reject', [BookPaymentController::class, 'reject'])->name('payments.reject');
        });

        // ── Pay-later deferrals ────────────────────────────────────────────
        // Finance asks, a different grant authorises. The register is visible to
        // every viewer so an unpaid release cannot sit quietly.
        Route::get('/bypasses', [PaymentBypassController::class, 'index'])->name('bypasses.index');

        Route::post('/requests/{bookRequest}/bypass', [PaymentBypassController::class, 'store'])
            ->middleware('permission:request_payment_bypass')->name('bypasses.store');

        Route::post('/bypasses/{bypass}/settle', [PaymentBypassController::class, 'settle'])
            ->middleware('permission:verify_book_payment')->name('bypasses.settle');

        Route::middleware('permission:approve_payment_bypass')->group(function () {
            Route::post('/bypasses/{bypass}/approve', [PaymentBypassController::class, 'approve'])->name('bypasses.approve');
            Route::post('/bypasses/{bypass}/reject', [PaymentBypassController::class, 'reject'])->name('bypasses.reject');
        });

        // ── Dispatch ───────────────────────────────────────────────────────
        Route::get('/dispatches', [BookDispatchController::class, 'index'])->name('dispatches.index');
        Route::get('/dispatches/{dispatch}', [BookDispatchController::class, 'show'])->name('dispatches.show');
        Route::get('/dispatches/{dispatch}/print', [BookDispatchController::class, 'print'])->name('dispatches.print');
        Route::post('/dispatches/{dispatch}/confirm', [BookDispatchController::class, 'confirm'])->name('dispatches.confirm');

        Route::middleware('permission:dispatch_books')->group(function () {
            Route::get('/requests/{bookRequest}/dispatch', [BookDispatchController::class, 'create'])->name('dispatches.create');
            Route::post('/requests/{bookRequest}/dispatch', [BookDispatchController::class, 'store'])->name('dispatches.store');
        });

        // ── Returns ────────────────────────────────────────────────────────
        Route::get('/returns', [BookReturnController::class, 'index'])->name('returns.index');

        Route::middleware('permission:record_book_return')->group(function () {
            Route::get('/returns/create', [BookReturnController::class, 'create'])->name('returns.create');
            Route::post('/returns', [BookReturnController::class, 'store'])->name('returns.store');
        });

        // ── Stock audits ───────────────────────────────────────────────────
        Route::middleware('permission:conduct_stock_audit')->group(function () {
            Route::get('/audits', [StockAuditController::class, 'index'])->name('audits.index');
            Route::post('/audits', [StockAuditController::class, 'store'])->name('audits.store');
            Route::get('/audits/{audit}', [StockAuditController::class, 'show'])->name('audits.show');
            Route::post('/audits/lines/{line}/count', [StockAuditController::class, 'count'])->name('audits.count');
            Route::post('/audits/{audit}/lines', [StockAuditController::class, 'addLine'])->name('audits.add-line');
            Route::post('/audits/{audit}/complete', [StockAuditController::class, 'complete'])->name('audits.complete');
            Route::post('/audits/{audit}/cancel', [StockAuditController::class, 'cancel'])->name('audits.cancel');
        });

        Route::post('/audits/{audit}/approve', [StockAuditController::class, 'approve'])
            ->middleware('permission:approve_stock_audit')->name('audits.approve');

        // ── Reports ────────────────────────────────────────────────────────
        Route::middleware('permission:view_book_reports')->group(function () {
            Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
            Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
        });
    });
