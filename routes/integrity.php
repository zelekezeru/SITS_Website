<?php

use App\Http\Controllers\Integrity\DashboardController;
use App\Http\Controllers\Integrity\DocumentController;
use App\Http\Controllers\Integrity\ExportController;
use App\Http\Controllers\Integrity\PlagiarismController;
use App\Http\Controllers\Integrity\ReviewController;
use App\Http\Controllers\Integrity\WritingToolController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'can:access-integrity-suite'])->prefix('integrity')->name('integrity.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/history', [DashboardController::class, 'history'])->name('history');

    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
    Route::post('/documents/{document}/reanalyze', [DocumentController::class, 'reanalyze'])->name('documents.reanalyze');
    Route::post('/documents/{document}/plagiarism', [PlagiarismController::class, 'run'])->name('documents.plagiarism');
    Route::post('/documents/{document}/tools/{type}', [WritingToolController::class, 'run'])->name('documents.tools.run');
    Route::get('/documents/{document}/export/pdf', [ExportController::class, 'pdf'])->name('documents.export.pdf');

    Route::patch('/reports/{report}/review', [ReviewController::class, 'update'])->name('reports.review');
});
