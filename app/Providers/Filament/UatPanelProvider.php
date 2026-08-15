<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\ChangePassword;
use App\Filament\Pages\Dashboard as CrmDashboard;
use App\Filament\Resources\Applications\ApplicationResource;
use App\Filament\Resources\CandidateApplications\CandidateApplicationResource;
use App\Filament\Resources\CbpApplications\CbpApplicationResource;
use App\Filament\Resources\DataCenterLeads\DataCenterLeadResource;
use App\Filament\Resources\FeDeeplinkApplications\FeDeeplinkApplicationResource;
use App\Filament\Resources\HotLeads\HotLeadResource;
use App\Filament\Resources\JobVacancies\JobVacancyResource;
use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\LotteFinanceApplications\LotteFinanceApplicationResource;
use App\Filament\Resources\ProjectReports\ProjectReportResource;
use App\Filament\Resources\SaleProfiles\SaleProfileResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\UiSetting;
use Filament\Actions\Action;
use Filament\Enums\DatabaseNotificationsPosition;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class UatPanelProvider extends AdminPanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('uat')
            ->domain('uat-apps2.3rdvn.io.vn')
            ->path('')
            ->login(Login::class)
            ->loginRouteSlug('authen/login')
            ->brandName(fn () => UiSetting::current()->app_name ?: '3RDVN CRM')
            ->brandLogo(fn () => ($path = UiSetting::current()->logo_path)
                ? $this->versionedPublicAsset($path)
                : new HtmlString('<div style="height:2rem;width:2rem;border-radius:.65rem;background:#2563eb;color:#fff;display:grid;place-items:center;font-weight:800;line-height:1">3</div>'))
            ->brandLogoHeight('2.75rem')
            ->favicon(fn () => $this->versionedPublicAsset(UiSetting::current()->favicon_path))
            ->font(fn () => $this->fontFamilyName(UiSetting::current()->font_family ?: 'Inter'))
            ->globalSearch((bool) UiSetting::current()->show_search)
            ->databaseNotifications(
                condition: fn () => (bool) UiSetting::current()->show_notifications,
                position: DatabaseNotificationsPosition::Topbar,
            )
            ->databaseNotificationsPolling('5s')
            ->userMenuItems([
                'profile' => fn (Action $action): Action => $this->accountMenuHeader($action),
                Action::make('change-password')
                    ->label('Thay đổi mật khẩu')
                    ->icon(Heroicon::Key)
                    ->url(fn (): string => url('/change-password'))
                    ->sort(10),
                'logout' => fn (Action $action): Action => $action->label('Đăng xuất'),
            ])
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups(\App\Support\Filament\AdminNavigation::groups())
            ->sidebarWidth($this->px(UiSetting::current()->sidebar_width ?: 260))
            ->collapsedSidebarWidth($this->px(UiSetting::current()->sidebar_collapsed_width ?: 76))
            ->renderHook(PanelsRenderHook::STYLES_AFTER, fn () => $this->settingsStyles())
            ->renderHook(PanelsRenderHook::BODY_START, fn () => view('partials.identity-watermark'))
            ->renderHook(PanelsRenderHook::STYLES_AFTER, fn () => $this->notificationPanelStyles())
            ->renderHook(PanelsRenderHook::HEAD_END, fn () => $this->pwaHead())
            ->renderHook(PanelsRenderHook::HEAD_END, fn () => view('filament.hooks.searchable-select-assets'))
            ->renderHook(PanelsRenderHook::SCRIPTS_BEFORE, fn () => $this->sidebarDefaultScript())
            ->renderHook(PanelsRenderHook::SCRIPTS_BEFORE, fn () => $this->userFiltersToggleScript())
            ->renderHook(PanelsRenderHook::SCRIPTS_BEFORE, fn () => $this->repaymentPreviewScript())
            ->renderHook(PanelsRenderHook::SCRIPTS_BEFORE, fn () => $this->nd13ConsentScript())
            ->renderHook(PanelsRenderHook::SCRIPTS_BEFORE, fn () => $this->notificationSoundScript())
            ->renderHook(PanelsRenderHook::SCRIPTS_BEFORE, fn () => $this->notificationPanelScript())
            ->renderHook(PanelsRenderHook::STYLES_AFTER, fn () => $this->chatStyles())
            ->renderHook(PanelsRenderHook::SCRIPTS_BEFORE, fn () => $this->chatScripts())
            ->renderHook(PanelsRenderHook::GLOBAL_SEARCH_AFTER, fn () => $this->chatLauncher())
            ->renderHook(PanelsRenderHook::TOPBAR_END, fn () => $this->topbarUserMeta())
            ->renderHook(PanelsRenderHook::BODY_END, fn () => $this->chatAssets())
            ->renderHook(PanelsRenderHook::BODY_END, fn () => view('filament.hooks.form-drafts'))
            ->renderHook(PanelsRenderHook::BODY_END, fn () => view('filament.hooks.web-push'))
            ->renderHook(PanelsRenderHook::BODY_END, fn () => $this->pwaServiceWorkerScript())
            ->colors([
                'primary' => Color::Blue,
                'gray' => Color::Slate,
            ])
            ->resources([
                LeadResource::class,
                HotLeadResource::class,
                DataCenterLeadResource::class,
                ApplicationResource::class,
                CbpApplicationResource::class,
                LotteFinanceApplicationResource::class,
                FeDeeplinkApplicationResource::class,
                ProjectReportResource::class,
                SaleProfileResource::class,
                JobVacancyResource::class,
                CandidateApplicationResource::class,
                UserResource::class,
            ])
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                CrmDashboard::class,
                ChangePassword::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
