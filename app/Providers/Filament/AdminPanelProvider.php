<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Dashboard as CrmDashboard;
use App\Filament\Resources\Applications\ApplicationResource;
use App\Filament\Resources\CandidateApplications\CandidateApplicationResource;
use App\Filament\Resources\CbpApplications\CbpApplicationResource;
use App\Filament\Resources\DataCenterLeads\DataCenterLeadResource;
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
use Filament\PanelProvider;
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

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('')
            ->login(Login::class)
            ->loginRouteSlug('authen/login')
            ->brandName(fn () => UiSetting::current()->app_name ?: '3RDVN CRM')
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
            ->renderHook(PanelsRenderHook::HEAD_END, fn () => $this->pwaHead())
            ->renderHook(PanelsRenderHook::HEAD_END, fn () => view('filament.hooks.searchable-select-assets'))
            ->renderHook(PanelsRenderHook::STYLES_AFTER, fn () => $this->notificationPanelStyles())
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
                ProjectReportResource::class,
                SaleProfileResource::class,
                JobVacancyResource::class,
                CandidateApplicationResource::class,
                UserResource::class,
            ])
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                CrmDashboard::class,
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

    protected function settingsStyles(): HtmlString
    {
        $settings = UiSetting::current();
        $topbarHeight = max(56, min(104, (int) ($settings->topbar_height ?: 72)));
        $radius = max(4, min(28, (int) ($settings->radius ?: 14)));
        $font = e($this->fontStack($settings->font_family ?: 'Inter'));
        $background = $this->color($settings->background_color, '#f7f8fb');
        $surface = $this->color($settings->surface_color, '#ffffff');
        $border = $this->color($settings->border_color, '#e5e7eb');
        $text = $this->color($settings->text_color, '#101828');
        $muted = $this->color($settings->muted_text_color, '#667085');
        $sidebar = $this->color($settings->sidebar_color, '#ffffff');
        $sticky = $settings->topbar_sticky ? 'sticky' : 'relative';
        $sidebarTop = $settings->topbar_sticky ? 'top:0;' : 'top:auto;';
        $mainPadding = match ($settings->density) {
            'compact' => '12px',
            'spacious' => '20px',
            default => '16px',
        };

        return new HtmlString(<<<HTML
<style id="crm-ui-settings-runtime">
    :root {
        --font-family: {$font};
        --crm-topbar-height: {$topbarHeight}px;
        --crm-radius: {$radius}px;
        --crm-background: {$background};
        --crm-surface: {$surface};
        --crm-border: {$border};
        --crm-text: {$text};
        --crm-muted: {$muted};
        --crm-sidebar: {$sidebar};
    }

    html.fi, .fi-body {
        font-family: {$font};
    }

    .fi-body {
        background: var(--crm-background) !important;
        color: var(--crm-text);
    }

    .fi-body {
        padding-block-start: var(--crm-topbar-height) !important;
    }

    .fi-topbar-ctn {
        position: fixed !important;
        inset-block-start: 0 !important;
        inset-inline: 0 !important;
        z-index: 70 !important;
    }

    .fi-topbar {
        min-height: var(--crm-topbar-height) !important;
        height: var(--crm-topbar-height) !important;
        background: var(--crm-surface) !important;
        border-bottom: 1px solid var(--crm-border);
    }

    .fi-modal-window,
    .fi-dropdown-panel,
    .fi-global-search-results-ctn,
    .fi-no-database {
        z-index: 120 !important;
    }




    .fi-modal:has(.crm-lead-modal) > .fi-modal-window-ctn,
    .fi-modal-window-ctn:has(> .crm-lead-modal) {
        display: grid !important;
        grid-template-rows: minmax(16px, 1fr) auto minmax(16px, 1fr) !important;
        align-items: center !important;
        justify-items: center !important;
        padding: 16px !important;
        overflow: hidden !important;
    }

    .fi-modal-window.crm-lead-modal {
        width: min(980px, calc(100vw - 32px)) !important;
        max-width: min(980px, calc(100vw - 32px)) !important;
        height: min(820px, calc(100dvh - 32px)) !important;
        max-height: calc(100dvh - 32px) !important;
        margin: auto !important;
        display: flex !important;
        flex-direction: column !important;
        overflow: visible !important;
    }

    .fi-modal-window.crm-lead-process-modal {
        width: min(680px, calc(100vw - 32px)) !important;
        max-width: min(680px, calc(100vw - 32px)) !important;
        height: auto !important;
        max-height: calc(100dvh - 32px) !important;
    }

    .fi-modal-window.crm-lead-modal > .fi-modal-header,
    .fi-modal-window.crm-lead-modal > .fi-modal-footer {
        flex: 0 0 auto !important;
        padding-inline: 18px !important;
        padding-block: 10px !important;
    }

    .fi-modal-window.crm-lead-modal > .fi-modal-content {
        flex: 1 1 auto !important;
        min-height: 0 !important;
        max-height: none !important;
        overflow: auto !important;
        padding: 12px 18px !important;
        overscroll-behavior: contain !important;
        scrollbar-gutter: stable both-edges !important;
    }

    .fi-modal-window.crm-lead-modal .fi-tabs,
    .fi-modal-window.crm-lead-modal [role="tablist"] {
        max-width: 100% !important;
        overflow-x: auto !important;
        overflow-y: hidden !important;
        scrollbar-width: thin !important;
    }

    .fi-modal-window.crm-lead-modal .fi-tabs-item {
        white-space: nowrap !important;
    }

    .fi-modal-window.crm-lead-modal .fi-section {
        max-width: 100% !important;
        box-shadow: 0 8px 20px rgba(15, 23, 42, .05) !important;
    }

    .fi-modal-window.crm-lead-modal .fi-section-content,
    .fi-modal-window.crm-lead-modal .fi-in-entry-wrp,
    .fi-modal-window.crm-lead-modal .fi-fo-field-wrp,
    .fi-modal-window.crm-lead-modal .fi-sc,
    .fi-modal-window.crm-lead-modal .fi-tabs-panel,
    .fi-modal-window.crm-lead-modal .fi-grid {
        min-width: 0 !important;
        max-width: 100% !important;
    }

    .fi-modal-window.crm-lead-modal .fi-in-text,
    .fi-modal-window.crm-lead-modal .fi-fo-placeholder,
    .fi-modal-window.crm-lead-modal .fi-input {
        overflow-wrap: anywhere !important;
        word-break: break-word !important;
    }

    @media (max-width: 768px) {
        .fi-modal-window.crm-lead-modal,
        .fi-modal-window.crm-lead-process-modal {
            width: calc(100vw - 16px) !important;
            max-width: calc(100vw - 16px) !important;
            height: calc(100dvh - 16px) !important;
            max-height: calc(100dvh - 16px) !important;
            border-radius: 14px !important;
        }

        .fi-modal-window.crm-lead-modal > .fi-modal-header,
        .fi-modal-window.crm-lead-modal > .fi-modal-footer {
            padding-inline: 12px !important;
            padding-block: 9px !important;
        }

        .fi-modal-window.crm-lead-modal .fi-modal-heading {
            font-size: 1rem !important;
            line-height: 1.25 !important;
            padding-inline-end: 34px !important;
        }

        .fi-modal-window.crm-lead-modal > .fi-modal-content {
            padding: 8px 10px !important;
        }

        .fi-modal-window.crm-lead-modal .fi-section {
            border-radius: 12px !important;
        }

        .fi-modal-window.crm-lead-modal .fi-section-header,
        .fi-modal-window.crm-lead-modal .fi-section-content {
            padding-inline: 12px !important;
            padding-block: 10px !important;
        }
    }

    .fi-no {
        z-index: 120 !important;
        pointer-events: none !important;
        inset: .75rem !important;
    }

    .fi-no .fi-no-notification,
    .fi-no .fi-no-notification * {
        pointer-events: auto !important;
    }

    .fi-no-database {
        pointer-events: auto !important;
    }

    .fi-layout {
        gap: 0 !important;
        min-height: calc(100dvh - var(--crm-topbar-height)) !important;
    }

    .fi-main-ctn {
        min-width: 0 !important;
    }

    .fi-main {
        max-width: none !important;
        padding-inline: {$mainPadding} !important;
        padding-block: 0 {$mainPadding} !important;
    }

    .fi-main .fi-page,
    .fi-main .fi-page-content {
        gap: 8px !important;
        margin-block-start: 0 !important;
    }

    .fi-main .fi-page-header-main-ctn {
        padding-block-start: 8px !important;
    }

    .fi-main .fi-header {
        min-height: auto !important;
        margin: 0 !important;
        padding-block: 0 2px !important;
        gap: 8px !important;
    }

    .fi-main .fi-header-heading,
    .fi-main .fi-header h1,
    .fi-main [class*="fi-header-heading"] {
        line-height: 1.08 !important;
        font-size: clamp(1.38rem, 1.65vw, 1.78rem) !important;
        font-weight: 820 !important;
        letter-spacing: 0 !important;
        margin: 0 !important;
    }

    .fi-main .fi-breadcrumbs {
        margin: 0 !important;
        font-size: .8rem !important;
        line-height: 1.15 !important;
    }

    .fi-main .fi-header-actions,
    .fi-main .fi-ac {
        align-self: center !important;
    }

    .fi-sidebar {
        border-inline-end: 1px solid var(--crm-border);
    }

    .fi-body-has-topbar .fi-sidebar {
        top: var(--crm-topbar-height) !important;
        height: calc(100dvh - var(--crm-topbar-height)) !important;
        background: var(--crm-sidebar) !important;
    }

    .fi-body-has-sidebar-collapsible-on-desktop .fi-sidebar:not(.fi-sidebar-open) {
        width: var(--collapsed-sidebar-width) !important;
    }

    .fi-body-has-sidebar-collapsible-on-desktop .fi-main-ctn {
        flex: 1 1 auto !important;
    }

    .fi-section,
    .fi-ta-ctn,
    .fi-modal-window,
    .fi-dropdown-panel,
    .fi-input-wrp,
    .fi-fo-field-wrp .fi-input-wrp {
        border-radius: var(--crm-radius) !important;
    }

    .fi-ta-ctn {
        display: flex !important;
        flex-direction: column !important;
        gap: 8px !important;
        border: 0 !important;
        background: transparent !important;
        box-shadow: none !important;
        overflow: visible !important;
    }

    .fi-ta-content-ctn {
        border: 1px solid var(--crm-border) !important;
        border-radius: var(--crm-radius) !important;
        background: #fff !important;
        box-shadow: 0 10px 28px rgba(15, 23, 42, .05) !important;
    }

    .fi-ta-filters-above-content-ctn,
    .fi-ta-header-toolbar {
        border: 0 !important;
        border-radius: 0 !important;
        background: transparent !important;
        box-shadow: none !important;
    }

    .fi-ta-header-toolbar {
        padding: 2px 0 !important;
    }

    .fi-ta-header-toolbar,
    .fi-ta-header-toolbar .fi-ta-actions {
        justify-content: flex-end !important;
    }

    .fi-ta-header-toolbar .fi-ta-actions {
        width: 100% !important;
    }

    .fi-ta-content {
        overflow: auto !important;
        max-height: min(68dvh, calc(100dvh - var(--crm-topbar-height) - 220px));
        overscroll-behavior: contain !important;
        -webkit-overflow-scrolling: touch;
    }

    .fi-ta-table {
        min-width: 1280px !important;
    }

    .fi-ta-header-cell,
    .fi-ta-cell {
        white-space: nowrap !important;
    }

    .fi-ta-row:nth-child(odd) {
        background: color-mix(in srgb, var(--crm-background) 42%, #fff) !important;
    }

    .fi-ta-filters {
        border: 0 !important;
        background: transparent !important;
        padding: 0 !important;
        box-shadow: none !important;
    }

    .fi-ta-filters-header,
    .fi-ta-filters-heading {
        display: none !important;
    }

    .fi-ta-header-toolbar {
        align-items: flex-start !important;
    }

    .fi-ta-header-toolbar > div:has(.fi-ta-actions) {
        width: 100% !important;
    }

    .fi-ta-header-toolbar .fi-ta-actions {
        display: flex !important;
        flex-wrap: wrap !important;
        justify-content: flex-end !important;
        align-items: center !important;
        gap: 8px !important;
        width: 100% !important;
    }

    .crm-users-table .fi-ta-header-toolbar .fi-ta-actions .fi-ta-filters-trigger-action-ctn {
        display: none !important;
    }

    .crm-users-table .fi-ta-header-toolbar .fi-ta-actions [wire\:key*="exportUsers"] {
        order: 10 !important;
    }

    .crm-users-table .fi-ta-header-toolbar .fi-ta-actions [wire\:key*="createUser"],
    .crm-users-table .fi-ta-header-toolbar .fi-ta-actions a[href$="/users/create"] {
        order: 20 !important;
    }

    .crm-users-table .fi-ta-header-toolbar .fi-ta-actions .fi-ta-col-manager-dropdown,
    .crm-users-table .fi-ta-header-toolbar .fi-ta-actions .fi-ta-col-manager-modal {
        order: 30 !important;
    }

    .crm-users-table .fi-ta-filters-above-content-ctn {
        margin: 4px 0 8px !important;
        padding: 0 !important;
        border: 0 !important;
        border-radius: 0 !important;
        background: transparent !important;
        box-shadow: none !important;
    }

    .crm-users-table .fi-ta-filters {
        display: flex !important;
        flex-direction: column !important;
        gap: 8px !important;
        width: 100% !important;
        overflow: visible !important;
    }

    .crm-users-table .fi-ta-filters > .fi-sc {
        order: 1 !important;
        transition: opacity .18s ease, margin .18s ease !important;
    }

    .crm-users-table.crm-users-filter-collapsed .fi-ta-filters > .fi-sc,
    .crm-users-table:not(.crm-users-filter-expanded) .fi-ta-filters > .fi-sc {
        max-height: none !important;
        margin: 0 !important;
        overflow: visible !important;
        opacity: 1 !important;
        pointer-events: auto !important;
        transform: translateY(0) !important;
    }

    .crm-users-table.crm-users-filter-expanded .fi-ta-filters > .fi-sc {
        max-height: none !important;
        overflow: visible !important;
        opacity: 1 !important;
        pointer-events: auto !important;
        transform: translateY(0) !important;
    }

    .crm-users-table.crm-users-filter-collapsed .crm-filter-extra-field,
    .crm-users-table:not(.crm-users-filter-expanded) .crm-filter-extra-field {
        display: none !important;
    }

    .crm-users-table .fi-ta-filters-actions-ctn,
    .crm-users-table .fi-ta-filters-actions {
        order: 2 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: flex-end !important;
        gap: 8px !important;
        width: 100% !important;
        padding-top: 4px !important;
        margin-left: auto !important;
    }

    .crm-users-table .fi-ta-filters-actions-ctn .fi-btn,
    .crm-users-table .fi-ta-filters-actions .fi-btn,
    .crm-user-filter-toggle {
        min-height: 34px !important;
        padding: 7px 13px !important;
        border-radius: 8px !important;
        font-size: .84rem !important;
        font-weight: 720 !important;
        line-height: 1 !important;
        transition: background-color .16s ease, border-color .16s ease, color .16s ease, transform .16s ease, box-shadow .16s ease !important;
    }

    .crm-users-table .fi-ta-filters-actions-ctn .fi-btn:hover,
    .crm-users-table .fi-ta-filters-actions .fi-btn:hover,
    .crm-user-filter-toggle:hover {
        transform: translateY(-1px) !important;
    }

    .crm-user-filter-toggle {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        border: 1px solid #cbd5e1 !important;
        background: #fff !important;
        color: #334155 !important;
        box-shadow: 0 4px 10px rgba(15, 23, 42, .05) !important;
        cursor: pointer !important;
        white-space: nowrap !important;
    }

    .crm-user-filter-toggle svg {
        width: 1rem !important;
        height: 1rem !important;
        margin-inline-end: .38rem !important;
        stroke-width: 2 !important;
    }

    .crm-users-table .fi-ta-filters-actions-ctn .fi-btn svg,
    .crm-users-table .fi-ta-filters-actions .fi-btn svg {
        width: 1rem !important;
        height: 1rem !important;
    }

    .crm-user-filter-toggle:hover {
        border-color: #93c5fd !important;
        background: #eff6ff !important;
        color: #1d4ed8 !important;
    }

    .fi-dropdown-panel:has(.fi-ta-filters) {
        padding: 18px !important;
        max-width: min(1180px, calc(100vw - 48px)) !important;
        width: min(1180px, calc(100vw - 48px)) !important;
        border-color: #bfdbfe !important;
        background: #eff6ff !important;
    }

    .fi-dropdown-panel:has(.fi-ta-filters) .fi-ta-filters {
        padding: 4px !important;
    }

    .fi-dropdown-panel:has(.fi-ta-filters) .fi-ta-filters-actions {
        padding-top: 8px !important;
    }

    .fi-dropdown-panel:has(.fi-ta-col-manager) {
        max-height: min(560px, calc(100dvh - var(--crm-topbar-height) - 32px)) !important;
        overflow: hidden !important;
    }

    .crm-column-order-tools {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        gap: 8px !important;
        padding: 0 0 10px !important;
        margin-bottom: 8px !important;
        border-bottom: 1px solid #e2e8f0 !important;
    }

    .crm-column-order-title {
        font-size: .82rem !important;
        font-weight: 750 !important;
        color: #475569 !important;
    }

    .crm-column-order-save {
        min-height: 32px !important;
        padding: 7px 11px !important;
        border-radius: 8px !important;
        border: 1px solid #2563eb !important;
        background: #2563eb !important;
        color: #fff !important;
        font-size: .8rem !important;
        font-weight: 750 !important;
        cursor: pointer !important;
    }

    .crm-column-order-save:hover {
        background: #1d4ed8 !important;
        border-color: #1d4ed8 !important;
    }

    .crm-column-sortable-row {
        cursor: grab !important;
        border-radius: 8px !important;
        transition: background-color .14s ease, transform .14s ease, box-shadow .14s ease !important;
    }

    .crm-column-sortable-row:active {
        cursor: grabbing !important;
    }

    .crm-column-sortable-row.crm-column-dragging {
        opacity: .6 !important;
        background: #eff6ff !important;
        box-shadow: 0 8px 18px rgba(37, 99, 235, .16) !important;
    }

    .crm-column-drag-handle {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 22px !important;
        min-width: 22px !important;
        margin-inline-end: 6px !important;
        color: #94a3b8 !important;
        font-weight: 900 !important;
        letter-spacing: -2px !important;
        user-select: none !important;
    }


    .crm-column-order-trigger {
        min-height: 34px !important;
        padding: 7px 13px !important;
        border-radius: 8px !important;
        border: 1px solid #cbd5e1 !important;
        background: #fff !important;
        color: #334155 !important;
        font-size: .84rem !important;
        font-weight: 720 !important;
        line-height: 1 !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 6px !important;
        cursor: pointer !important;
        box-shadow: 0 4px 10px rgba(15, 23, 42, .05) !important;
    }

    .crm-column-order-trigger:hover {
        border-color: #93c5fd !important;
        background: #eff6ff !important;
        color: #1d4ed8 !important;
    }

    .crm-column-order-overlay {
        position: fixed !important;
        inset: 0 !important;
        z-index: 9999 !important;
        display: none !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 18px !important;
        background: rgba(15, 23, 42, .42) !important;
    }

    .crm-column-order-overlay.is-open {
        display: flex !important;
    }

    .crm-column-order-dialog {
        width: min(420px, calc(100vw - 32px)) !important;
        max-height: min(680px, calc(100dvh - 40px)) !important;
        display: flex !important;
        flex-direction: column !important;
        overflow: hidden !important;
        border-radius: 16px !important;
        border: 1px solid #e2e8f0 !important;
        background: #fff !important;
        box-shadow: 0 24px 80px rgba(15, 23, 42, .24) !important;
    }

    .crm-column-order-dialog header,
    .crm-column-order-dialog footer {
        flex: 0 0 auto !important;
        padding: 14px 16px !important;
        border-color: #e2e8f0 !important;
    }

    .crm-column-order-dialog header {
        border-bottom: 1px solid #e2e8f0 !important;
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        gap: 8px !important;
    }

    .crm-column-order-dialog h3 {
        margin: 0 !important;
        font-size: 1rem !important;
        font-weight: 800 !important;
        color: #0f172a !important;
    }

    .crm-column-order-close {
        border: 0 !important;
        background: transparent !important;
        color: #64748b !important;
        cursor: pointer !important;
        font-size: 24px !important;
        line-height: 1 !important;
    }

    .crm-column-order-list {
        flex: 1 1 auto !important;
        min-height: 0 !important;
        overflow: auto !important;
        padding: 10px !important;
        display: grid !important;
        gap: 8px !important;
    }

    .crm-column-order-item {
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
        min-height: 42px !important;
        padding: 9px 11px !important;
        border-radius: 10px !important;
        border: 1px solid #e2e8f0 !important;
        background: #f8fafc !important;
        color: #0f172a !important;
        font-weight: 700 !important;
        cursor: grab !important;
    }

    .crm-column-order-item.crm-column-dragging {
        opacity: .55 !important;
        background: #eff6ff !important;
        border-color: #93c5fd !important;
    }

    .crm-column-order-footer {
        border-top: 1px solid #e2e8f0 !important;
        display: flex !important;
        justify-content: flex-end !important;
        gap: 8px !important;
    }

    .crm-column-order-cancel,
    .crm-column-order-submit {
        min-height: 36px !important;
        padding: 8px 13px !important;
        border-radius: 9px !important;
        font-weight: 760 !important;
        cursor: pointer !important;
    }

    .crm-column-order-cancel {
        border: 1px solid #cbd5e1 !important;
        background: #fff !important;
        color: #334155 !important;
    }

    .crm-column-order-submit {
        border: 1px solid #2563eb !important;
        background: #2563eb !important;
        color: #fff !important;
    }

    .fi-ta-col-manager {
        display: flex !important;
        max-height: min(540px, calc(100dvh - var(--crm-topbar-height) - 48px)) !important;
        flex-direction: column !important;
    }

    .fi-ta-col-manager-header,
    .fi-ta-col-manager-actions-ctn {
        flex: 0 0 auto !important;
    }

    .fi-ta-col-manager-items {
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) !important;
        max-height: 320px !important;
        overflow-x: hidden !important;
        overflow-y: auto !important;
        overscroll-behavior: contain !important;
        gap: 0 !important;
        padding-inline-end: 6px !important;
    }

    .fi-ta-col-manager-group,
    .fi-ta-col-manager-group-items {
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) !important;
        min-width: 0 !important;
    }

    .fi-ta-col-manager-group-items {
        padding-inline-start: 0 !important;
    }

    .fi-ta-col-manager-item {
        min-height: 42px !important;
        width: 100% !important;
        min-width: 0 !important;
        break-inside: auto !important;
    }

    .fi-ta-col-manager-label {
        min-width: 0 !important;
        width: 100% !important;
    }

    .fi-ta-col-manager-label span {
        min-width: 0 !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        white-space: nowrap !important;
    }

    .fi-topbar-user-meta {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 2px;
        margin-inline-start: .45rem;
        line-height: 1.15;
        min-width: max-content;
        white-space: nowrap;
    }

    .fi-topbar-user-meta strong {
        display: block;
        color: var(--crm-text);
        font-size: .875rem;
        font-weight: 700;
    }

    .fi-topbar-user-meta span {
        display: block;
        color: var(--crm-muted);
        font-size: .72rem;
        font-weight: 600;
    }

    .fi-main:has(.crm-user-record-header) {
        background: #f5f7fb !important;
    }

    .fi-main:has(.crm-user-record-header) .fi-page {
        gap: 8px !important;
    }

    .fi-main:has(.crm-user-record-header) .fi-header {
        padding-block-end: 0 !important;
    }

    .fi-main:has(.crm-user-record-header) .fi-section {
        border: 1px solid #e4e9f2 !important;
        border-radius: 8px !important;
        background: #fff !important;
        box-shadow: none !important;
    }

    .fi-main:has(.crm-user-record-header) .fi-section-header {
        padding-block: 10px 7px !important;
    }

    .fi-main:has(.crm-user-record-header) .fi-section-content {
        padding-block: 12px !important;
    }

    .fi-main:has(.crm-user-record-header) .fi-tabs {
        min-height: auto !important;
        border-radius: 0 !important;
        border: 0 !important;
        border-bottom: 1px solid #dce5f2 !important;
        background: transparent !important;
        padding: 0 !important;
        box-shadow: none !important;
    }

    .fi-main:has(.crm-user-record-header) .fi-tabs-tab {
        min-height: 42px !important;
        border-radius: 0 !important;
        font-size: .86rem !important;
        font-weight: 700 !important;
    }

    .fi-main:has(.crm-user-record-header) .fi-sc-tabs {
        margin-block-start: 0 !important;
        height: auto !important;
    }

    .fi-main:has(.crm-user-record-header) .fi-sc-tabs-tab.fi-active {
        margin-block-start: 16px !important;
        min-height: 430px !important;
        padding: 22px !important;
        border: 1px solid #e4e9f2 !important;
        border-radius: 8px !important;
        background: #fff !important;
    }

    .fi-main:has(.crm-user-record-header) .fi-fo-field-wrp-label label,
    .fi-main:has(.crm-user-record-header) .fi-in-entry-wrp-label span {
        font-size: .8rem !important;
        font-weight: 740 !important;
        color: #334155 !important;
    }

    .fi-main:has(.crm-user-record-header) .fi-input-wrp,
    .fi-main:has(.crm-user-record-header) .fi-select-input,
    .fi-main:has(.crm-user-record-header) .fi-ta-textarea {
        border-radius: 7px !important;
    }

    .fi-main:has(.crm-user-record-header) .fi-input,
    .fi-main:has(.crm-user-record-header) .fi-select-input,
    .fi-main:has(.crm-user-record-header) .fi-ta-textarea {
        font-size: .9rem !important;
    }

    .fi-main:has(.crm-user-record-header) .filepond--drop-label label {
        font-size: 0 !important;
    }

    .fi-main:has(.crm-user-record-header) .filepond--drop-label label::before {
        content: 'Kéo thả ảnh hoặc bấm để chọn';
        font-size: .875rem !important;
        font-weight: 650;
        color: #64748b;
    }

    .crm-user-record-header {
        display: grid;
        grid-template-columns: 122px minmax(0, 1fr);
        align-items: start;
        gap: 22px;
        padding: 22px 28px;
        border: 1px solid #e4e9f2;
        border-radius: 8px;
        background: #fff;
        box-shadow: none;
    }

    .crm-record-avatar {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-start;
        padding-top: 2px;
    }

    .crm-record-avatar-circle {
        width: 96px;
        height: 96px;
        border: 1px solid #dbe7ff;
        border-radius: 999px;
        display: grid;
        place-items: center;
        color: #2563eb;
        font-size: 1.45rem;
        font-weight: 820;
        letter-spacing: 0;
        background: #eef5ff;
    }

    .crm-record-identity {
        min-width: 0;
        padding-top: 5px;
    }

    .crm-record-name {
        max-width: 100%;
        color: #111827;
        font-size: clamp(1.8rem, 2.7vw, 2.55rem);
        font-weight: 820;
        line-height: 1.1;
        letter-spacing: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .crm-record-subline {
        margin-top: 8px;
        color: #475569;
        font-size: .98rem;
        line-height: 1.35;
        font-weight: 500;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .crm-record-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 14px;
    }

    .crm-record-actions span {
        display: inline-flex;
        align-items: center;
        min-height: 28px;
        padding: 4px 10px;
        border: 1px solid #e2e8f0;
        border-radius: 999px;
        background: #f8fafc;
        color: #475569;
        font-size: .82rem;
        font-weight: 650;
        line-height: 1;
    }

    .crm-record-quick-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px 48px;
        align-content: start;
        margin-top: 24px;
    }

    .crm-record-field {
        min-width: 0;
    }

    .crm-record-field span {
        display: block;
        color: #334155;
        font-size: .88rem;
        line-height: 1.1;
        font-weight: 760;
        letter-spacing: 0;
    }

    .crm-record-field strong {
        display: block;
        margin-top: 7px;
        color: #111827;
        font-size: .95rem;
        line-height: 1.35;
        font-weight: 500;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: normal;
        opacity: 1;
    }

    @media (max-width: 1100px) {
        .crm-user-record-header {
            grid-template-columns: 108px minmax(0, 1fr);
            gap: 18px;
        }

        .crm-record-avatar-circle {
            width: 82px;
            height: 82px;
        }

        .crm-record-quick-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            column-gap: 28px;
        }
    }

    @media (max-width: 768px) {
        .fi-topbar-user-meta { display: none; }

        .crm-user-record-header {
            grid-template-columns: 1fr;
            gap: 12px;
            padding: 18px;
        }

        .crm-record-avatar {
            align-items: flex-start;
        }

        .crm-record-name {
            white-space: normal;
        }

        .crm-record-subline {
            white-space: normal;
        }

        .crm-record-quick-grid {
            grid-template-columns: 1fr;
            row-gap: 16px;
            margin-top: 18px;
        }

        .fi-main:has(.crm-user-record-header) .fi-sc-tabs-tab.fi-active {
            min-height: 320px !important;
        }
    }

    .fi-main:has(.crm-profile-summary) {
        background: #f7f8fc !important;
    }

    .fi-main:has(.crm-profile-summary) .fi-page {
        gap: 12px !important;
    }

    .fi-main:has(.crm-profile-summary) .fi-header {
        margin-bottom: 0 !important;
        padding-bottom: 0 !important;
    }

    .fi-main:has(.crm-profile-summary) .fi-tabs {
        display: flex !important;
        gap: 18px !important;
        border: 0 !important;
        background: transparent !important;
        padding: 0 0 14px !important;
        box-shadow: none !important;
    }

    .fi-main:has(.crm-profile-summary) .fi-tabs-tab {
        min-height: 46px !important;
        padding-inline: 18px !important;
        border-radius: 8px !important;
        color: #3f4658 !important;
        font-size: .95rem !important;
        font-weight: 760 !important;
        letter-spacing: 0 !important;
    }

    .fi-main:has(.crm-profile-summary) .fi-tabs-tab svg {
        width: 16px !important;
        height: 16px !important;
    }

    .fi-main:has(.crm-profile-summary) .fi-tabs-tab[aria-selected="true"],
    .fi-main:has(.crm-profile-summary) .fi-tabs-tab.fi-active {
        background: #6264f6 !important;
        color: #fff !important;
        box-shadow: 0 12px 22px rgba(98, 100, 246, .24) !important;
    }

    .fi-main:has(.crm-profile-summary) .fi-sc-tabs {
        margin-top: 0 !important;
        height: auto !important;
    }

    .fi-main:has(.crm-profile-summary) .fi-sc-tabs-tab.fi-active {
        margin-top: 0 !important;
        min-height: 560px !important;
        padding: 28px !important;
        border: 1px solid #e6e8f0 !important;
        border-radius: 10px !important;
        background: #fff !important;
        box-shadow: 0 14px 32px rgba(15, 23, 42, .06) !important;
    }

    .fi-main:has(.crm-profile-summary) .fi-section {
        border: 0 !important;
        border-radius: 0 !important;
        background: transparent !important;
        box-shadow: none !important;
    }

    .fi-main:has(.crm-profile-summary) .fi-section-header {
        padding: 8px 0 10px !important;
    }

    .fi-main:has(.crm-profile-summary) .fi-section-header-heading {
        color: #111827 !important;
        font-size: 1rem !important;
        font-weight: 820 !important;
    }

    .fi-main:has(.crm-profile-summary) .fi-section-content {
        padding: 0 !important;
    }

    .crm-profile-summary {
        display: grid !important;
        grid-template-columns: 132px minmax(0, 1fr) !important;
        align-items: center !important;
        gap: 28px !important;
        margin-bottom: 22px !important;
        padding: 0 !important;
        border: 0 !important;
        border-radius: 0 !important;
        background: transparent !important;
        box-shadow: none !important;
    }

    .crm-profile-avatar {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .crm-profile-avatar-img,
    .crm-profile-avatar-initials {
        width: 118px;
        height: 118px;
        border-radius: 999px;
        border: 1px solid #dbe4f0;
        object-fit: cover;
        background: #eef4ff;
    }

    .crm-profile-avatar-initials {
        display: grid;
        place-items: center;
        color: #6264f6;
        font-size: 1.9rem;
        font-weight: 860;
    }

    .crm-profile-main {
        min-width: 0;
    }

    .crm-profile-name {
        color: #111827;
        font-size: clamp(1.75rem, 2.35vw, 2.4rem);
        font-weight: 860;
        line-height: 1.1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .crm-profile-email {
        margin-top: 8px;
        color: #5f6678;
        font-size: .96rem;
        font-weight: 600;
    }

    .crm-profile-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 16px;
    }

    .crm-profile-tags span {
        display: inline-flex;
        align-items: center;
        min-height: 30px;
        padding: 5px 12px;
        border-radius: 999px;
        background: #eefbe7;
        color: #55d91d;
        font-size: .82rem;
        font-weight: 760;
    }

    .fi-main:has(.crm-profile-summary) .fi-fo-field-wrp,
    .fi-main:has(.crm-profile-summary) .fi-in-entry-wrp {
        position: relative;
        margin-top: 10px;
    }

    .fi-main:has(.crm-profile-summary) .fi-fo-field-wrp-label,
    .fi-main:has(.crm-profile-summary) .fi-in-entry-wrp-label {
        position: absolute;
        top: -9px;
        left: 14px;
        z-index: 3;
        width: auto !important;
        padding-inline: 8px;
        background: #fff;
    }

    .fi-main:has(.crm-profile-summary) .fi-fo-field-wrp-label label,
    .fi-main:has(.crm-profile-summary) .fi-in-entry-wrp-label span {
        color: #a6abb9 !important;
        font-size: .82rem !important;
        font-weight: 780 !important;
        line-height: 1.2 !important;
    }

    .fi-main:has(.crm-profile-summary) .fi-input-wrp,
    .fi-main:has(.crm-profile-summary) .fi-select-input,
    .fi-main:has(.crm-profile-summary) .fi-ta-textarea {
        min-height: 58px !important;
        border: 1px solid #d6d9e2 !important;
        border-radius: 9px !important;
        background: #fff !important;
        box-shadow: none !important;
    }

    .fi-main:has(.crm-profile-summary) .fi-input,
    .fi-main:has(.crm-profile-summary) .fi-select-input,
    .fi-main:has(.crm-profile-summary) .fi-ta-textarea,
    .fi-main:has(.crm-profile-summary) .fi-in-entry-wrp-content {
        color: #3f4658 !important;
        font-size: .98rem !important;
        font-weight: 620 !important;
    }

    .fi-main:has(.crm-profile-summary) .fi-in-entry-wrp-content {
        min-height: 58px;
        display: flex;
        align-items: center;
        padding: 16px 18px;
        border: 1px solid #d6d9e2;
        border-radius: 9px;
        background: #fff;
    }

    .fi-main:has(.crm-profile-summary) .filepond--root {
        max-width: 460px;
        margin-top: 4px;
    }

    @media (max-width: 900px) {
        .fi-main:has(.crm-profile-summary) .fi-tabs {
            gap: 8px !important;
            overflow-x: auto;
        }

        .fi-main:has(.crm-profile-summary) .fi-tabs-tab {
            flex: 0 0 auto;
        }

        .crm-profile-summary {
            grid-template-columns: 1fr !important;
            justify-items: start;
            gap: 16px !important;
        }

        .crm-profile-name {
            white-space: normal;
        }

        .fi-main:has(.crm-profile-summary) .fi-sc-tabs-tab.fi-active {
            padding: 18px !important;
        }
    }

    @media (max-width: 768px) {
        html.fi,
        .fi-body {
            width: 100% !important;
            max-width: 100% !important;
            overflow-x: hidden !important;
        }

        .fi-body {
            padding-block-start: var(--crm-topbar-height) !important;
        }

        .fi-layout,
        .fi-main-ctn,
        .fi-main,
        .fi-page,
        .fi-page-content,
        .fi-sc,
        .fi-section,
        .fi-section-content,
        .fi-grid,
        .fi-fo-field-wrp,
        .fi-in-entry-wrp {
            min-width: 0 !important;
            max-width: 100% !important;
        }

        .fi-main {
            width: 100% !important;
            padding-inline: 10px !important;
            padding-block: 8px 12px !important;
            overflow-x: hidden !important;
        }

        .fi-main .fi-page,
        .fi-main .fi-page-content {
            gap: 10px !important;
        }

        .fi-main .fi-header {
            align-items: stretch !important;
            gap: 8px !important;
            padding-block: 0 !important;
        }

        .fi-main .fi-header-heading,
        .fi-main .fi-header h1,
        .fi-main [class*="fi-header-heading"] {
            font-size: 1.28rem !important;
            line-height: 1.16 !important;
            white-space: normal !important;
            overflow-wrap: anywhere !important;
        }

        .fi-main .fi-breadcrumbs {
            max-width: 100% !important;
            overflow-x: auto !important;
            white-space: nowrap !important;
            scrollbar-width: none !important;
        }

        .fi-main .fi-header-actions,
        .fi-main .fi-ac,
        .fi-ta-header-toolbar,
        .fi-ta-header-toolbar .fi-ta-actions,
        .fi-ta-filters-actions-ctn,
        .fi-ta-filters-actions {
            width: 100% !important;
            max-width: 100% !important;
            display: flex !important;
            flex-wrap: wrap !important;
            align-items: center !important;
            justify-content: flex-end !important;
            gap: 6px !important;
        }

        .fi-btn,
        .crm-user-filter-toggle,
        .crm-column-order-trigger {
            min-height: 34px !important;
            max-width: 100% !important;
            padding: 7px 10px !important;
            border-radius: 8px !important;
            font-size: .82rem !important;
            line-height: 1.1 !important;
            white-space: nowrap !important;
        }

        .fi-section {
            border-radius: 12px !important;
            overflow: hidden !important;
        }

        .fi-section-header,
        .fi-section-content {
            padding-inline: 12px !important;
            padding-block: 10px !important;
        }

        .fi-input-wrp,
        .fi-select-input,
        .fi-ta-textarea,
        .fi-input,
        .fi-select-input,
        textarea,
        input,
        select {
            max-width: 100% !important;
            min-width: 0 !important;
        }

        .fi-input,
        .fi-select-input,
        .fi-ta-textarea {
            font-size: .9rem !important;
        }

        .fi-fo-field-wrp-label label,
        .fi-in-entry-wrp-label span {
            font-size: .8rem !important;
            line-height: 1.25 !important;
        }

        .fi-tabs,
        [role="tablist"] {
            max-width: 100% !important;
            overflow-x: auto !important;
            overflow-y: hidden !important;
            scrollbar-width: thin !important;
        }

        .fi-tabs-tab,
        .fi-tabs-item,
        [role="tab"] {
            flex: 0 0 auto !important;
            white-space: nowrap !important;
        }

        .fi-ta-ctn,
        .fi-ta-content-ctn {
            max-width: 100% !important;
            overflow-x: auto !important;
            overflow-y: visible !important;
        }

        .fi-ta-content {
            max-width: 100% !important;
            max-height: min(62dvh, calc(100dvh - var(--crm-topbar-height) - 190px)) !important;
            overflow: auto !important;
            overscroll-behavior: contain !important;
            -webkit-overflow-scrolling: touch !important;
        }

        .fi-ta-table {
            min-width: 920px !important;
        }

        .fi-ta-cell,
        .fi-ta-header-cell {
            padding-inline: 10px !important;
            white-space: nowrap !important;
        }

        .fi-global-search-results-ctn {
            width: min(420px, calc(100vw - 16px)) !important;
            max-width: calc(100vw - 16px) !important;
            max-height: calc(100dvh - var(--crm-topbar-height) - 20px) !important;
            overflow: auto !important;
        }

        .fi-modal-window-ctn,
        .fi-modal:has(.crm-lead-modal) > .fi-modal-window-ctn,
        .fi-modal-window-ctn:has(> .crm-lead-modal) {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            min-height: 100dvh !important;
            padding: 8px !important;
            overflow: hidden !important;
        }

        .fi-modal-window,
        .fi-modal-window.crm-lead-modal,
        .fi-modal-window.crm-lead-process-modal {
            width: calc(100vw - 16px) !important;
            max-width: calc(100vw - 16px) !important;
            height: auto !important;
            max-height: calc(100dvh - 16px) !important;
            margin: auto !important;
            display: flex !important;
            flex-direction: column !important;
            overflow: hidden !important;
            border-radius: 14px !important;
        }

        .fi-modal-window.crm-lead-modal {
            height: calc(100dvh - 16px) !important;
        }

        .fi-modal-window > .fi-modal-header,
        .fi-modal-window > .fi-modal-footer,
        .fi-modal-window.crm-lead-modal > .fi-modal-header,
        .fi-modal-window.crm-lead-modal > .fi-modal-footer {
            flex: 0 0 auto !important;
            padding: 10px 12px !important;
        }

        .fi-modal-window > .fi-modal-content,
        .fi-modal-window.crm-lead-modal > .fi-modal-content {
            flex: 1 1 auto !important;
            min-height: 0 !important;
            max-height: none !important;
            overflow: auto !important;
            padding: 10px 12px !important;
            overscroll-behavior: contain !important;
        }

        .fi-modal-heading {
            max-width: calc(100vw - 88px) !important;
            font-size: 1rem !important;
            line-height: 1.25 !important;
            overflow-wrap: anywhere !important;
        }

        .fi-no {
            inset: 8px !important;
            max-width: calc(100vw - 16px) !important;
        }

        .fi-no .fi-no-notification {
            max-width: calc(100vw - 16px) !important;
        }

        .crm-users-table .fi-ta-filters > .fi-sc {
            max-width: 100% !important;
        }

        .crm-users-table .fi-ta-filters-actions-ctn,
        .crm-users-table .fi-ta-filters-actions {
            justify-content: flex-end !important;
        }
    }


</style>
HTML);
    }

    protected function userFiltersToggleScript(): HtmlString
    {
        return new HtmlString(<<<'HTML'
<script data-navigate-once>
    (() => {
        if (window.__crmUserFilterToggleBound) {
            return;
        }

        window.__crmUserFilterToggleBound = true;

        const filterStateKey = () => `3rdvn:table-filter-expanded:${window.location.pathname.replace(/\/$/, '') || '/'}`;
        const columnOrderSaveUrl = '/crm/table-column-preferences';
        let activeColumnTableKey = null;

        const userTables = () => Array.from(document.querySelectorAll('.fi-ta.crm-users-table'))
            .filter((table) => table.querySelector('.fi-ta-filters'));

        const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            || decodeURIComponent(document.cookie.split('; ').find((row) => row.startsWith('XSRF-TOKEN='))?.split('=')[1] || '');

        const normalizeColumnKey = (value) => (value || '')
            .trim()
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/đ/g, 'd')
            .replace(/[^a-z0-9]+/g, '_')
            .replace(/^_+|_+$/g, '');

        const icon = (expanded) => expanded
            ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m18 15-6-6-6 6"/></svg>'
            : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>';

        const setExpanded = (table, button, expanded) => {
            const value = expanded ? '1' : '0';

            if (table.dataset.crmFilterExpanded !== value) {
                table.dataset.crmFilterExpanded = value;
                table.classList.toggle('crm-users-filter-expanded', expanded);
                table.classList.toggle('crm-users-filter-collapsed', ! expanded);
            }

            if (button.dataset.crmExpanded !== value) {
                button.dataset.crmExpanded = value;
                button.innerHTML = `${icon(expanded)}<span>${expanded ? 'Thu gọn' : 'Mở rộng'}</span>`;
                button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                button.setAttribute('aria-label', expanded ? 'Thu gọn bộ lọc' : 'Mở rộng bộ lọc');
            }
        };

        const normalizeActionLabels = (actions) => {
            actions.querySelectorAll('button, a').forEach((action) => {
                const text = action.textContent.trim().toLowerCase();

                if (['reset', 'đặt lại', 'dat lai'].includes(text)) {
                    action.classList.add('crm-user-filter-reset');
                    const label = action.querySelector('.fi-btn-label');
                    label ? label.textContent = 'Reset' : null;
                }
            });
        };

        const syncUserFilterTable = () => {
            userTables().forEach((table) => {
                table.classList.add('crm-users-table');

                const filters = table.querySelector('.fi-ta-filters');
                const form = filters?.querySelector(':scope > .fi-sc');
                const actions = filters?.querySelector('.fi-ta-filters-actions-ctn')
                    || filters?.querySelector('.fi-ta-filters-actions');

                if (! filters || ! form || ! actions) {
                    return;
                }

                normalizeActionLabels(actions);

                const fieldContainer = form.querySelector(':scope > .fi-grid') || form;
                const fields = Array.from(fieldContainer.children)
                    .filter((field) => field.querySelector('input, select, textarea, button[role="combobox"]'));

                form.querySelectorAll('.crm-filter-extra-field')
                    .forEach((field) => field.classList.remove('crm-filter-extra-field'));

                fields.forEach((field, index) => field.classList.toggle('crm-filter-extra-field', index >= 3));

                let button = actions.querySelector('.crm-user-filter-toggle');

                if (! button) {
                    button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'crm-user-filter-toggle';
                    actions.appendChild(button);
                }

                const expanded = window.localStorage.getItem(filterStateKey()) === '1';
                setExpanded(table, button, expanded);
            });
        };

        const visibleColumnLabels = (table) => Array.from(table.querySelectorAll('thead th, .fi-ta-header-cell'))
            .map((cell) => (cell.innerText || cell.textContent || '').replace(/\s+/g, ' ').trim())
            .map((label) => label.replace(/^(sort|sắp xếp)\s+/i, '').trim())
            .filter((label) => label && ! ['Actions', 'Hành động'].includes(label))
            .filter((label, index, labels) => labels.indexOf(label) === index);

        const ensureColumnOrderModal = () => {
            let overlay = document.querySelector('.crm-column-order-overlay');

            if (overlay) {
                return overlay;
            }

            overlay = document.createElement('div');
            overlay.className = 'crm-column-order-overlay';
            overlay.innerHTML = `
                <section class="crm-column-order-dialog" role="dialog" aria-modal="true" aria-label="Sắp xếp cột">
                    <header>
                        <h3>Sắp xếp cột</h3>
                        <button type="button" class="crm-column-order-close" aria-label="Đóng">×</button>
                    </header>
                    <div class="crm-column-order-list"></div>
                    <footer class="crm-column-order-footer">
                        <button type="button" class="crm-column-order-cancel">Hủy</button>
                        <button type="button" class="crm-column-order-submit">Lưu thứ tự</button>
                    </footer>
                </section>
            `;
            document.body.appendChild(overlay);

            overlay.addEventListener('click', (event) => {
                if (event.target === overlay || event.target.closest('.crm-column-order-close, .crm-column-order-cancel')) {
                    overlay.classList.remove('is-open');
                }
            });

            overlay.addEventListener('dragstart', (event) => {
                const row = event.target.closest?.('.crm-column-order-item');

                if (! row) {
                    return;
                }

                row.classList.add('crm-column-dragging');
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', row.dataset.columnLabel || row.textContent.trim());
            });

            overlay.addEventListener('dragend', (event) => {
                event.target.closest?.('.crm-column-order-item')?.classList.remove('crm-column-dragging');
            });

            overlay.addEventListener('dragover', (event) => {
                const list = event.target.closest?.('.crm-column-order-list');
                const dragging = overlay.querySelector('.crm-column-dragging');

                if (! list || ! dragging) {
                    return;
                }

                event.preventDefault();
                const rows = Array.from(list.querySelectorAll('.crm-column-order-item:not(.crm-column-dragging)'));
                const before = rows.reduce((closest, row) => {
                    const box = row.getBoundingClientRect();
                    const offset = event.clientY - box.top - box.height / 2;

                    if (offset < 0 && offset > closest.offset) {
                        return { offset, row };
                    }

                    return closest;
                }, { offset: Number.NEGATIVE_INFINITY, row: null }).row;

                if (before) {
                    list.insertBefore(dragging, before);
                } else {
                    list.appendChild(dragging);
                }
            });

            overlay.querySelector('.crm-column-order-submit').addEventListener('click', async () => {
                const tableKey = overlay.dataset.tableKey;
                const order = Array.from(overlay.querySelectorAll('.crm-column-order-item'))
                    .map((item) => item.dataset.columnLabel)
                    .filter(Boolean);
                const button = overlay.querySelector('.crm-column-order-submit');
                const oldText = button.textContent;

                if (! tableKey || order.length === 0) {
                    return;
                }

                button.disabled = true;
                button.textContent = 'Đang lưu...';

                try {
                    const response = await fetch(columnOrderSaveUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken(),
                        },
                        body: JSON.stringify({ table_key: tableKey, column_order: order }),
                    });

                    if (! response.ok) {
                        throw new Error(response.status === 403 ? 'Chỉ Admin được lưu thứ tự cột.' : 'Không lưu được thứ tự cột.');
                    }

                    window.location.assign(window.location.href);
                } catch (error) {
                    alert(error.message || 'Không lưu được thứ tự cột.');
                    button.disabled = false;
                    button.textContent = oldText;
                }
            });

            return overlay;
        };

        const openColumnOrderModal = (table) => {
            const tableKey = table.dataset.crmColumnTable;
            const labels = visibleColumnLabels(table);

            if (! tableKey || labels.length === 0) {
                alert('Không tìm thấy cột đang hiển thị để sắp xếp.');
                return;
            }

            const overlay = ensureColumnOrderModal();
            const list = overlay.querySelector('.crm-column-order-list');
            overlay.dataset.tableKey = tableKey;
            list.innerHTML = labels.map((label) => `
                <div class="crm-column-order-item" draggable="true" data-column-label="${label.replace(/"/g, '&quot;')}">
                    <span class="crm-column-drag-handle">⋮⋮</span>
                    <span>${label}</span>
                </div>
            `).join('');
            overlay.querySelector('.crm-column-order-submit').disabled = false;
            overlay.querySelector('.crm-column-order-submit').textContent = 'Lưu thứ tự';
            overlay.classList.add('is-open');
        };

        const syncColumnOrderButtons = () => {
            document.querySelectorAll('.fi-ta.crm-users-table[data-crm-column-table]').forEach((table) => {
                const actions = table.querySelector('.fi-ta-header-toolbar .fi-ta-actions');

                if (! actions || actions.querySelector('.crm-column-order-trigger')) {
                    return;
                }

                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'crm-column-order-trigger';
                button.innerHTML = '<span>↕</span><span>Sắp xếp cột</span>';
                button.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    openColumnOrderModal(table);
                });
                actions.appendChild(button);
            });
        };

        document.addEventListener('click', (event) => {
            const button = event.target.closest?.('.crm-user-filter-toggle');

            if (! button) {
                return;
            }

            const table = button.closest('.crm-users-table') || button.closest('.fi-ta');

            if (! table) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            const expanded = ! table.classList.contains('crm-users-filter-expanded');
            window.localStorage.setItem(filterStateKey(), expanded ? '1' : '0');
            setExpanded(table, button, expanded);
        }, true);

        let raf = null;
        const scheduleSync = () => {
            if (raf) {
                return;
            }

            raf = window.requestAnimationFrame(() => {
                raf = null;
                syncUserFilterTable();
                syncColumnOrderButtons();
            });
        };

        scheduleSync();
        setTimeout(scheduleSync, 80);
        setTimeout(scheduleSync, 300);
        document.addEventListener('DOMContentLoaded', scheduleSync);
        document.addEventListener('livewire:navigated', scheduleSync);
        document.addEventListener('livewire:update', scheduleSync);
        document.addEventListener('livewire:updated', scheduleSync);

        new MutationObserver(scheduleSync).observe(document.body || document.documentElement, {
            childList: true,
            subtree: true,
        });
    })();
