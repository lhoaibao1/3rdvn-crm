<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class UiSetting extends Model
{
    protected $fillable = [
        'app_name', 'logo_text', 'logo_path', 'favicon_path', 'favicon_url', 'login_title', 'login_subtitle',
        'primary_color', 'secondary_color', 'background_color', 'surface_color', 'sidebar_color',
        'sidebar_active_color', 'text_color', 'muted_text_color', 'border_color',
        'font_family', 'radius', 'density',
        'login_background_type', 'login_background_color', 'login_background_image', 'login_layout',
        'sidebar_width', 'sidebar_collapsed_width', 'sidebar_default_collapsed', 'sidebar_style',
        'topbar_height', 'topbar_sticky', 'show_search', 'show_notifications', 'show_user_role',
        'show_employee_code', 'notification_sound', 'notification_sound_path', 'notification_sound_volume',
        'dashboard_layout', 'dashboard_widgets',
        'sidebar_width_expanded', 'sidebar_width_collapsed',
        'smtp_enabled', 'smtp_host', 'smtp_port', 'smtp_encryption', 'smtp_username', 'smtp_password',
        'mail_from_address', 'mail_from_name', 'password_reset_mail_subject', 'password_reset_mail_body',
        'mail_module_title', 'mail_module_subtitle', 'mail_module_accent', 'mail_show_user_meta',
        'mail_compact_mode', 'mail_user_meta_fields',
    ];

    protected $casts = [
        'sidebar_default_collapsed' => 'boolean',
        'topbar_sticky' => 'boolean',
        'show_search' => 'boolean',
        'show_notifications' => 'boolean',
        'show_user_role' => 'boolean',
        'show_employee_code' => 'boolean',
        'notification_sound_volume' => 'integer',
        'dashboard_widgets' => 'array',
        'smtp_enabled' => 'boolean',
        'smtp_port' => 'integer',
        'smtp_password' => 'encrypted',
        'mail_show_user_meta' => 'boolean',
        'mail_compact_mode' => 'boolean',
        'mail_user_meta_fields' => 'array',
    ];

    public static function current(): self
    {
        if (! Schema::hasTable((new static)->getTable())) {
            return new static(static::defaults());
        }

        return static::query()->first() ?? new static(static::defaults());
    }

    public static function defaults(): array
    {
        return [
            'app_name' => '3RDVN CRM',
            'logo_text' => '3RDVN CRM',
            'logo_path' => null,
            'favicon_path' => null,
            'favicon_url' => null,
            'login_title' => 'Đăng nhập 3RDVN CRM',
            'login_subtitle' => 'Hệ thống CRM nội bộ',
            'primary_color' => '#2563eb',
            'secondary_color' => '#64748b',
            'background_color' => '#f7f8fb',
            'surface_color' => '#ffffff',
            'sidebar_color' => '#ffffff',
            'sidebar_active_color' => '#2563eb',
            'text_color' => '#101828',
            'muted_text_color' => '#667085',
            'border_color' => '#e5e7eb',
            'font_family' => 'Inter, ui-sans-serif, system-ui',
            'radius' => 14,
            'density' => 'comfortable',
            'login_background_type' => 'solid',
            'login_background_color' => '#f7f8fb',
            'login_background_image' => null,
            'login_layout' => 'split',
            'sidebar_width' => 260,
            'sidebar_collapsed_width' => 76,
            'sidebar_default_collapsed' => false,
            'sidebar_style' => 'light',
            'topbar_height' => 72,
            'topbar_sticky' => true,
            'show_search' => true,
            'show_notifications' => false,
            'show_user_role' => true,
            'show_employee_code' => true,
            'notification_sound' => 'outlook',
            'notification_sound_path' => null,
            'notification_sound_volume' => 80,
            'dashboard_layout' => 'default',
            'dashboard_widgets' => ['profiles', 'leads', 'approvals', 'api'],
            'sidebar_width_expanded' => 232,
            'sidebar_width_collapsed' => 68,
            'smtp_enabled' => false,
            'smtp_host' => null,
            'smtp_port' => 587,
            'smtp_encryption' => 'tls',
            'smtp_username' => null,
            'smtp_password' => null,
            'mail_from_address' => null,
            'mail_from_name' => '3RDVN CRM',
            'password_reset_mail_subject' => 'OTP đặt lại mật khẩu 3RDVN CRM',
            'mail_module_title' => 'Mail',
            'mail_module_subtitle' => 'Email doanh nghiệp 3RDVN',
            'mail_module_accent' => '#2563eb',
            'mail_show_user_meta' => true,
            'mail_compact_mode' => false,
            'mail_user_meta_fields' => ['uid', 'employee_code', 'role', 'department', 'company', 'branch'],
            'password_reset_mail_body' => "Xin chào {{name}},

Mã OTP đặt lại mật khẩu {{app_name}} của bạn là: {{otp}}
OTP có hiệu lực trong {{ttl}} phút.

Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.",
        ];
    }
}
