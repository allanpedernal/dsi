<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Support\AuditContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

/**
 * Binds the request-scoped AuditContext and applies app-wide defaults (dates, password rules, admin gate).
 */
class AppServiceProvider extends ServiceProvider
{
    /** Register a request-scoped AuditContext so middleware and models share it. */
    public function register(): void
    {
        $this->app->scoped(AuditContext::class, fn () => new AuditContext);
    }

    /** Apply safe Carbon/DB/password defaults, then short-circuit gates for admins. */
    public function boot(): void
    {
        $this->configureDefaults();

        Gate::before(function ($user, string $ability) {
            return $user->hasRole(UserRole::Admin->value) ? true : null;
        });
    }

    /** Apply Carbon immutability, production DB guards, and strong password defaults. */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
