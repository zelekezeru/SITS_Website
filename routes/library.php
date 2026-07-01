<?php

use App\Http\Controllers\Library\AdminExternalResourceController;
use App\Http\Controllers\Library\AuditController;
use App\Http\Controllers\Library\BookController;
use App\Http\Controllers\Library\BookCopyController;
use App\Http\Controllers\Library\BookReviewController;
use App\Http\Controllers\Library\CampusController;
use App\Http\Controllers\Library\CatalogController;
use App\Http\Controllers\Library\CirculationController;
use App\Http\Controllers\Library\DashboardController;
use App\Http\Controllers\Library\ExternalResourceController;
use App\Http\Controllers\Library\FineController;
use App\Http\Controllers\Library\FloorController;
use App\Http\Controllers\Library\HoldController;
use App\Http\Controllers\Library\IsbnLookupController;
use App\Http\Controllers\Library\LanguageController;
use App\Http\Controllers\Library\Legacy\LegacyImportController;
use App\Http\Controllers\Library\MyFinesController;
use App\Http\Controllers\Library\MyHoldsController;
use App\Http\Controllers\Library\MyLoansController;
use App\Http\Controllers\Library\NotificationController;
use App\Http\Controllers\Library\PatronController;
use App\Http\Controllers\Library\PaymentController;
use App\Http\Controllers\Library\ReportController;
use App\Http\Controllers\Library\RowController;
use App\Http\Controllers\Library\ScanController;
use App\Http\Controllers\Library\SecureDocumentController;
use App\Http\Controllers\Library\ShelfBoxController;
use App\Http\Controllers\Library\StocktakeController;
use App\Http\Controllers\Library\TransferController;
use App\Http\Controllers\Library\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|------------------------------------------------------------------------------
| SITS Library (ILS) routes — merged from the standalone sits-library.
|------------------------------------------------------------------------------
| Everything is mounted under the "/library" path prefix and the "library."
| route-name prefix so nothing collides with the ERP/website routes. Access is
| gated by the shared spatie permissions (seeded by RolesAndPermissionsSeeder).
| The library is INTERNAL now (shared auth/session/DB), so the old cross-app
| HMAC SSO and Breeze auth routes are intentionally dropped.
*/

// Payment gateway callback (server-to-server + browser return) — outside auth.
Route::match(['get', 'post'], '/library/payments/callback', [PaymentController::class, 'callback'])
    ->name('library.payments.callback');

