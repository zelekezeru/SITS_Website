<?php

namespace App\Http\Controllers;

use App\Models\Library;
use App\Models\LibrarySubscription;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\LibraryStoreRequest;
use App\Http\Requests\LibraryUpdateRequest;
use Inertia\Inertia;

class LibraryController extends Controller
{
    // ── Public-facing library pages ───────────────────────────────────────────

    /**
     * Public listing of available library resources.
     */
    public function index()
    {
        $libraries = Library::select(['id', 'title', 'banner', 'category', 'description', 'link'])
            ->latest()
            ->paginate(12);
        return Inertia::render('Website/Libraries/Index', ['libraries' => $libraries]);
    }

    /**
     * Show subscription plans (the JSTORE-equivalent page).
     * All users (authenticated or not) can view plans.
     * Authenticated users see their current subscription status.
     */
    public function plans(): View
    {
        $plans = [
            [
                'key'         => 'monthly',
                'name'        => 'Monthly Access',
                'price'       => 150,
                'duration'    => '30 days',
                'colour'      => 'indigo',
                'features'    => [
                    'Full access to all digital books',
                    'Download study materials',
                    'Access to audio/video lectures',
                    'Priority support',
                ],
            ],
            [
                'key'         => 'annual',
                'name'        => 'Annual Access',
                'price'       => 1200,
                'duration'    => '365 days',
                'colour'      => 'violet',
                'popular'     => true,
                'features'    => [
                    'Everything in Monthly',
                    '33% savings vs monthly',
                    'Early access to new materials',
                    'Dedicated librarian support',
                ],
            ],
            [
                'key'         => 'lifetime',
                'name'        => 'Lifetime Access',
                'price'       => 4500,
                'duration'    => 'Lifetime',
                'colour'      => 'amber',
                'features'    => [
                    'Everything in Annual',
                    'One-time payment — never expires',
                    'Access to all future materials',
                    'SITS Alumni badge',
                ],
            ],
        ];

        $activeSubscription = null;
        if (Auth::check()) {
            $activeSubscription = LibrarySubscription::where('user_id', Auth::id())
                ->active()
                ->latest()
                ->first();
        }

        return view('library.plans', compact('plans', 'activeSubscription'));
    }

    /**
     * The subscriber-only digital library portal.
     * Shows all available books and resources.
     */
    public function portal()
    {
        $user = Auth::user();

        $subscription = LibrarySubscription::where('user_id', $user->id)
            ->active()
            ->latest()
            ->first();

        // Admin/Superadmin/Librarian bypass subscription check
        $hasBypass = $user->hasAnyRole(['SUPERADMIN', 'ADMIN', 'LIBRARIAN']);

        // If no active subscription and no bypass, redirect to plans
        if (! $subscription && ! $hasBypass) {
            return redirect()->route('library.plans')
                ->with('info', 'You need an active subscription to access the library portal.');
        }

        $libraries = Library::latest()->paginate(20);

        return view('library.portal', compact('libraries', 'subscription'));
    }

    /**
     * Handle subscription request (admin-confirmed for now — no payment gateway yet).
     * Records the subscription request and notifies admin.
     */
    public function subscribe(Request $request): RedirectResponse
    {
        $request->validate([
            'plan_type'         => 'required|in:monthly,annual,lifetime',
            'payment_reference' => 'required|string|max:100',
            'payment_method'    => 'required|string|max:50',
        ]);

        $plans = [
            'monthly'  => ['name' => 'Monthly Access',  'price' => 150,  'days' => 30],
            'annual'   => ['name' => 'Annual Access',   'price' => 1200, 'days' => 365],
            'lifetime' => ['name' => 'Lifetime Access', 'price' => 4500, 'days' => null],
        ];

        $plan = $plans[$request->plan_type];

        LibrarySubscription::create([
            'user_id'           => Auth::id(),
            'plan_name'         => $plan['name'],
            'plan_type'         => $request->plan_type,
            'amount_paid'       => $plan['price'],
            'start_date'        => now()->toDateString(),
            'expiry_date'       => $plan['days']
                                        ? now()->addDays($plan['days'])->toDateString()
                                        : null,
            'is_active'         => false,    // Admin must confirm payment first
            'payment_reference' => $request->payment_reference,
            'payment_method'    => $request->payment_method,
        ]);

        return redirect()->route('library.plans')
            ->with('success', 'Your subscription request has been submitted. Our team will verify your payment and activate your access within 24 hours.');
    }

    // ── Admin CRUD ────────────────────────────────────────────────────────────

    /**
     * Admin list view for library items.
     */
    public function list(): View
    {
        $libraries = Library::paginate(15);
        return view('libraries.list', compact('libraries'));
    }

    /**
     * Show the form for creating a new library resource.
     */
    public function create(): View
    {
        $library = new Library;
        return view('libraries.create', compact('library'));
    }

    /**
     * Store a newly created library resource.
     */
    public function store(LibraryStoreRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('banner')) {
            $data['banner'] = $request->file('banner')->store('libraryBanners', 'public');
        }

        if ($request->hasFile('file')) {
            $data['file'] = $request->file('file')->store('files', 'public');
        }

        Library::create($data);

        return redirect()->route('libraries.list')
            ->with('status', 'Library resource created successfully.');
    }

    /**
     * Display a single library resource.
     */
    public function show(Library $library): View
    {
        return view('libraries.show', compact('library'));
    }

    /**
     * Show the edit form for a library resource.
     */
    public function edit(Library $library): View
    {
        return view('libraries.edit', compact('library'));
    }

    /**
     * Update an existing library resource.
     */
    public function update(LibraryUpdateRequest $request, Library $library): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('banner')) {
            $data['banner'] = $request->file('banner')->store('libraryBanners', 'public');
        }

        if ($request->hasFile('file')) {
            $data['file'] = $request->file('file')->store('files', 'public');
        }

        $library->update($data);

        return redirect()->route('libraries.list')
            ->with('status', 'Library resource updated successfully.');
    }

    /**
     * Delete a library resource.
     */
    public function destroy(Library $library): RedirectResponse
    {
        $library->delete();

        return redirect()->route('libraries.list')
            ->with('status', 'Library resource deleted successfully.');
    }

    // ── Admin: Subscription Management ───────────────────────────────────────

    /**
     * Admin list of pending/active subscriptions.
     */
    public function subscriptions(): View
    {
        $subscriptions = LibrarySubscription::with('user')->latest()->paginate(20);
        return view('library.subscriptions', compact('subscriptions'));
    }

    /**
     * Admin: Activate a pending subscription after verifying payment.
     */
    public function activateSubscription(LibrarySubscription $subscription): RedirectResponse
    {
        $subscription->update(['is_active' => true]);

        return back()->with('status', "Subscription for {$subscription->user->name} activated.");
    }
}
