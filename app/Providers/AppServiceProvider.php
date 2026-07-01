<?php

namespace App\Providers;

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
            return new AiServiceManager();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register Passport's built-in Blade authorization consent view so that
        // /oauth/authorize works when Moodle redirects users for SSO login.
        Passport::authorizationView('passport::authorize');

        // Store client secrets in plain text so external OAuth2 consumers like
        // Moodle can authenticate without bcrypt comparison issues.
        // Note: Passport v13 stores secrets as plain text by default.

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

        // ERP password-recovery email links point at the Inertia reset page.
        ResetPassword::createUrlUsing(function (User $user, string $token) {
            return config('app.url')."/reset-password/{$token}?email=".urlencode($user->email);
        });
    }
}
