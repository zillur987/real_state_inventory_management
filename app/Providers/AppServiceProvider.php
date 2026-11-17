<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /** Register application services. */
    public function register(): void
    {
        //
    }

    /** Bootstrap application services and PIMS workflow abilities. */
    public function boot(): void
    {
        // Workflow queue and read-only workflow details.
        Gate::define('properties.workflow.view', static fn (User $user): bool =>
            $user->is_active && $user->hasRole(
                UserRole::Admin,
                UserRole::Manager,
                UserRole::SalesAgent,
                UserRole::LegalOfficer,
            )
        );

        // Admin and Manager can submit registered properties for verification.
        Gate::define('properties.workflow.submit', static fn (User $user): bool =>
            $user->is_active && $user->hasRole(
                UserRole::Admin,
                UserRole::Manager,
            )
        );

        // Legal Officer performs the verification; Admin retains oversight access.
        Gate::define('properties.workflow.verify', static fn (User $user): bool =>
            $user->is_active && $user->hasRole(
                UserRole::Admin,
                UserRole::LegalOfficer,
            )
        );

        // Admin and Manager make the final inventory approval decision.
        Gate::define('properties.workflow.approve', static fn (User $user): bool =>
            $user->is_active && $user->hasRole(
                UserRole::Admin,
                UserRole::Manager,
            )
        );

        Gate::define('properties.dashboard.view', static fn (User $user): bool =>
            $user->is_active && $user->hasRole(
                UserRole::Admin,
                UserRole::Manager,
                UserRole::SalesAgent,
                UserRole::LegalOfficer,
            )
        );

        Gate::define('properties.reports.view', static fn (User $user): bool =>
            $user->is_active && $user->hasRole(
                UserRole::Admin,
                UserRole::Manager,
            )
        );
    }
}
