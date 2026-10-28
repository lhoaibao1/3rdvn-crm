<?php

namespace App\Providers;

use App\Http\Responses\LogoutResponse;
use App\Models\ApiMapping;
use App\Models\Application;
use App\Models\CrmModule;
use App\Models\Lead;
use App\Models\ProjectReport;
use App\Models\SaleProfile;
use App\Models\UiSetting;
use App\Models\User;
use App\Observers\ApplicationNotificationObserver;
use App\Policies\ApiMappingPolicy;
use App\Policies\ApplicationPolicy;
use App\Policies\CrmModulePolicy;
use App\Policies\LeadPolicy;
use App\Policies\ProjectReportPolicy;
use App\Policies\RolePolicy;
use App\Policies\SaleProfilePolicy;
use App\Policies\UiSettingPolicy;
use App\Policies\UserPolicy;
use Filament\Auth\Http\Responses\Contracts\LogoutResponse as LogoutResponseContract;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LogoutResponseContract::class, LogoutResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('completed-customers', function (Request $request): Limit {
            $credential = $request->bearerToken() ?: 'anonymous';

            return Limit::perMinute(60)->by(hash('sha256', $credential).'|'.$request->ip());
        });

        Application::observe(ApplicationNotificationObserver::class);

        Gate::policy(Lead::class, LeadPolicy::class);
        Gate::policy(Application::class, ApplicationPolicy::class);
        Gate::policy(SaleProfile::class, SaleProfilePolicy::class);
        Gate::policy(ApiMapping::class, ApiMappingPolicy::class);
        Gate::policy(CrmModule::class, CrmModulePolicy::class);
        Gate::policy(UiSetting::class, UiSettingPolicy::class);
        Gate::policy(ProjectReport::class, ProjectReportPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
    }
}
