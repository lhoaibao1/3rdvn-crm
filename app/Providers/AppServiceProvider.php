<?php

namespace App\Providers;

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
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
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
