<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\ChangePassword;
use App\Filament\Resources\ApiMappings\ApiMappingResource;
use App\Filament\Resources\CrmLookups\CrmLookupResource;
use App\Filament\Resources\CrmModules\CrmModuleResource;
use App\Filament\Resources\ProcessingAssignmentConfigs\ProcessingAssignmentConfigResource;
use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\SalesChannels\SalesChannelResource;
use App\Filament\Resources\SalesProjects\SalesProjectResource;
use App\Filament\Resources\UiSettings\UiSettingResource;
use App\Models\UiSetting;
use Filament\Actions\Action;
use Filament\Enums\DatabaseNotificationsPosition;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
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
            ->brandName(fn () => 'UAT · '.(UiSetting::current()->app_name ?: '3RDVN CRM'))
            ->brandLogo(fn () => ($path = UiSetting::current()->logo_path)
                ? asset('storage/'.$path)
                : new HtmlString('<div style="height:2rem;width:2rem;border-radius:.65rem;background:#2563eb;color:#fff;display:grid;place-items:center;font-weight:800;line-height:1">3</div>'))
            ->brandLogoHeight('2rem')
            ->favicon(fn () => ($path = UiSetting::current()->favicon_path) ? asset('storage/'.$path) : null)
            ->font(fn () => $this->fontFamilyName(UiSetting::current()->font_family ?: 'Inter'))
            ->globalSearch((bool) UiSetting::current()->show_search)
            ->databaseNotifications(
                condition: fn () => (bool) UiSetting::current()->show_notifications,
                position: DatabaseNotificationsPosition::Topbar,
            )
            ->databaseNotificationsPolling('5s')
            ->userMenuItems([
                'profile' => fn (Action $action): Action => $action->hidden(),
                Action::make('change-password')
                    ->label('Thay đổi mật khẩu')
                    ->icon(Heroicon::Key)
                    ->url(fn (): string => url('/change-password'))
                    ->sort(10),
                'logout' => fn (Action $action): Action => $action->label('Đăng xuất'),
            ])
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth($this->px(UiSetting::current()->sidebar_width ?: 260))
            ->collapsedSidebarWidth($this->px(UiSetting::current()->sidebar_collapsed_width ?: 76))
            ->renderHook(PanelsRenderHook::STYLES_AFTER, fn () => $this->settingsStyles())
            ->renderHook(PanelsRenderHook::STYLES_AFTER, fn () => $this->notificationPanelStyles())
            ->renderHook(PanelsRenderHook::HEAD_END, fn () => $this->pwaHead())
            ->renderHook(PanelsRenderHook::HEAD_END, fn () => view('filament.hooks.searchable-select-assets'))
            ->renderHook(PanelsRenderHook::SCRIPTS_BEFORE, fn () => $this->sidebarDefaultScript())
            ->renderHook(PanelsRenderHook::SCRIPTS_BEFORE, fn () => $this->notificationPanelScript())
            ->renderHook(PanelsRenderHook::TOPBAR_END, fn () => $this->topbarUserMeta())
            ->renderHook(PanelsRenderHook::BODY_END, fn () => $this->pwaServiceWorkerScript())
            ->colors([
                'primary' => Color::Blue,
                'gray' => Color::Slate,
            ])
            ->resources([
                CrmModuleResource::class,
                SalesProjectResource::class,
                CrmLookupResource::class,
                SalesChannelResource::class,
                ProcessingAssignmentConfigResource::class,
                UiSettingResource::class,
                ApiMappingResource::class,
                RoleResource::class,
            ])
            ->pages([
                Dashboard::class,
                ChangePassword::class,
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
