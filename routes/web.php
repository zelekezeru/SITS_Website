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
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\MoodleController;
use App\Http\Controllers\Auth\OidcUserInfoController;

// ── OIDC userinfo for Moodle SSO ──────────────────────────────────────────────
// Moodle's auth_oauth2 plugin calls this with the Bearer token from /oauth/token
// to fetch the signed-in SITS user (email/name/roles) and provision/match locally.
Route::middleware('auth:api')->get('/oauth/userinfo', OidcUserInfoController::class)->name('oauth.userinfo');

// ─── Public Routes ───────────────────────────────────────────────────────────
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/home', [HomeController::class, 'index']);
Route::get('/about', [HomeController::class, 'about'])->name('abouts.index');
Route::get('/elements', [HomeController::class, 'elements'])->name('elements.index');



// ─── Authenticated User Routes ────────────────────────────────────────────────
// The unified portal hub (launch-pad for ERP / LMS / Library). The ERP owns the
// /dashboard route (employee self-service), so the website hub lives at /portal.
Route::middleware(['auth'])->group(function () {
    Route::get('/portal', [HomeController::class, 'dashboard'])->name('portal');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/upload-profile-image', [ProfileController::class, 'uploadProfileImage'])->name('profile.uploadProfileImage');

    // ── Moodle SSO Redirect ──────────────────────────────────────────────────
    // Authenticated users are redirected to Moodle with auto-login (SSO).
    Route::get('/go/lms', [MoodleController::class, 'redirect'])->name('lms.redirect');

    // ── Library Portal (subscription-gated, handled inside controller) ───────
    Route::get('/library/portal',    [LibraryController::class, 'portal'])->name('library.portal');
    Route::post('/library/subscribe', [LibraryController::class, 'subscribe'])->name('library.subscribe');
});

// ── Library Plans (public — anyone can view plans) ────────────────────────────
Route::get('/library/plans', [LibraryController::class, 'plans'])->name('library.plans');

// ─── Admin-Only Routes ────────────────────────────────────────────────────────
// These routes are protected by both authentication and role-based access.
// Only users with the SUPERADMIN, ADMIN, or EDITOR role may access them.
Route::middleware(['auth', 'role:SUPERADMIN|ADMIN|EDITOR'])->group(function () {

    Route::get('/website/admin', [HomeController::class, 'websiteAdminDashboard'])->name('website.admin.dashboard');

    // CKFinder / rich-text upload
    Route::post('/ckfinder-upload', [BlogController::class, 'upload'])->name('ckeditor.blog.upload');

    // ── List/Index views (admin table pages)
    Route::get('blogs/list',      [BlogController::class,     'list'])->name('blogs.list');
    Route::get('courses/list',    [CourseController::class,   'list'])->name('courses.list');
    Route::get('programs/list',   [ProgramController::class,  'list'])->name('programs.list');
    Route::get('events/list',     [EventController::class,    'list'])->name('events.list');
    Route::get('users/list',      [UserController::class,     'list'])->name('users.list');
    Route::get('trainers/list',   [TrainerController::class,  'list'])->name('trainers.list');
    Route::get('contacts/list',   [ContactController::class,  'list'])->name('contacts.list');
    Route::get('libraries/list',  [LibraryController::class,  'list'])->name('libraries.list');
    Route::get('galleries/list',  [GalleryController::class,  'list'])->name('galleries.list');

    // ── Library Subscription Management (Admin) ──────────────────────────────
    Route::get('library/subscriptions', [LibraryController::class, 'subscriptions'])->name('library.subscriptions');
    Route::post('library/subscriptions/{subscription}/activate', [LibraryController::class, 'activateSubscription'])->name('library.subscriptions.activate');

    // ── Full CRUD Resource routes (create / edit / delete / update)
    Route::resource('users',         UserController::class);
    Route::resource('blogs',         BlogController::class)->except(['index', 'show']);
    Route::resource('contacts',      ContactController::class)->except(['index', 'store']);
    Route::resource('testimonials',  TestimonialController::class);
    Route::resource('courses',       CourseController::class)->except(['index', 'show']);
    Route::resource('libraries',     LibraryController::class)->except(['index', 'show']);
    Route::resource('admins',        AdminController::class);
    Route::resource('programs',      ProgramController::class);
    Route::resource('events',        EventController::class);
    Route::resource('trainers',      TrainerController::class);
    Route::resource('subscriptions', SubscriptionController::class);
    Route::resource('galleries',     GalleryController::class);
});

// ─── Public Resource Routes (placed after admin list views to avoid interception) ──
Route::resource('courses',       CourseController::class)->only(['index', 'show']);
Route::resource('blogs',         BlogController::class)->only(['index', 'show']);
Route::resource('libraries',     LibraryController::class)->only(['index', 'show']);
Route::resource('contacts',      ContactController::class)->only(['index', 'store']);


// ─── Hikvision Webhook Real-time Attendance Integration ───────────────────────
Route::post('/hikvision/webhook', [\App\Http\Controllers\Admin\HikvisionWebhookController::class, 'handle'])->name('hikvision.webhook');

require __DIR__ . '/auth.php';

// ─── SITS ERP (Performance Management System) ─────────────────────────────────
// Inertia/Vue back-office, merged from the standalone PMS app.
require __DIR__ . '/erp.php';

// ─── SITS Library (Integrated Library System) ─────────────────────────────────
// Inertia/Vue ILS, merged from the standalone sits-library. Mounted under /library.
require __DIR__ . '/library.php';
