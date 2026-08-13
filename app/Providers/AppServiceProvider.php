<?php

namespace App\Providers;

use App\Enums\Role;
use App\Models\ConductDecision;
use App\Models\ConductIssue;
use App\Models\Deliverable;
use App\Models\Evaluation;
use App\Models\JobDescriptionVersion;
use App\Models\LeaveRequest;
use App\Models\Payslip;
use App\Models\Target;
use App\Models\Task;
use App\Models\Termination;
use App\Models\User;
use App\Services\Ai\AiServiceManager;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // ERP AI Service Manager (narrative + performance analysis).
        $this->app->singleton(AiServiceManager::class, function () {
            return new AiServiceManager;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register a local Blade authorization consent view for the OAuth2/SSO flow.
        // Uses resources/views/oauth/authorize.blade.php (no vendor:publish needed).
        Passport::authorizationView('oauth.authorize');

        // Our own LMS skips that consent screen — see App\Models\OAuthClient.
        Passport::useClientModel(\App\Models\OAuthClient::class);

        // Force a default/recovery password to be retired before SSO can hand
        // the user on to Moodle. /go/lms carries this in its route definition,
        // but Moodle's "Log in with SITS" button enters at /oauth/authorize,
        // which Passport registers itself — so the middleware is attached to
        // that route here rather than by re-declaring Passport's routes.
        $this->app->booted(function () {
            $route = \Illuminate\Support\Facades\Route::getRoutes()
                ->getByName('passport.authorizations.authorize');

            $route?->middleware('password.fresh');
        });

        // Store client secrets in plain text so external OAuth2 consumers like
        // Moodle can authenticate without bcrypt comparison issues.
        // Note: Passport v13 stores secrets as plain text by default.

        // Define OAuth2 / OIDC scopes that external clients (Moodle) may request.
        Passport::tokensCan([
            'openid' => 'OpenID Connect identity',
            'profile' => 'Read your basic profile information (name)',
            'email' => 'Read your email address',
        ]);

        // Stable morph aliases so polymorphic types survive class renames and
        // keep the DB readable (kpiable / commentable / documentable).
        Relation::morphMap([
            'job_description_version' => JobDescriptionVersion::class,
            'target' => Target::class,
            'task' => Task::class,
            'deliverable' => Deliverable::class,
            'evaluation' => Evaluation::class,
            'payslip' => Payslip::class,
            'conduct_issue' => ConductIssue::class,
            'conduct_decision' => ConductDecision::class,
            'leave_request' => LeaveRequest::class,
            'termination' => Termination::class,
        ]);

        // The President / Super Admin can do everything; every other ability is
        // granted explicitly through policies + spatie permissions. The website
        // SUPERADMIN role is handled by the role: middleware on website routes.
        Gate::before(function (User $user, string $ability) {
            return $user->hasRole('President / Super Admin') ? true : null;
        });

        // Academic Integrity Suite: Instructor + Admin only. Uses primaryRole()
        // (not a raw Spatie role-name check) since this codebase's unified RBAC
        // maps Spatie role names to this enum differently per legacy scheme
        // (e.g. the Spatie role "TRAINER" is Role::INSTRUCTOR).
        Gate::define('access-integrity-suite', function (User $user) {
            return in_array($user->primaryRole(), [Role::INSTRUCTOR, Role::SUPER_ADMIN, Role::CAMPUS_ADMIN], true);
        });

        // ERP password-recovery email links point at the Inertia reset page.
        ResetPassword::createUrlUsing(function (User $user, string $token) {
            return config('app.url')."/reset-password/{$token}?email=".urlencode($user->email);
        });
    }
}