</script>
HTML);
    }

    protected function repaymentPreviewScript(): HtmlString
    {
        return new HtmlString(<<<'HTML'
<script data-navigate-once>
    (() => {
        if (window.__crmRepaymentPreviewBound) {
            return;
        }

        window.__crmRepaymentPreviewBound = true;

        const ensureStyle = () => {
            if (document.getElementById('crm-repayment-preview-style')) {
                return;
            }

            const style = document.createElement('style');
            style.id = 'crm-repayment-preview-style';
            style.textContent = `

                .crm-repayment-preview-btn {
                    display: inline-flex;
                    align-items: center;
                    gap: 10px;
                    border: 0;
                    background: transparent;
                    color: #2563eb;
                    cursor: pointer;
                    padding: 0;
                    font-size: 14px;
                    font-weight: 700;
                }
                .crm-repayment-preview-btn span:last-child {
                    color: #64748b;
                    font-size: 12px;
                    font-weight: 600;
                }
                .crm-repayment-overlay {
                    position: fixed;
                    inset: 0;
                    z-index: 10050;
                    display: grid;
                    place-items: center;
                    background: rgba(15, 23, 42, .45);
                    padding: 20px;
                }
                .crm-repayment-dialog {
                    width: min(920px, 96vw);
                    max-height: min(720px, 86dvh);
                    display: flex;
                    flex-direction: column;
                    overflow: hidden;
                    border-radius: 18px;
                    background: #fff;
                    box-shadow: 0 24px 80px rgba(15, 23, 42, .28);
                }
                .crm-repayment-header {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 16px;
                    padding: 16px 18px;
                    border-bottom: 1px solid #e5e7eb;
                    font-size: 18px;
                    font-weight: 800;
                    color: #0f172a;
                }
                .crm-repayment-close {
                    border: 0;
                    background: #f8fafc;
                    color: #64748b;
                    cursor: pointer;
                    border-radius: 999px;
                    width: 36px;
                    height: 36px;
                    font-size: 22px;
                    line-height: 1;
                }
                .crm-repayment-body {
                    padding: 16px;
                    overflow: auto;
                    -webkit-overflow-scrolling: touch;
                }
                .crm-repayment-table-wrap {
                    overflow: auto;
                    border: 1px solid #e5e7eb;
                    border-radius: 12px;
                    background: #fff;
                }
                .crm-repayment-table {
                    min-width: 720px;
                    width: 100%;
                    border-collapse: collapse;
                    font-size: 14px;
                }
                .crm-repayment-table thead {
                    position: sticky;
                    top: 0;
                    background: #f8fafc;
                    color: #475569;
                }
                .crm-repayment-table th,
                .crm-repayment-table td {
                    padding: 10px 12px;
                    border-bottom: 1px solid #edf2f7;
                    text-align: right;
                    white-space: nowrap;
                }
                .crm-repayment-table th:first-child,
                .crm-repayment-table td:first-child {
                    text-align: center;
                }
                .crm-repayment-table td:nth-child(4) {
                    font-weight: 800;
                    color: #0f172a;
                }
                @media (max-width: 768px) {
                    .crm-repayment-overlay {
                        padding: 10px;
                    }
                    .crm-repayment-dialog {
                        width: 100%;
                        max-height: 88dvh;
                        border-radius: 16px;
                    }
                    .crm-repayment-header {
                        padding: 14px;
                        font-size: 16px;
                    }
                    .crm-repayment-body {
                        padding: 12px;
                    }
                }

            `;
            document.head.appendChild(style);
        };

        const closeRepaymentModal = () => {
            document.querySelector('.crm-repayment-overlay')?.remove();
            document.body.style.removeProperty('overflow');
        };

        const openRepaymentModal = (button) => {
            closeRepaymentModal();

            const html = button.dataset.crmRepaymentHtml || '';
            const title = button.dataset.crmRepaymentTitle || 'Lịch trả nợ dự kiến';
            const overlay = document.createElement('div');
            overlay.className = 'crm-repayment-overlay';
            overlay.innerHTML = `
                <div class="crm-repayment-dialog" role="dialog" aria-modal="true" aria-label="${title.replace(/"/g, '&quot;')}">
                    <div class="crm-repayment-header">
                        <span>${title}</span>
                        <button type="button" class="crm-repayment-close" aria-label="Đóng">×</button>
                    </div>
                    <div class="crm-repayment-body">${html}</div>
                </div>
            `;

            overlay.addEventListener('click', (event) => {
                if (event.target === overlay || event.target.closest('.crm-repayment-close')) {
                    closeRepaymentModal();
                }
            });

            document.body.appendChild(overlay);
            document.body.style.overflow = 'hidden';
        };

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeRepaymentModal();
            }
        });

        document.addEventListener('click', (event) => {
            const button = event.target.closest?.('.crm-repayment-preview-btn');

            if (! button) {
                return;
            }

            event.preventDefault();
            openRepaymentModal(button);
        });

        const schedule = () => {
            ensureStyle();
        };

        schedule();
        document.addEventListener("DOMContentLoaded", schedule);
        document.addEventListener("livewire:navigated", schedule);
    })();