Route::middleware('auth')->prefix('library')->name('library.')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Language toggle (library UI). SITS also has /locale; kept for the library's switcher.
    Route::post('/language', [LanguageController::class, 'switch'])->name('language.switch');

    // ── Notifications ──────────────────────────────────────────────────────
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

    // ── Catalog & Books ────────────────────────────────────────────────────
    Route::middleware('permission:view_books')->group(function () {
        Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');
        Route::get('/catalog/{book}', [CatalogController::class, 'show'])->name('catalog.show');
        Route::get('/copies/labels/print', [BookCopyController::class, 'printLabels'])->name('copies.labels');
        Route::get('/copies/{copy}/qr', [BookCopyController::class, 'qr'])->name('copies.qr');
        Route::get('/books', [BookController::class, 'index'])->name('books.index');
        Route::post('/books/{book}/reviews', [BookReviewController::class, 'store'])->name('reviews.store');
        Route::delete('/reviews/{review}', [BookReviewController::class, 'destroy'])->name('reviews.destroy');
    });

    Route::middleware('permission:create_book')->group(function () {
        Route::resource('books', BookController::class)->except(['show', 'index']);
        Route::post('/books/{book}/copies', [BookCopyController::class, 'store'])->name('books.copies.store');
        Route::get('/scan/place', fn () => Inertia::render('Library/Scan/Place'))->name('scan.place');
        Route::post('/scan/resolve', [ScanController::class, 'resolve']);
        Route::post('/scan/place', [ScanController::class, 'place']);
        Route::post('/scan/bulk-place', [ScanController::class, 'bulkPlace']);
        Route::post('/isbn/lookup', IsbnLookupController::class)->name('isbn.lookup');
    });

    Route::middleware('permission:edit_book')->group(function () {
        Route::patch('/copies/{copy}/withdraw', [BookCopyController::class, 'withdraw'])->name('copies.withdraw');
        Route::patch('/copies/{copy}/mark-lost', [BookCopyController::class, 'markLost'])->name('copies.mark-lost');
        Route::post('/reviews/{review}/toggle', [BookReviewController::class, 'toggleApproval'])->name('reviews.toggle');
    });

    // ── Circulation ────────────────────────────────────────────────────────
    Route::middleware('permission:checkout_book')->group(function () {
        Route::get('/circulation/desk', [CirculationController::class, 'desk'])->name('circulation.desk');
        Route::get('/circulation/lookup', [CirculationController::class, 'lookupUser'])->name('circulation.lookup');
        Route::post('/circulation/checkout', [CirculationController::class, 'checkout'])->name('circulation.checkout');
        Route::post('/loans/{loan}/renew', [CirculationController::class, 'renew'])->name('loans.renew');
    });
    Route::middleware('permission:return_book')->group(function () {
        Route::get('/circulation/returns', [CirculationController::class, 'returns'])->name('circulation.returns');
        Route::post('/circulation/return', [CirculationController::class, 'return'])->name('circulation.return');
    });

    // ── Self-service ───────────────────────────────────────────────────────
    Route::get('/my/loans', [MyLoansController::class, 'index'])->name('my.loans');
    Route::post('/my/loans/{loan}/renew', [MyLoansController::class, 'renew'])->name('my.loans.renew');
    Route::get('/my/holds', [MyHoldsController::class, 'index'])->name('my.holds');
    Route::post('/holds', [HoldController::class, 'store'])->name('holds.store');
    Route::delete('/holds/{hold}', [HoldController::class, 'cancel'])->name('holds.cancel');

    // ── Fines & payments ───────────────────────────────────────────────────
    Route::get('/my/fines', [MyFinesController::class, 'index'])->name('my.fines');
    Route::get('/my/fines/{fine}/pay', [PaymentController::class, 'pay'])->name('payments.pay');
    Route::middleware('permission:collect_fine')->group(function () {
        Route::get('/fines', [FineController::class, 'index'])->name('fines.index');
        Route::post('/fines', [FineController::class, 'store'])->name('fines.store');
        Route::post('/fines/{fine}/collect', [FineController::class, 'collect'])->name('fines.collect');
        Route::post('/fines/{fine}/waive', [FineController::class, 'waive'])->name('fines.waive');
    });

    // ── Transfers ──────────────────────────────────────────────────────────
    Route::middleware('permission:approve_transfer')->group(function () {
        Route::patch('/transfers/{transfer}/approve', [TransferController::class, 'approve'])->name('transfers.approve');
        Route::patch('/transfers/{transfer}/reject', [TransferController::class, 'reject'])->name('transfers.reject');
        Route::patch('/transfers/{transfer}/dispatch', [TransferController::class, 'dispatch'])->name('transfers.dispatch');
        Route::patch('/transfers/{transfer}/return', [TransferController::class, 'returnToOrigin'])->name('transfers.return');
        Route::patch('/transfers/{transfer}/lost', [TransferController::class, 'markLost'])->name('transfers.lost');
    });
    Route::middleware('permission:request_transfer')->group(function () {
        Route::get('/transfers/create', [TransferController::class, 'create'])->name('transfers.create');
        Route::post('/transfers', [TransferController::class, 'store'])->name('transfers.store');
    });
    Route::middleware('permission:receive_transfer')->group(function () {
        Route::patch('/transfers/{transfer}/receive', [TransferController::class, 'receive'])->name('transfers.receive');
    });
    Route::get('/transfers', [TransferController::class, 'index'])->name('transfers.index');
    Route::get('/transfers/{transfer}', [TransferController::class, 'show'])->name('transfers.show');

    // ── Secure Digital Archive ─────────────────────────────────────────────
    Route::middleware('permission:view_secure_pdf')->group(function () {
        Route::get('/archive', [SecureDocumentController::class, 'index'])->name('archive.index');
        Route::get('/archive/create', [SecureDocumentController::class, 'create'])->name('archive.create')->middleware('permission:upload_secure_pdf');
        Route::get('/archive/{document}', [SecureDocumentController::class, 'show'])->name('archive.show');
        Route::get('/archive/{document}/stream', [SecureDocumentController::class, 'stream'])->name('archive.stream');
        Route::get('/archive/{document}/qr', [SecureDocumentController::class, 'qr'])->name('archive.qr');
        Route::post('/archive/{document}/heartbeat', [SecureDocumentController::class, 'heartbeat'])->name('archive.heartbeat');
    });
    Route::middleware('permission:upload_secure_pdf')->group(function () {
        Route::post('/archive', [SecureDocumentController::class, 'store'])->name('archive.store');
        Route::delete('/archive/{document}', [SecureDocumentController::class, 'destroy'])->name('archive.destroy');
    });

    // ── External Resources ─────────────────────────────────────────────────
    Route::get('/resources', [ExternalResourceController::class, 'index'])->name('resources.index');
    Route::get('/resources/{resource}/go', [ExternalResourceController::class, 'go'])->name('resources.go');
    Route::get('/resources/{resource}/qr', [ExternalResourceController::class, 'qr'])->name('resources.qr');
    Route::middleware('permission:manage_external_links')->group(function () {
        Route::resource('admin/resources', AdminExternalResourceController::class)->names('admin.resources')->except(['show']);
    });
    Route::get('/admin/audit', [AuditController::class, 'index'])->name('admin.audit')->middleware('permission:manage_campus');

    // ── Spatial (Campus → Floor → Row → Shelf-box) ─────────────────────────
    Route::middleware('permission:manage_campus')->group(function () {
        Route::resource('campuses', CampusController::class);
    });
    Route::middleware('permission:manage_floor')->group(function () {
        Route::resource('campuses.floors', FloorController::class)->shallow();
    });
    Route::middleware('permission:manage_row')->group(function () {
        Route::resource('floors.rows', RowController::class)->shallow();
    });
    Route::middleware('permission:manage_shelf_box')->group(function () {
        Route::resource('rows.shelf-boxes', ShelfBoxController::class)->shallow();
        Route::get('/shelf-boxes/{shelfBox}/qr', [ShelfBoxController::class, 'qr'])->name('shelf-boxes.qr');
    });

    // ── Users & patrons ────────────────────────────────────────────────────
    Route::middleware('permission:manage_users')->group(function () {
        Route::resource('users', UserController::class);
    });
    Route::get('/patrons/{user}', [PatronController::class, 'show'])->name('patrons.show');

    // ── Stocktake / Inventory ──────────────────────────────────────────────
    Route::middleware('permission:manage_shelf_box')->group(function () {
        Route::get('/stocktakes', [StocktakeController::class, 'index'])->name('stocktakes.index');
        Route::post('/stocktakes', [StocktakeController::class, 'store'])->name('stocktakes.store');
        Route::get('/stocktakes/{stocktake}', [StocktakeController::class, 'show'])->name('stocktakes.show');
        Route::post('/stocktakes/{stocktake}/scan', [StocktakeController::class, 'scan'])->name('stocktakes.scan');
        Route::post('/stocktakes/{stocktake}/complete', [StocktakeController::class, 'complete'])->name('stocktakes.complete');
        Route::post('/stocktakes/{stocktake}/cancel', [StocktakeController::class, 'cancel'])->name('stocktakes.cancel');
    });

    // ── Reports ────────────────────────────────────────────────────────────
    Route::middleware('permission:view_loans')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::post('/reports/generate', [ReportController::class, 'generate'])->name('reports.generate');
        Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
    });

    // ── Legacy import ──────────────────────────────────────────────────────
    Route::middleware('permission:manage_legacy_data')->group(function () {
        Route::get('/admin/legacy', [LegacyImportController::class, 'index'])->name('admin.legacy.index');
        Route::get('/admin/legacy/export', [LegacyImportController::class, 'export'])->name('admin.legacy.export');
        Route::post('/admin/legacy/import', [LegacyImportController::class, 'store'])->name('admin.legacy.import');
    });
});
