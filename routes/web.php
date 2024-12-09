<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\TestimonialController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LibraryController;


Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';


//Hotel
Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::get('/about', [HomeController::class, 'index'])->name('abouts.index');

Route::get('/elements', [HomeController::class, 'index'])->name('elements.index');

Route::resource('blogs', BlogController::class);

Route::resource('contacts', ContactController::class);

Route::resource('testimonials', TestimonialController::class);

Route::resource('courses', CourseController::class);

Route::resource('libraries', LibraryController::class);
