<style>
    :root {
        --crm-primary: {{ $settings->primary_color ?? '#2563eb' }};
        --crm-secondary: {{ $settings->secondary_color ?? '#64748b' }};
        --crm-bg: {{ $settings->background_color ?? '#f7f8fb' }};
        --crm-surface: {{ $settings->surface_color ?? '#ffffff' }};
        --crm-sidebar: {{ $settings->sidebar_color ?? '#ffffff' }};
        --crm-sidebar-active: {{ $settings->sidebar_active_color ?? '#2563eb' }};
        --crm-text: {{ $settings->text_color ?? '#101828' }};
        --crm-muted: {{ $settings->muted_text_color ?? '#667085' }};
        --crm-border: {{ $settings->border_color ?? '#e5e7eb' }};
        --crm-radius: {{ $settings->radius ?? 14 }}px;
        --crm-font: {{ $settings->font_family ?? 'Inter, ui-sans-serif, system-ui' }};
        --crm-sidebar-width: {{ $settings->sidebar_width ?? $settings->sidebar_width_expanded ?? 260 }}px;
        --crm-sidebar-collapsed: {{ $settings->sidebar_collapsed_width ?? $settings->sidebar_width_collapsed ?? 76 }}px;
        --crm-topbar-height: {{ $settings->topbar_height ?? 72 }}px;
    }
</style>
