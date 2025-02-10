<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\TestimonialController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TrainerController;
use App\Http\Controllers\SubscriptionController;
use App\Models\Trainer;
use Spatie\Permission\Middleware\RoleMiddleware;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/dashboard', [HomeController::class, 'dashboard'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ADMIN ONLY ROUTES GO HERE
Route::middleware(['auth'])->group(function () {
    Route::get('blogs/list', [BlogController::class, 'list'])->name("blogs.list");
    Route::get('courses/list', [CourseController::class, 'list'])->name("courses.list");
    Route::get('programs/list', [ProgramController::class, 'list'])->name("programs.list");
    Route::get('events/list', [EventController::class, 'list'])->name("events.list");
    Route::get('users/list', [UserController::class, 'list'])->name("users.list");
    Route::get('trainers/list', [TrainerController::class, 'list'])->name("trainers.list");
    Route::get('contacts/list', [ContactController::class, 'list'])->name("contacts.list");
    Route::get('libraries/list', [LibraryController::class, 'list'])->name("libraries.list");

    Route::post('/ckfinder-upload', [BlogController::class, 'upload'])->name('ckeditor.blog.upload');
});

//Hotel
Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::get('/about', [HomeController::class, 'about'])->name('abouts.index');

Route::get('/elements', [HomeController::class, 'elements'])->name('elements.index');

// Resource routes

Route::resource('users', UserController::class);

Route::resource('blogs', BlogController::class);

Route::resource('contacts', ContactController::class);

Route::resource('testimonials', TestimonialController::class);

Route::resource('courses', CourseController::class);

Route::resource('libraries', LibraryController::class);

Route::resource('admins', AdminController::class);

Route::resource('programs', ProgramController::class);

Route::resource('events', EventController::class);

Route::resource('trainers', TrainerController::class);

Route::resource('subscriptions', SubscriptionController::class);


require __DIR__ . '/auth.php';