</script>
HTML);
    }

    protected function nd13ConsentScript(): HtmlString
    {
        return new HtmlString(<<<'HTML'
<script data-navigate-once>
    (() => {
        if (window.__crmNd13ConsentBound) {
            return;
        }

        window.__crmNd13ConsentBound = true;

        const ensureStyles = () => {
            if (document.getElementById('crm-nd13-consent-style')) {
                return;
            }

            const style = document.createElement('style');
            style.id = 'crm-nd13-consent-style';
            style.textContent = `
                .crm-nd13-overlay {
                    position: fixed;
                    inset: 0;
                    z-index: 9999;
                    display: none;
                    align-items: center;
                    justify-content: center;
                    padding: 18px;
                    background: rgba(15, 23, 42, .48);
                    backdrop-filter: blur(5px);
                }
                .crm-nd13-overlay.is-open { display: flex; }
                .crm-nd13-dialog {
                    width: min(680px, calc(100vw - 32px));
                    max-height: min(720px, calc(100dvh - 32px));
                    display: flex;
                    flex-direction: column;
                    overflow: hidden;
                    border-radius: 18px;
                    background: #fff;
                    box-shadow: 0 28px 80px rgba(15, 23, 42, .28);
                    color: #0f172a;
                }
                .crm-nd13-header,
                .crm-nd13-footer {
                    flex: 0 0 auto;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 12px;
                    padding: 16px 18px;
                    border-color: #e2e8f0;
                }
                .crm-nd13-header { border-bottom: 1px solid #e2e8f0; }
                .crm-nd13-footer { border-top: 1px solid #e2e8f0; }
                .crm-nd13-title { margin: 0; font-size: 18px; font-weight: 800; line-height: 1.25; }
                .crm-nd13-close {
                    width: 34px;
                    height: 34px;
                    border: 1px solid #e2e8f0;
                    border-radius: 999px;
                    background: #fff;
                    color: #64748b;
                    font-size: 24px;
                    line-height: 1;
                    cursor: pointer;
                }
                .crm-nd13-body {
                    flex: 1 1 auto;
                    overflow: auto;
                    padding: 18px;
                }
                .crm-nd13-body p { margin: 0 0 14px; line-height: 1.6; }
                .crm-nd13-message {
                    margin: 14px 0;
                    padding: 14px;
                    border: 1px solid #bfdbfe;
                    border-radius: 14px;
                    background: #eff6ff;
                    color: #1e3a8a;
                    font-size: 15px;
                    font-weight: 700;
                    line-height: 1.7;
                    overflow-wrap: anywhere;
                    user-select: all;
                }
                .crm-nd13-note {
                    padding: 12px 14px;
                    border-radius: 12px;
                    background: #f8fafc;
                    color: #475569;
                    font-size: 14px;
                    line-height: 1.55;
                }
                .crm-nd13-button {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    min-height: 38px;
                    padding: 8px 14px;
                    border-radius: 10px;
                    border: 1px solid #dbe3ef;
                    background: #fff;
                    color: #334155;
                    font-weight: 700;
                    cursor: pointer;
                }
                .crm-nd13-button-primary {
                    border-color: #2563eb;
                    background: #2563eb;
                    color: #fff;
                }
                @media (max-width: 640px) {
                    .crm-nd13-overlay { padding: 8px; }
                    .crm-nd13-dialog {
                        width: calc(100vw - 16px);
                        max-height: calc(100dvh - 16px);
                        border-radius: 14px;
                    }
                    .crm-nd13-header,
                    .crm-nd13-footer,
                    .crm-nd13-body { padding: 14px; }
                    .crm-nd13-footer { flex-direction: column-reverse; align-items: stretch; }
                    .crm-nd13-button { width: 100%; }
                }
            `;
            document.head.appendChild(style);
        };

        const escapeHtml = (value) => String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');

        const ensureOverlay = () => {
            ensureStyles();

            let overlay = document.querySelector('.crm-nd13-overlay');

            if (overlay) {
                return overlay;
            }

            overlay = document.createElement('div');
            overlay.className = 'crm-nd13-overlay';
            overlay.innerHTML = `
                <section class="crm-nd13-dialog" role="dialog" aria-modal="true" aria-labelledby="crm-nd13-title">
                    <header class="crm-nd13-header">
                        <h3 id="crm-nd13-title" class="crm-nd13-title">Thông báo đồng ý NĐ13</h3>
                        <button type="button" class="crm-nd13-close" aria-label="Đóng">×</button>
                    </header>
                    <main class="crm-nd13-body"></main>
                    <footer class="crm-nd13-footer">
                        <button type="button" class="crm-nd13-button crm-nd13-copy">Copy nội dung SMS</button>
                        <button type="button" class="crm-nd13-button crm-nd13-button-primary crm-nd13-ok">Đã hiểu</button>
                    </footer>
                </section>
            `;
            document.body.appendChild(overlay);

            overlay.addEventListener('click', (event) => {
                if (event.target === overlay || event.target.closest('.crm-nd13-close, .crm-nd13-ok')) {
                    overlay.classList.remove('is-open');
                }
            });

            overlay.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    overlay.classList.remove('is-open');
                }
            });

            overlay.querySelector('.crm-nd13-copy').addEventListener('click', async () => {
                const message = overlay.dataset.message || '';
                const button = overlay.querySelector('.crm-nd13-copy');
                const oldText = button.textContent;

                try {
                    await navigator.clipboard.writeText(message);
                    button.textContent = 'Đã copy';
                    setTimeout(() => button.textContent = oldText, 1200);
                } catch (error) {
                    const temp = document.createElement('textarea');
                    temp.value = message;
                    document.body.appendChild(temp);
                    temp.select();
                    document.execCommand('copy');
                    temp.remove();
                    button.textContent = 'Đã copy';
                    setTimeout(() => button.textContent = oldText, 1200);
                }
            });

            return overlay;
        };

        const openNd13Popup = (detail = {}) => {
            const overlay = ensureOverlay();
            const title = detail.title || 'Thông báo đồng ý NĐ13';
            const message = detail.message || '';
            const suffix = detail.suffix || 'xxxxxx';
            const leadCode = detail.leadCode || '';

            overlay.dataset.message = message;
            overlay.querySelector('.crm-nd13-title').textContent = title;
            overlay.querySelector('.crm-nd13-body').innerHTML = `
                <p>Vui lòng hướng dẫn Khách hàng soạn tin nhắn nội dung sau nhằm đồng ý chia sẻ dữ liệu cá nhân với SHB Finance và các Công ty bên thứ 3 có liên quan:</p>
                <div class="crm-nd13-message">${escapeHtml(message)}</div>
                <div class="crm-nd13-note">
                    Gửi đến <strong>6088</strong>. <strong>${escapeHtml(suffix)}</strong> là 6 số đuôi của CCCD/CMND.${leadCode ? ` Lead: <strong>${escapeHtml(leadCode)}</strong>.` : ''}
                </div>
            `;
            overlay.classList.add('is-open');
            overlay.querySelector('.crm-nd13-ok')?.focus({ preventScroll: true });
        };

        window.addEventListener('crm-nd13-consent', (event) => openNd13Popup(event.detail || {}));
    })();
