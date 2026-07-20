<?php

namespace App\Filament\Resources\UiSettings\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UiSettingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Branding')->schema([
                    TextEntry::make('app_name')->label('Tên CRM'),
                    TextEntry::make('logo_text')->label('Logo text')->placeholder('-'),
                    ImageEntry::make('logo_path')->label('Logo')->disk('public')->height(56)->placeholder('-'),
                    ImageEntry::make('favicon_path')->label('Favicon')->disk('public')->height(32)->placeholder('-'),
                ])->columns(2),
                Section::make('Login')->schema([
                    TextEntry::make('login_title')->label('Tiêu đề')->placeholder('-'),
                    TextEntry::make('login_subtitle')->label('Mô tả')->placeholder('-'),
                    TextEntry::make('login_layout')->label('Bố cục'),
                ])->columns(2),
                Section::make('Hiển thị')->schema([
                    TextEntry::make('primary_color')->label('Màu chính'),
                    TextEntry::make('background_color')->label('Nền'),
                    IconEntry::make('show_search')->label('Tìm kiếm')->boolean(),
                    IconEntry::make('show_notifications')->label('Thông báo')->boolean(),
                    IconEntry::make('show_user_role')->label('Role')->boolean(),
                    IconEntry::make('show_employee_code')->label('Mã nhân viên')->boolean(),
                ])->columns(3),
                Section::make('Mail/OTP')->schema([
                    IconEntry::make('smtp_enabled')->label('SMTP gửi thật')->boolean(),
                    TextEntry::make('smtp_host')->label('SMTP Host')->placeholder('-'),
                    TextEntry::make('mail_from_address')->label('Email gửi đi')->placeholder('-'),
                    TextEntry::make('password_reset_mail_subject')->label('Tiêu đề OTP')->placeholder('-'),
                ])->columns(2),
            ]);
    }
}
