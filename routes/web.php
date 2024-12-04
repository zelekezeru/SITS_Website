<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\TestimonialController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\HomeController;

Route::get('/', function () {
    return view('index');
});


//Hotel
Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::get('/about', [HomeController::class, 'index'])->name('abouts.index');

Route::get('/elements', [HomeController::class, 'index'])->name('elements.index');

Route::resource('blogs', BlogController::class);

Route::resource('contacts', ContactController::class);

Route::resource('testimonials', TestimonialController::class);

Route::resource('courses', CourseController::class);