</script>
HTML);
    }

    protected function notificationPanelStyles(): HtmlString
    {
        return new HtmlString(<<<'HTML'
<style id="crm-notification-panel-styles">
    #database-notifications.fi-modal,
    #database-notifications.fi-modal .fi-modal-close-overlay,
    #database-notifications.fi-modal .fi-modal-window-ctn {
        z-index: 2147483000 !important;
    }

    #database-notifications.fi-modal .fi-modal-close-overlay {
        background: transparent !important;
        backdrop-filter: none !important;
    }

    #database-notifications.fi-modal .fi-modal-window-ctn {
        align-items: flex-start !important;
        justify-content: flex-end !important;
        padding: 0 !important;
        pointer-events: none !important;
    }

    #database-notifications.fi-modal .fi-modal-window {
        pointer-events: auto !important;
        position: fixed !important;
        top: calc(var(--crm-topbar-height, 72px) + 6px) !important;
        right: 14px !important;
        margin: 0 !important;
        width: min(360px, calc(100vw - 24px)) !important;
        max-width: min(360px, calc(100vw - 24px)) !important;
        min-width: 0 !important;
        height: auto !important;
        max-height: min(480px, calc(100dvh - var(--crm-topbar-height, 72px) - 18px)) !important;
        display: flex !important;
        flex-direction: column !important;
        overflow: hidden !important;
        color: #172033 !important;
        background: #fff !important;
        border: 1px solid #dce3ed !important;
        border-radius: 8px !important;
        box-shadow: 0 16px 44px rgba(15, 23, 42, .22) !important;
        transform-origin: top right !important;
    }

    #database-notifications.fi-modal .fi-modal-header {
        flex: 0 0 auto !important;
        min-height: 58px !important;
        padding: 10px 44px 9px 16px !important;
        background: #fff !important;
        border-bottom: 1px solid #e5eaf1 !important;
    }

    #database-notifications.fi-modal .fi-modal-header > div:not(.fi-modal-icon-ctn) {
        display: grid !important;
        gap: 4px !important;
        width: 100% !important;
        min-width: 0 !important;
    }

    #database-notifications.fi-modal .fi-modal-heading {
        display: flex !important;
        align-items: center !important;
        gap: 7px !important;
        margin: 0 !important;
        color: #172033 !important;
        font-size: 18px !important;
        line-height: 1.2 !important;
        font-weight: 780 !important;
    }

    #database-notifications.fi-modal .fi-modal-heading .fi-badge {
        display: grid !important;
        place-items: center !important;
        min-width: 18px !important;
        height: 18px !important;
        padding: 0 5px !important;
        color: #fff !important;
        background: #dc2626 !important;
        border: 0 !important;
        border-radius: 999px !important;
        font-size: 10px !important;
        line-height: 1 !important;
        font-weight: 800 !important;
    }

    #database-notifications.fi-modal .fi-modal-close-btn {
        position: absolute !important;
        top: 12px !important;
        right: 10px !important;
        width: 34px !important;
        height: 34px !important;
        color: #6b7b91 !important;
        border-radius: 999px !important;
    }

    #database-notifications.fi-modal .fi-modal-close-btn:hover,
    #database-notifications.fi-modal .fi-modal-close-btn:focus-visible {
        color: #2563eb !important;
        background: #eef4ff !important;
    }

    #database-notifications.fi-modal .fi-modal-close-btn .fi-icon,
    #database-notifications.fi-modal .fi-no-notification-close-btn .fi-icon {
        width: 17px !important;
        height: 17px !important;
    }

    #database-notifications.fi-modal .fi-modal-header .fi-ac {
        display: flex !important;
        align-items: center !important;
        gap: 12px !important;
        min-height: 18px !important;
        margin: 0 !important;
    }

    #database-notifications.fi-modal .fi-modal-header .fi-ac :where(a, button, .fi-btn) {
        min-height: 18px !important;
        padding: 0 !important;
        color: #64748b !important;
        background: transparent !important;
        border: 0 !important;
        border-radius: 0 !important;
        box-shadow: none !important;
        font-size: 11px !important;
        line-height: 1.2 !important;
        font-weight: 700 !important;
        text-decoration: none !important;
        white-space: nowrap !important;
    }

    #database-notifications.fi-modal .fi-modal-header .fi-ac :where(a, button, .fi-btn):hover,
    #database-notifications.fi-modal .fi-modal-header .fi-ac :where(a, button, .fi-btn):focus-visible {
        color: #2563eb !important;
    }

    #database-notifications.fi-modal .fi-modal-header .fi-ac :where(a, button, .fi-btn).fi-color-danger {
        color: #dc2626 !important;
    }

    #database-notifications.fi-modal .fi-modal-content {
        flex: 1 1 auto !important;
        width: 100% !important;
        min-width: 0 !important;
        min-height: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow-x: hidden !important;
        overflow-y: auto !important;
        overscroll-behavior: contain !important;
        scrollbar-width: thin !important;
        background: #fff !important;
    }

    #database-notifications.fi-modal .crm-notification-tabs {
        position: sticky !important;
        top: 0 !important;
        z-index: 3 !important;
        display: flex !important;
        gap: 4px !important;
        width: 100% !important;
        min-height: 44px !important;
        padding: 7px 10px !important;
        margin: 0 !important;
        overflow-x: auto !important;
        background: #fff !important;
        border-bottom: 1px solid #e5eaf1 !important;
        scrollbar-width: none !important;
    }

    #database-notifications.fi-modal .crm-notification-tabs::-webkit-scrollbar {
        display: none !important;
    }

    #database-notifications.fi-modal .crm-notification-tab {
        flex: 0 0 auto !important;
        min-height: 30px !important;
        padding: 5px 11px !important;
        color: #64748b !important;
        background: transparent !important;
        border: 0 !important;
        border-radius: 7px !important;
        font-size: 12px !important;
        line-height: 1.2 !important;
        font-weight: 750 !important;
        cursor: pointer !important;
    }

    #database-notifications.fi-modal .crm-notification-tab:hover,
    #database-notifications.fi-modal .crm-notification-tab:focus-visible {
        color: #1d4ed8 !important;
        background: #f1f5fb !important;
        outline: 0 !important;
    }

    #database-notifications.fi-modal .crm-notification-tab.is-active {
        color: #1d4ed8 !important;
        background: #eaf1ff !important;
        box-shadow: none !important;
    }

    #database-notifications.fi-modal .fi-no-notification-read-ctn,
    #database-notifications.fi-modal .fi-no-notification-unread-ctn {
        border: 0 !important;
    }

    #database-notifications.fi-modal .fi-no-notification {
        position: relative !important;
        display: grid !important;
        grid-template-columns: 44px minmax(0, 1fr) 26px !important;
        align-items: center !important;
        gap: 11px !important;
        width: 100% !important;
        min-height: 76px !important;
        padding: 9px 10px 9px 14px !important;
        color: #172033 !important;
        background: #fff !important;
        border: 0 !important;
        cursor: pointer !important;
        transition: background-color .14s ease !important;
    }

    #database-notifications.fi-modal .fi-no-notification:hover,
    #database-notifications.fi-modal .fi-no-notification:focus-visible {
        background: #f1f5fb !important;
        outline: 0 !important;
    }

    #database-notifications.fi-modal .fi-no-notification-unread-ctn .fi-no-notification {
        background: #f4f7ff !important;
    }

    #database-notifications.fi-modal .fi-no-notification-unread-ctn .fi-no-notification::after {
        position: absolute !important;
        right: 12px !important;
        bottom: 10px !important;
        width: 8px !important;
        height: 8px !important;
        content: "" !important;
        background: #2563eb !important;
        border-radius: 999px !important;
    }

    #database-notifications.fi-modal .fi-no-notification-icon {
        display: grid !important;
        place-items: center !important;
        width: 44px !important;
        height: 44px !important;
        box-sizing: border-box !important;
        margin: 0 !important;
        padding: 11px !important;
        color: #2563eb !important;
        background: #eaf1ff !important;
        border-radius: 999px !important;
    }

    #database-notifications.fi-modal .fi-no-notification-main,
    #database-notifications.fi-modal .fi-no-notification-text {
        min-width: 0 !important;
    }

    #database-notifications.fi-modal .fi-no-notification-text {
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) auto !important;
        column-gap: 8px !important;
        row-gap: 3px !important;
        align-items: center !important;
    }

    #database-notifications.fi-modal .fi-no-notification-title {
        min-width: 0 !important;
        margin: 0 !important;
        overflow: hidden !important;
        color: #172033 !important;
        font-size: 13px !important;
        line-height: 1.25 !important;
        font-weight: 750 !important;
        text-overflow: ellipsis !important;
        white-space: nowrap !important;
    }

    #database-notifications.fi-modal .fi-no-notification-date {
        grid-column: 2 !important;
        grid-row: 1 !important;
        display: block !important;
        margin: 0 !important;
        color: #7b8ba3 !important;
        font-size: 10px !important;
        line-height: 1.2 !important;
        white-space: nowrap !important;
    }

    #database-notifications.fi-modal .fi-no-notification-body {
        grid-column: 1 / -1 !important;
        min-width: 0 !important;
        max-height: 2.7em !important;
        margin: 0 !important;
        overflow: hidden !important;
        color: #6b7b91 !important;
        font-size: 12px !important;
        line-height: 1.35 !important;
        overflow-wrap: anywhere !important;
    }

    #database-notifications.fi-modal .crm-notification-category {
        display: inline-flex !important;
        align-items: center !important;
        width: fit-content !important;
        margin: 0 5px 0 0 !important;
        padding: 1px 6px !important;
        color: #1d4ed8 !important;
        background: #eaf1ff !important;
        border-radius: 999px !important;
        font-size: 10px !important;
        line-height: 1.3 !important;
        font-weight: 800 !important;
        vertical-align: 1px !important;
    }

    #database-notifications.fi-modal .fi-no-notification-actions {
        display: none !important;
    }

    #database-notifications.fi-modal .fi-no-notification-close-btn {
        align-self: center !important;
        width: 26px !important;
        height: 26px !important;
        margin: 0 !important;
        color: #94a3b8 !important;
        border-radius: 999px !important;
        opacity: .7 !important;
    }

    #database-notifications.fi-modal .fi-no-notification:hover .fi-no-notification-close-btn,
    #database-notifications.fi-modal .fi-no-notification-close-btn:focus-visible {
        color: #dc2626 !important;
        background: #fee2e2 !important;
        opacity: 1 !important;
    }

    #database-notifications.fi-modal .crm-notification-hidden {
        display: none !important;
    }

    @media (max-width: 640px) {
        #database-notifications.fi-modal .fi-modal-window {
            top: var(--crm-topbar-height, 64px) !important;
            right: 0 !important;
            left: 0 !important;
            width: 100vw !important;
            max-width: 100vw !important;
            max-height: calc(100dvh - var(--crm-topbar-height, 64px)) !important;
            border-right: 0 !important;
            border-left: 0 !important;
            border-radius: 0 !important;
            transform-origin: top center !important;
        }

        #database-notifications.fi-modal .fi-modal-window-ctn {
            justify-content: center !important;
        }
    }

    .dark #database-notifications.fi-modal .fi-modal-window,
    .dark #database-notifications.fi-modal .fi-modal-header,
    .dark #database-notifications.fi-modal .fi-modal-content,
    .dark #database-notifications.fi-modal .crm-notification-tabs,
    .dark #database-notifications.fi-modal .fi-no-notification {
        color: #e5e7eb !important;
        background: #111827 !important;
        border-color: #273449 !important;
    }

    .dark #database-notifications.fi-modal .fi-no-notification:hover,
    .dark #database-notifications.fi-modal .fi-no-notification-unread-ctn .fi-no-notification {
        background: #172033 !important;
    }

    .dark #database-notifications.fi-modal .fi-modal-heading,
    .dark #database-notifications.fi-modal .fi-no-notification-title {
        color: #f8fafc !important;
    }
