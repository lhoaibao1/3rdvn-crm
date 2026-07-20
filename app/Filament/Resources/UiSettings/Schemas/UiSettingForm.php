<?php

namespace App\Filament\Resources\UiSettings\Schemas;

use App\Forms\Components\SearchableSelect as Select;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class UiSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Tabs::make('Cài đặt giao diện')
                    ->columnSpanFull()
                    ->persistTabInQueryString('settings')
                    ->tabs([
                        Tab::make('Branding')
                            ->columns(12)
                            ->schema([
                                Section::make('Logo, tên CRM, favicon')
                                    ->description('Chỉ sửa phần nhận diện hệ thống ở đây.')
                                    ->columnSpan(7)
                                    ->schema([
                                        TextInput::make('app_name')
                                            ->label('Tên CRM')
                                            ->required()
                                            ->live(debounce: 300)
                                            ->default('3RDVN CRM'),
                                        TextInput::make('logo_text')
                                            ->label('Tên ngắn')
                                            ->live(debounce: 300)
                                            ->placeholder('3RDVN CRM'),
                                        Grid::make(2)->schema([
                                            FileUpload::make('logo_path')
                                                ->label('Logo')
                                                ->disk('public')
                                                ->directory('branding')
                                                ->image()
                                                ->imagePreviewHeight('72')
                                                ->openable()
                                                ->downloadable()
                                                ->deleteUploadedFileUsing(fn (string|TemporaryUploadedFile $file) => self::deleteUploadedFile($file))
                                                ->live(),
                                            FileUpload::make('favicon_path')
                                                ->label('Favicon')
                                                ->disk('public')
                                                ->directory('branding')
                                                ->image()
                                                ->imagePreviewHeight('48')
                                                ->openable()
                                                ->downloadable()
                                                ->deleteUploadedFileUsing(fn (string|TemporaryUploadedFile $file) => self::deleteUploadedFile($file))
                                                ->live(),
                                        ]),
                                    ]),
                                Section::make('Preview')
                                    ->description('Xem phần nhận diện đang chọn.')
                                    ->columnSpan(5)
                                    ->schema([
                                        Placeholder::make('preview')
                                            ->hiddenLabel()
                                            ->content(fn (Get $get, $record): HtmlString => self::brandingPreview($get, $record)),
                                    ]),
                            ]),

                        Tab::make('Login')
                            ->columns(12)
                            ->schema([
                                Section::make('Trang đăng nhập')
                                    ->description('Chỉ sửa nội dung, bố cục và hình nền login.')
                                    ->columnSpan(7)
                                    ->schema([
                                        TextInput::make('login_title')->label('Tiêu đề login')->live(debounce: 300),
                                        Textarea::make('login_subtitle')->label('Mô tả login')->rows(3)->live(debounce: 300),
                                        Select::make('login_layout')
                                            ->label('Bố cục')
                                            ->options(['split' => 'Chia 2 cột', 'centered' => 'Form giữa màn hình'])
                                            ->live()
                                            ->default('split'),
                                        Select::make('login_background_type')
                                            ->label('Loại background')
                                            ->options(['solid' => 'Màu nền', 'image' => 'Hình ảnh'])
                                            ->live()
                                            ->default('solid'),
                                        ColorPicker::make('login_background_color')->label('Màu nền login')->live()->default('#f7f8fb'),
                                        FileUpload::make('login_background_image')
                                            ->label('Hình nền login')
                                            ->disk('public')
                                            ->directory('branding/login')
                                            ->image()
                                            ->imagePreviewHeight('120')
                                            ->openable()
                                            ->deleteUploadedFileUsing(fn (string|TemporaryUploadedFile $file) => self::deleteUploadedFile($file))
                                            ->live(),
                                    ]),
                                Section::make('Preview login')
                                    ->columnSpan(5)
                                    ->schema([
                                        Placeholder::make('login_preview')
                                            ->hiddenLabel()
                                            ->content(fn (Get $get): HtmlString => self::loginPreview($get)),
                                    ]),
                            ]),

                        Tab::make('Theme')
                            ->columns(12)
                            ->schema([
                                Section::make('Font cho CRM')
                                    ->description('Font gọn, rõ số, hợp bảng dữ liệu và form nhập liệu.')
                                    ->columnSpan(5)
                                    ->schema([
                                        Select::make('font_family')
                                            ->label('Font chữ')
                                            ->options(self::fontOptions())
                                            ->required()
                                            ->native(false)
                                            ->live()
                                            ->default('Inter'),
                                        Placeholder::make('font_preview')
                                            ->label('Preview font đang chọn')
                                            ->content(fn (Get $get): HtmlString => self::fontPreview($get('font_family'))),
                                    ]),
                                Section::make('Màu sắc và mật độ')
                                    ->columnSpan(7)
                                    ->schema([
                                        Grid::make(2)->schema([
                                            ColorPicker::make('primary_color')->label('Màu chính')->required()->live()->default('#2563eb'),
                                            ColorPicker::make('secondary_color')->label('Màu phụ')->required()->live()->default('#64748b'),
                                            ColorPicker::make('background_color')->label('Nền')->required()->live()->default('#f7f8fb'),
                                            ColorPicker::make('surface_color')->label('Bề mặt')->required()->live()->default('#ffffff'),
                                            ColorPicker::make('text_color')->label('Màu chữ')->required()->live()->default('#101828'),
                                            ColorPicker::make('border_color')->label('Màu viền')->required()->live()->default('#e5e7eb'),
                                        ]),
                                        Grid::make(2)->schema([
                                            TextInput::make('radius')->label('Bo góc')->numeric()->required()->live(debounce: 300)->default(14),
                                            Select::make('density')->label('Mật độ')->options(['compact' => 'Gọn', 'comfortable' => 'Vừa', 'spacious' => 'Rộng'])->live()->default('comfortable'),
                                        ]),
                                    ]),
                            ]),

                        Tab::make('Sidebar')
                            ->columns(12)
                            ->schema([
                                Section::make('Sidebar')
                                    ->description('Chỉ sửa menu trái và trạng thái thu gọn.')
                                    ->columnSpan(6)
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('sidebar_width')->label('Rộng khi mở')->numeric()->live(debounce: 300)->default(232),
                                            TextInput::make('sidebar_collapsed_width')->label('Rộng khi thu gọn')->numeric()->live(debounce: 300)->default(56),
                                        ]),
                                        Toggle::make('sidebar_default_collapsed')->label('Mặc định thu gọn')->live(),
                                        Select::make('sidebar_style')->label('Kiểu sidebar')->options(['light' => 'Sáng', 'dark' => 'Tối'])->live()->default('light'),
                                    ]),
                                Section::make('Preview sidebar')
                                    ->columnSpan(6)
                                    ->schema([
                                        Placeholder::make('sidebar_preview')
                                            ->hiddenLabel()
                                            ->content(fn (Get $get): HtmlString => self::sidebarPreview($get)),
                                    ]),
                            ]),

                        Tab::make('Topbar')
                            ->columns(12)
                            ->schema([
                                Section::make('Topbar')
                                    ->description('Chỉ sửa thanh trên cùng và thông tin user.')
                                    ->columnSpan(6)
                                    ->schema([
                                        TextInput::make('topbar_height')->label('Chiều cao topbar')->numeric()->live(debounce: 300)->default(60),
                                        Toggle::make('topbar_sticky')->label('Ghim topbar')->live()->default(true),
                                        Toggle::make('show_search')->label('Hiện ô tìm kiếm')->live()->default(true),
                                        Toggle::make('show_notifications')->label('Hiện thông báo')->live()->default(true),
                                        Select::make('notification_sound')
                                            ->label('Âm thanh thông báo')
                                            ->options([
                                                'outlook' => 'Outlook',
                                                'chime' => 'Chuông',
                                                'soft' => 'Nhẹ',
                                                'custom' => 'Âm thanh tải lên',
                                                'off' => 'Tắt âm thanh',
                                            ])
                                            ->native(false)
                                            ->live()
                                            ->default('outlook'),
                                        FileUpload::make('notification_sound_path')
                                            ->label('Tệp âm thanh')
                                            ->disk('public')
                                            ->directory('notification-sounds')
                                            ->acceptedFileTypes([
                                                'audio/mpeg',
                                                'audio/mp4',
                                                'audio/ogg',
                                                'audio/wav',
                                                'audio/x-wav',
                                            ])
                                            ->maxSize(5120)
                                            ->openable()
                                            ->downloadable()
                                            ->deleteUploadedFileUsing(fn (string|TemporaryUploadedFile $file) => self::deleteUploadedFile($file))
                                            ->visible(fn (Get $get): bool => $get('notification_sound') === 'custom'),
                                        TextInput::make('notification_sound_volume')
                                            ->label('Âm lượng (%)')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->default(80),
                                        Toggle::make('show_user_role')->label('Hiện role user')->live()->default(true),
                                        Toggle::make('show_employee_code')->label('Hiện mã nhân viên')->live()->default(true),
                                    ]),
                                Section::make('Preview topbar')
                                    ->columnSpan(6)
                                    ->schema([
                                        Placeholder::make('topbar_preview')
                                            ->hiddenLabel()
                                            ->content(fn (Get $get): HtmlString => self::topbarPreview($get)),
                                    ]),
                            ]),

                        Tab::make('Mail/OTP')
                            ->columns(12)
                            ->schema([
                                Section::make('Module Mail trong CRM')
                                    ->description('Cấu hình phần nhận diện và thông tin nhân viên hiển thị trong Mail.')
                                    ->columnSpanFull()
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('mail_module_title')
                                            ->label('Tiêu đề module')
                                            ->default('Mail'),
                                        TextInput::make('mail_module_subtitle')
                                            ->label('Mô tả ngắn')
                                            ->default('Email doanh nghiệp 3RDVN'),
                                        ColorPicker::make('mail_module_accent')
                                            ->label('Màu nhấn')
                                            ->default('#2563eb')
                                            ->live(),
                                        Toggle::make('mail_show_user_meta')
                                            ->label('Hiện thông tin nhân viên từ CRM')
                                            ->default(true)
                                            ->live(),
                                        Toggle::make('mail_compact_mode')
                                            ->label('Chế độ hiển thị gọn')
                                            ->default(false)
                                            ->live(),
                                        Select::make('mail_user_meta_fields')
                                            ->label('Thông tin nhân viên hiển thị')
                                            ->multiple()
                                            ->native(false)
                                            ->searchable()
                                            ->options([
                                                'uid' => 'UID',
                                                'employee_code' => 'Mã nhân viên',
                                                'role' => 'Vai trò',
                                                'department' => 'Phòng ban',
                                                'position' => 'Chức danh',
                                                'company' => 'Công ty',
                                                'branch' => 'Chi nhánh',
                                                'office' => 'Văn phòng',
                                            ])
                                            ->default(['uid', 'employee_code', 'role', 'department', 'company', 'branch'])
                                            ->helperText('Tên, avatar và địa chỉ mail luôn lấy trực tiếp từ bảng người dùng.'),
                                    ]),
                                Section::make('SMTP gửi mail doanh nghiệp')
                                    ->description('Điều khiển relay gửi mail ra ngoài cho cả Module Mail và OTP. Mật khẩu hiện tại được giữ trên máy chủ mail; chỉ nhập khi cần thay đổi.')
                                    ->columnSpan(6)
                                    ->schema([
                                        Toggle::make('smtp_enabled')
                                            ->label('Bật SMTP relay gửi ra ngoài')
                                            ->live()
                                            ->default(false),
                                        Grid::make(2)->schema([
                                            TextInput::make('smtp_host')
                                                ->label('SMTP Host')
                                                ->placeholder('smtp.domain.com')
                                                ->required(fn (Get $get): bool => (bool) $get('smtp_enabled')),
                                            TextInput::make('smtp_port')
                                                ->label('SMTP Port')
                                                ->numeric()
                                                ->default(587)
                                                ->required(fn (Get $get): bool => (bool) $get('smtp_enabled')),
                                            Select::make('smtp_encryption')
                                                ->label('Bảo mật')
                                                ->options(['tls' => 'TLS', 'ssl' => 'SSL', 'none' => 'Không mã hoá'])
                                                ->native(false)
                                                ->default('tls'),
                                            TextInput::make('smtp_username')
                                                ->label('SMTP Username')
                                                ->required(fn (Get $get): bool => (bool) $get('smtp_enabled')),
                                            TextInput::make('smtp_password')
                                                ->label('SMTP Password')
                                                ->password()
                                                ->revealable()
                                                ->afterStateHydrated(fn (TextInput $component) => $component->state(''))
                                                ->dehydrated(fn (?string $state): bool => filled($state))
                                                ->placeholder('Đã lưu trên máy chủ - để trống nếu không đổi'),
                                            TextInput::make('mail_from_address')
                                                ->label('Email hệ thống/OTP')
                                                ->email()
                                                ->required(fn (Get $get): bool => (bool) $get('smtp_enabled')),
                                            TextInput::make('mail_from_name')
                                                ->label('Tên người gửi')
                                                ->default('3RDVN CRM'),
                                        ]),
                                    ]),
                                Section::make('Nội dung OTP')
                                    ->description('Biến dùng được: {{app_name}}, {{name}}, {{email}}, {{uid}}, {{employee_code}}, {{otp}}, {{ttl}}.')
                                    ->columnSpan(6)
                                    ->schema([
                                        TextInput::make('password_reset_mail_subject')
                                            ->label('Tiêu đề mail')
                                            ->default('OTP đặt lại mật khẩu 3RDVN CRM')
                                            ->required(),
                                        Textarea::make('password_reset_mail_body')
                                            ->label('Nội dung mail')
                                            ->rows(11)
                                            ->default('Xin chào {{name}},

Mã OTP đặt lại mật khẩu {{app_name}} của bạn là: {{otp}}
OTP có hiệu lực trong {{ttl}} phút.

Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.')
                                            ->required(),
                                        Placeholder::make('mail_template_preview')
                                            ->label('Preview')
                                            ->content(fn (Get $get): HtmlString => self::mailTemplatePreview($get)),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    private static function fontOptions(): array
    {
        return [
            'Inter' => 'Inter - khuyên dùng cho CRM',
            'IBM Plex Sans' => 'IBM Plex Sans - rõ chữ, hợp bảng dữ liệu',
            'Source Sans 3' => 'Source Sans 3 - nhẹ, dễ đọc nhiều dòng',
            'Noto Sans' => 'Noto Sans - tiếng Việt ổn định',
            'Roboto' => 'Roboto - sạch, quen thuộc',
        ];
    }

    private static function deleteUploadedFile(string|TemporaryUploadedFile $file): bool
    {
        if ($file instanceof TemporaryUploadedFile) {
            return $file->delete();
        }

        return Storage::disk('public')->delete($file);
    }

    private static function fontStack(?string $font): string
    {
        return match ($font ?: 'Inter') {
            'IBM Plex Sans' => '"IBM Plex Sans", Inter, ui-sans-serif, system-ui, sans-serif',
            'Source Sans 3' => '"Source Sans 3", Inter, ui-sans-serif, system-ui, sans-serif',
            'Noto Sans' => '"Noto Sans", Inter, ui-sans-serif, system-ui, sans-serif',
            'Roboto' => 'Roboto, Inter, Arial, sans-serif',
            default => 'Inter, ui-sans-serif, system-ui, sans-serif',
        };
    }

    private static function fontPreview(?string $selected = null): HtmlString
    {
        $font = $selected ?: 'Inter';
        $family = self::fontStack($font);

        return new HtmlString('<div style="border:1px solid #dbe3ef;border-radius:14px;padding:18px;background:#fff;font-family:'.$family.';box-shadow:0 1px 2px rgba(15,23,42,.06)"><div style="font-size:12px;font-weight:700;color:#2563eb;text-transform:uppercase;letter-spacing:.04em">'.$font.'</div><div style="margin-top:8px;font-size:22px;font-weight:750;color:#0f172a">Danh sách hồ sơ CRM</div><div style="margin-top:10px;display:grid;grid-template-columns:1.1fr .8fr .8fr;gap:8px;font-size:13px;color:#475569"><strong style="color:#111827">Khách hàng</strong><strong style="color:#111827">Trạng thái</strong><strong style="color:#111827">Số tiền</strong><span>Nguyễn Văn An</span><span>Đang xử lý</span><span>128,500,000</span><span>Trần Thị Minh</span><span>Chờ duyệt</span><span>42,000,000</span></div></div>');
    }

    private static function brandingPreview(Get $get, $record): HtmlString
    {
        $appName = e($get('app_name') ?: $record?->app_name ?: '3RDVN CRM');
        $logoPath = $get('logo_path') ?: $record?->logo_path;
        $faviconPath = $get('favicon_path') ?: $record?->favicon_path;
        $logo = is_string($logoPath) ? asset('storage/'.$logoPath) : null;
        $favicon = is_string($faviconPath) ? asset('storage/'.$faviconPath) : null;
        $logoHtml = $logo ? '<img src="'.$logo.'" style="height:42px;max-width:180px;object-fit:contain;border-radius:8px">' : '<div style="height:42px;width:42px;border-radius:12px;background:#2563eb;color:white;display:grid;place-items:center;font-weight:800">3</div>';
        $faviconHtml = $favicon ? '<img src="'.$favicon.'" style="height:24px;width:24px;object-fit:contain;border-radius:5px">' : '<div style="height:24px;width:24px;border-radius:6px;background:#2563eb"></div>';

        return new HtmlString('<div style="border:1px solid #e5e7eb;border-radius:14px;padding:18px;background:white"><div style="display:flex;align-items:center;gap:12px">'.$logoHtml.'<strong style="font-size:16px">'.$appName.'</strong></div><div style="margin-top:16px;display:flex;align-items:center;gap:8px;color:#64748b">'.$faviconHtml.'<span>Favicon trình duyệt</span></div></div>');
    }

    private static function loginPreview(Get $get): HtmlString
    {
        $title = e($get('login_title') ?: 'Đăng nhập 3RDVN CRM');
        $subtitle = e($get('login_subtitle') ?: 'Hệ thống CRM nội bộ');
        $color = e($get('login_background_color') ?: '#f7f8fb');

        return new HtmlString('<div style="border:1px solid #e5e7eb;border-radius:16px;background:'.$color.';padding:18px"><div style="background:white;border-radius:14px;padding:18px;max-width:320px"><div style="font-size:20px;font-weight:750;color:#0f172a">'.$title.'</div><div style="margin-top:6px;color:#64748b;font-size:13px">'.$subtitle.'</div><div style="height:36px;background:#f1f5f9;border-radius:9px;margin-top:14px"></div><div style="height:36px;background:#f1f5f9;border-radius:9px;margin-top:8px"></div><div style="height:38px;background:#2563eb;border-radius:9px;margin-top:12px"></div></div></div>');
    }

    private static function sidebarPreview(Get $get): HtmlString
    {
        $open = (int) ($get('sidebar_width') ?: 232);
        $closed = (int) ($get('sidebar_collapsed_width') ?: 56);

        return new HtmlString('<div style="display:flex;gap:12px;align-items:stretch"><div style="width:'.min($open, 260).'px;border:1px solid #e5e7eb;border-radius:14px;padding:12px;background:white"><strong>Sidebar mở</strong><div style="margin-top:12px;color:#475569;font-size:13px">Hồ sơ<br>Phê duyệt<br>API Mapping<br>Phân quyền</div></div><div style="width:'.max($closed, 44).'px;border:1px solid #e5e7eb;border-radius:14px;padding:12px;background:white;text-align:center"><strong>☰</strong><div style="margin-top:12px;color:#94a3b8">•<br>•<br>•</div></div></div>');
    }

    private static function mailTemplatePreview(Get $get): HtmlString
    {
        $subject = e($get('password_reset_mail_subject') ?: 'OTP đặt lại mật khẩu 3RDVN CRM');
        $body = (string) ($get('password_reset_mail_body') ?: 'Xin chào {{name}},

Mã OTP đặt lại mật khẩu {{app_name}} của bạn là: {{otp}}');
        $body = strtr($body, [
            '{{app_name}}' => e($get('app_name') ?: '3RDVN CRM'),
            '{{name}}' => 'Nguyễn Văn A',
            '{{email}}' => 'user@example.com',
            '{{uid}}' => 'UID26070001',
            '{{employee_code}}' => 'E0001',
            '{{otp}}' => '123456',
            '{{ttl}}' => '10',
        ]);

        return new HtmlString('<div style="border:1px solid #dbe3ef;border-radius:14px;background:white;padding:16px"><div style="font-weight:800;color:#0f172a">'.$subject.'</div><pre style="white-space:pre-wrap;margin:12px 0 0;color:#475569;font-family:Inter,ui-sans-serif,system-ui;font-size:13px;line-height:1.55">'.e($body).'</pre></div>');
    }

    private static function topbarPreview(Get $get): HtmlString
    {
        $height = (int) ($get('topbar_height') ?: 60);
        $search = $get('show_search') ? '<div style="height:30px;width:160px;border-radius:999px;background:#f1f5f9"></div>' : '';
        $noti = $get('show_notifications') ? '<div style="height:30px;width:30px;border-radius:999px;background:#eff6ff;color:#2563eb;display:grid;place-items:center">!</div>' : '';

        return new HtmlString('<div style="height:'.$height.'px;border:1px solid #e5e7eb;border-radius:14px;background:white;display:flex;align-items:center;gap:12px;padding:0 14px"><strong style="color:#2563eb">3</strong>'.$search.'<div style="margin-left:auto"></div>'.$noti.'<div style="font-size:13px;text-align:right"><strong>Admin User</strong><br><span style="color:#64748b">UID001 · Admin</span></div></div>');
    }
}