</style>
HTML);
    }

    protected function notificationSoundScript(): HtmlString
    {
        $settings = UiSetting::current();
        $soundPath = is_string($settings->notification_sound_path) ? $settings->notification_sound_path : null;
        $soundConfig = json_encode([
            'preset' => in_array($settings->notification_sound, ['outlook', 'chime', 'soft', 'custom', 'off'], true)
                ? $settings->notification_sound
                : 'outlook',
            'url' => $soundPath ? asset('storage/'.$soundPath) : null,
            'volume' => max(0, min(100, (int) ($settings->notification_sound_volume ?? 80))) / 100,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        $script = <<<'HTML'
<script data-navigate-once>
    (() => {
        if (window.__crmNotificationSoundBound) {
            return;
        }

        window.__crmNotificationSoundBound = true;

        const soundConfig = __CRM_SOUND_CONFIG__;
        const storageKey = '3rdvn:last-unread-notification-count';
        let audioContext = null;
        let audioUnlocked = false;
        let customAudio = null;

        const currentUnreadCount = () => {
            const trigger = document.querySelector('.fi-topbar-database-notifications-btn');

            if (! trigger) {
                return 0;
            }

            const text = (trigger.innerText || trigger.textContent || '').replace(/\s+/g, ' ').trim();
            const numbers = text.match(/\d+/g);

            return numbers?.length ? Number.parseInt(numbers[numbers.length - 1], 10) || 0 : 0;
        };

        const unlockAudio = () => {
            if (soundConfig.preset === 'off') {
                return;
            }

            try {
                audioContext ||= new (window.AudioContext || window.webkitAudioContext)();

                if (audioContext.state === 'suspended') {
                    audioContext.resume();
                }

                audioUnlocked = true;

                if (soundConfig.preset === 'custom' && soundConfig.url && ! customAudio) {
                    customAudio = new Audio(soundConfig.url);
                    customAudio.preload = 'auto';
                    customAudio.volume = soundConfig.volume;
                    customAudio.load();
                }
            } catch (error) {
                audioUnlocked = false;
            }
        };

        const playNotificationSound = () => {
            if (soundConfig.preset === 'off' || soundConfig.volume <= 0) {
                return;
            }

            unlockAudio();

            if (soundConfig.preset === 'custom' && customAudio) {
                customAudio.currentTime = 0;
                customAudio.volume = soundConfig.volume;
                customAudio.play().catch(() => {});
                return;
            }

            if (! audioContext || ! audioUnlocked) {
                return;
            }

            const now = audioContext.currentTime;
            const gain = audioContext.createGain();
            gain.gain.setValueAtTime(0.0001, now);
            gain.gain.exponentialRampToValueAtTime(Math.max(0.0001, 0.2 * soundConfig.volume), now + 0.02);
            gain.connect(audioContext.destination);

            const tones = {
                outlook: [740, 980],
                chime: [620, 830, 1100],
                soft: [520],
            }[soundConfig.preset] || [740, 980];
            const duration = soundConfig.preset === 'soft' ? 0.28 : 0.18;

            gain.gain.exponentialRampToValueAtTime(0.0001, now + (tones.length * 0.1) + duration);

            tones.forEach((frequency, index) => {
                const osc = audioContext.createOscillator();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(frequency, now + index * 0.1);
                osc.connect(gain);
                osc.start(now + index * 0.1);
                osc.stop(now + index * 0.1 + duration);
            });
        };

        window.crmPreviewNotificationSound = playNotificationSound;

        const pushStorageKey = '3rdvn:last-native-notification-id';

        const requestPushPermission = () => {
            if ('Notification' in window && Notification.permission === 'default') {
                Notification.requestPermission().catch(() => {});
            }
        };

        const syncNativeNotification = async (announce = true) => {
            if (!('Notification' in window) || Notification.permission !== 'granted') {
                return;
            }

            try {
                const response = await fetch('/crm/notifications/latest', {
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json' },
                });

                if (! response.ok) {
                    return;
                }

                const item = (await response.json()).notification;

                if (! item?.id) {
                    return;
                }

                const previousId = window.localStorage.getItem(pushStorageKey);
                window.localStorage.setItem(pushStorageKey, item.id);

                if (! announce || previousId === null || previousId === item.id) {
                    return;
                }

                const options = {
                    body: item.body,
                    icon: '/favicon.ico',
                    badge: '/favicon.ico',
                    tag: `3rdvn-crm-${item.id}`,
                    renotify: true,
                    data: { url: item.url || '/' },
                };

                if ('serviceWorker' in navigator) {
                    const registration = await navigator.serviceWorker.ready;
                    await registration.showNotification(item.title, options);
                } else {
                    const notification = new Notification(item.title, options);
                    notification.onclick = () => window.location.assign(item.url || '/');
                }
            } catch (error) {
                // Notification polling must never interrupt CRM interactions.
            }
        };

        const syncUnreadCount = () => {
            const count = currentUnreadCount();
            const previousRaw = window.sessionStorage.getItem(storageKey);

            if (previousRaw === null) {
                window.sessionStorage.setItem(storageKey, String(count));
                return;
            }

            const previous = Number.parseInt(previousRaw, 10) || 0;

            if (count > previous) {
                playNotificationSound();
            }

            if (count !== previous) {
                window.sessionStorage.setItem(storageKey, String(count));
            }
        };

        window.crmHandleRealtimeNotification = () => {
            playNotificationSound();
            syncNativeNotification(true);
            setTimeout(syncUnreadCount, 80);
        };

        document.addEventListener('pointerdown', () => {
            unlockAudio();
            requestPushPermission();
        }, { once: true, passive: true });
        document.addEventListener('keydown', unlockAudio, { once: true });

        syncNativeNotification(false);
        setInterval(syncNativeNotification, 5000);
        setTimeout(syncUnreadCount, 250);
        setInterval(syncUnreadCount, 1500);
        document.addEventListener('livewire:navigated', () => setTimeout(syncUnreadCount, 250));
        document.addEventListener('livewire:update', () => setTimeout(syncUnreadCount, 80));
        document.addEventListener('livewire:updated', () => setTimeout(syncUnreadCount, 80));

        new MutationObserver(() => window.requestAnimationFrame(syncUnreadCount))
            .observe(document.body || document.documentElement, { childList: true, subtree: true, characterData: true });
    })();
</script>
HTML;

        return new HtmlString(str_replace('__CRM_SOUND_CONFIG__', $soundConfig, $script));
    }

    protected function notificationPanelScript(): HtmlString
    {
        return new HtmlString(<<<'HTML'
<script data-navigate-once>
    (() => {
        if (window.__crmNotificationPanelBound) {
            return;
        }

        window.__crmNotificationPanelBound = true;

        const tabStorageKey = '3rdvn:notification-active-tab';
        let selectedCategory = window.sessionStorage.getItem(tabStorageKey) || 'all';
        let scheduled = false;

        const isProfileNotification = (item) => {
            const text = (item.textContent || '').toLowerCase();

            return [
                'lead',
                'application',
                'hồ sơ',
                'ho so',
                'acl',
                'cbp',
                'dự án',
                'du an',
            ].some((keyword) => text.includes(keyword));
        };

        const categoryOf = (item) => {
            const explicit = item.querySelector('.crm-notification-category')?.dataset.category;

            if (explicit) {
                return explicit;
            }

            return isProfileNotification(item) ? 'ho-so' : 'he-thong';
        };

        const setActiveTab = (tabs, category) => {
            tabs.querySelectorAll('.crm-notification-tab').forEach((tab) => {
                tab.classList.toggle('is-active', (tab.dataset.category || 'all') === category);
            });
        };

        const applyFilter = (root, category) => {
            root.querySelectorAll('.fi-no-notification-read-ctn, .fi-no-notification-unread-ctn').forEach((item) => {
                item.classList.toggle('crm-notification-hidden', category !== 'all' && categoryOf(item) !== category);
            });
        };

        const closeDatabaseNotifications = () => {
            const modal = document.querySelector('#database-notifications.fi-modal');

            if (! modal) {
                return;
            }

            const closeButton = modal.querySelector('.fi-modal-close-btn, [aria-label="Close"], [aria-label="Đóng"]');
            closeButton?.click();
        };

        const bindOutsideClose = () => {
            if (window.__crmNotificationOutsideCloseBound) {
                return;
            }

            window.__crmNotificationOutsideCloseBound = true;

            document.addEventListener('pointerdown', (event) => {
                const modal = document.querySelector('#database-notifications.fi-modal');

                if (! modal || modal.classList.contains('hidden') || modal.getAttribute('aria-hidden') === 'true') {
                    return;
                }

                const windowEl = modal.querySelector('.fi-modal-window');
                const trigger = document.querySelector('.fi-topbar-database-notifications-btn');
                const target = event.target;

                if (windowEl?.contains(target) || trigger?.contains(target)) {
                    return;
                }

                closeDatabaseNotifications();
            }, true);
        };

        const enhanceClickableItems = (root) => {
            root.querySelectorAll('.fi-no-notification').forEach((notification) => {
                if (notification.dataset.crmClickableBound === '1') {
                    return;
                }

                const link = notification.querySelector('.fi-no-notification-actions a[href]');

                if (! link) {
                    return;
                }

                notification.dataset.crmClickableBound = '1';
                notification.setAttribute('role', 'button');
                notification.setAttribute('tabindex', '0');

                const openNotification = () => link.click();

                notification.addEventListener('click', (event) => {
                    if (event.target.closest('a, button')) {
                        return;
                    }

                    openNotification();
                });

                notification.addEventListener('keydown', (event) => {
                    if (! ['Enter', ' '].includes(event.key)) {
                        return;
                    }

                    event.preventDefault();
                    openNotification();
                });
            });
        };


        const ensureTabs = () => {
            document.querySelectorAll('.fi-no-database').forEach((root) => {
                const content = root.querySelector('.fi-modal-content');
                const hasItems = root.querySelector('.fi-no-notification-read-ctn, .fi-no-notification-unread-ctn');

                enhanceClickableItems(root);

                if (! content || ! hasItems) {
                    return;
                }

                let tabs = content.querySelector(':scope > .crm-notification-tabs');

                if (! tabs) {
                    tabs = document.createElement('div');
                    tabs.className = 'crm-notification-tabs';
                    tabs.innerHTML = `
                        <button type="button" class="crm-notification-tab" data-category="all">Tất cả</button>
                        <button type="button" class="crm-notification-tab" data-category="ho-so">Hồ sơ</button>
                        <button type="button" class="crm-notification-tab" data-category="mail">Mail</button>
                        <button type="button" class="crm-notification-tab" data-category="he-thong">Hệ thống</button>
                    `;
                    content.prepend(tabs);
                }

                tabs.querySelectorAll('.crm-notification-tab').forEach((button) => {
                    if (button.dataset.crmTabBound === '1') {
                        return;
                    }

                    button.dataset.crmTabBound = '1';
                    button.addEventListener('click', (event) => {
                        event.preventDefault();
                        event.stopPropagation();

                        selectedCategory = button.dataset.category || 'all';
                        window.sessionStorage.setItem(tabStorageKey, selectedCategory);
                        setActiveTab(tabs, selectedCategory);
                        applyFilter(root, selectedCategory);
                    });
                });

                setActiveTab(tabs, selectedCategory);
                applyFilter(root, selectedCategory);
            });
        };

        const schedule = () => {
            if (scheduled) {
                return;
            }

            scheduled = true;
            window.requestAnimationFrame(() => {
                scheduled = false;
                bindOutsideClose();
                ensureTabs();
            });
        };

        schedule();
        document.addEventListener('DOMContentLoaded', schedule);
        document.addEventListener('livewire:navigated', schedule);
        document.addEventListener('livewire:update', schedule);
        document.addEventListener('livewire:updated', schedule);

        new MutationObserver(schedule).observe(document.body || document.documentElement, {
            childList: true,
            subtree: true,
        });
    })();
</script>
HTML);
    }

    protected function pwaHead(): HtmlString
    {
        $settings = UiSetting::current();
        $theme = $this->color($settings->primary_color, '#2563eb');
        $appName = e($settings->app_name ?: '3RDVN CRM');
        $faviconPath = $settings->favicon_path ? public_path('storage/'.$settings->favicon_path) : null;
        $favicon = e($settings->favicon_path
            ? asset('storage/'.$settings->favicon_path).(is_file($faviconPath) ? '?v='.filemtime($faviconPath) : '')
            : ($settings->favicon_url ?: asset('favicon.ico').'?v='.filemtime(public_path('favicon.ico'))));

        return new HtmlString(<<<HTML
<link rel="manifest" href="/manifest.webmanifest">
<meta name="theme-color" content="{$theme}">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="{$appName}">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<link rel="apple-touch-icon" href="{$favicon}">
<link rel="icon" href="{$favicon}">
HTML);
    }

    protected function maskBrowserPathScript(): HtmlString
    {
        return new HtmlString(<<<'HTML'
<script data-navigate-once>
    (() => {
        if (window.__crmMaskBrowserPathBound) {
            return;
        }

        window.__crmMaskBrowserPathBound = true;

        const shouldSkip = () => {
            const path = window.location.pathname;

            return path.startsWith('/authen/login')
                || path.startsWith('/tro-chuyen')
                || path.startsWith('/bao-cao')
                || path.startsWith('/livewire')
                || path.startsWith('/storage')
                || path.startsWith('/build')
                || path.startsWith('/vendor');
        };

        const mask = () => {
            if (shouldSkip() || window.location.pathname === '/') {
                return;
            }

            window.history.replaceState(window.history.state, document.title, '/');
        };

        window.addEventListener('load', () => setTimeout(mask, 80));
        document.addEventListener('livewire:navigated', () => setTimeout(mask, 120));
    })();
</script>
HTML);
    }

    protected function pwaServiceWorkerScript(): HtmlString
    {
        return new HtmlString(<<<'HTML'
<script data-navigate-once>
    (() => {
        if (!('serviceWorker' in navigator)) {
            return;
        }

        const local = ['localhost', '127.0.0.1'].includes(location.hostname);

        if (!window.isSecureContext && !local) {
            return;
        }

        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js', { updateViaCache: 'none' }).then((registration) => registration.update()).catch(() => {});
        });
    })();
</script>
HTML);
    }

    protected function sidebarDefaultScript(): HtmlString
    {
        $settings = UiSetting::current();
        $isOpen = ! (bool) $settings->sidebar_default_collapsed;
        $version = (string) optional($settings->updated_at)->timestamp;

        return new HtmlString(<<<HTML
<script data-navigate-once>
    (() => {
        const version = '{$version}';
        const key = '3rdvn:sidebar-default-version';

        if (localStorage.getItem(key) === version) {
            return;
        }

        localStorage.setItem('isOpen', JSON.stringify({$this->jsBool($isOpen)}));
        localStorage.setItem('isOpenDesktop', JSON.stringify({$this->jsBool($isOpen)}));
        localStorage.setItem(key, version);
    })();
</script>
HTML);
    }

    protected function chatStyles(): HtmlString
    {
        if (! filament()->auth()->check()) {
            return new HtmlString('');
        }

        return new HtmlString(view('filament.hooks.chat-styles')->render());
    }

    protected function chatScripts(): HtmlString
    {
        if (! filament()->auth()->check()) {
            return new HtmlString('');
        }

        return new HtmlString(view('filament.hooks.chat-scripts')->render());
    }

    protected function chatLauncher(): HtmlString
    {
        if (! filament()->auth()->check()) {
            return new HtmlString('');
        }

        return new HtmlString(view('filament.hooks.chat-launcher')->render());
    }

    protected function chatAssets(): HtmlString
    {
        if (! filament()->auth()->check()) {
            return new HtmlString('');
        }

        return new HtmlString(view('filament.hooks.chat-assets')->render());
    }

    protected function topbarUserMeta(): HtmlString
    {
        $settings = UiSetting::current();

        if (! filament()->auth()->check()) {
            return new HtmlString('');
        }

        if (! $settings->show_user_role && ! $settings->show_employee_code) {
            return new HtmlString('');
        }

        $user = filament()->auth()->user();
        $name = e(filament()->getUserName($user));
        $lines = [];

        if ($settings->show_employee_code && filled($user->uid ?? $user->employee_code ?? null)) {
            $lines[] = 'UID '.($user->uid ?? $user->employee_code);
        }

        if ($settings->show_user_role && method_exists($user, 'getRoleNames')) {
            $role = $user->getRoleNames()->first();

            if (filled($role)) {
                $lines[] = $role;
            }
        }

        $meta = filled($lines) ? '<span>'.e(collect($lines)->join(' · ')).'</span>' : '';

        return new HtmlString('<div class="fi-topbar-user-meta"><strong>'.$name.'</strong><br>'.$meta.'</div>');
    }

    protected function px(int|string $value): string
    {
        return max(52, min(320, (int) $value)).'px';
    }

    protected function fontFamilyName(?string $value): string
    {
        $value = trim((string) $value);

        return match (true) {
            str_contains($value, 'Be Vietnam Pro') => 'Be Vietnam Pro',
            str_contains($value, 'IBM Plex Sans') => 'IBM Plex Sans',
            str_contains($value, 'Source Sans 3') => 'Source Sans 3',
            str_contains($value, 'Noto Sans') => 'Noto Sans',
            str_contains($value, 'Roboto') => 'Roboto',
            str_contains($value, 'Manrope') => 'Manrope',
            str_contains($value, 'system') || str_contains($value, 'Segoe UI') => 'Inter',
            default => $value !== '' ? str_replace('"', '', $value) : 'Inter',
        };
    }

    protected function fontStack(?string $value): string
    {
        $family = $this->fontFamilyName($value);

        return match ($family) {
            'Be Vietnam Pro' => '"Be Vietnam Pro", "Inter", ui-sans-serif, system-ui, sans-serif',
            'IBM Plex Sans' => '"IBM Plex Sans", "Inter", ui-sans-serif, system-ui, sans-serif',
            'Source Sans 3' => '"Source Sans 3", "Inter", ui-sans-serif, system-ui, sans-serif',
            'Noto Sans' => '"Noto Sans", "Inter", ui-sans-serif, system-ui, sans-serif',
            'Roboto' => 'Roboto, "Inter", Arial, sans-serif',
            'Manrope' => 'Manrope, "Inter", ui-sans-serif, system-ui, sans-serif',
            default => 'Inter, ui-sans-serif, system-ui, sans-serif',
        };
    }

    protected function color(?string $value, string $fallback): string
    {
        return preg_match('/^#[0-9a-fA-F]{6}$/', (string) $value) ? $value : $fallback;
    }

    protected function jsBool(bool $value): string
    {
        return $value ? 'true' : 'false';
    }
}
