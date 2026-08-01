<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class UserFormLayoutTest extends TestCase
{
    private const SHARED_SECTIONS = [
        'Thông tin người dùng',
        'Dự án & kênh bán hàng',
        'Quản lý trực tiếp',
        'Địa chỉ & liên hệ',
        'Ngân hàng, thuế & bảo hiểm',
        'Hộp thư 3RDVN',
        'Phân quyền hệ thống',
    ];

    public function test_user_create_and_edit_match_application_section_layout(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2).'/app/Filament/Resources/Users/Schemas/UserForm.php',
        );

        $this->assertStringContainsString('SearchableSelect as Select', $source);
        $this->assertStringContainsString('->columns(1)', $source);
        $this->assertStringNotContainsString('->columns(12)', $source);
        $this->assertStringNotContainsString('Tabs::make', $source);
        $this->assertStringNotContainsString('Tab::make', $source);

        foreach (self::SHARED_SECTIONS as $section) {
            $this->assertStringContainsString("Section::make('{$section}')", $source);
        }
    }

    public function test_user_view_matches_form_sections_without_a_custom_header(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2).'/app/Filament/Resources/Users/Schemas/UserInfolist.php',
        );

        $this->assertStringContainsString('->columns(1)', $source);
        $this->assertStringNotContainsString('->columns(12)', $source);
        $this->assertStringNotContainsString('Tabs::make', $source);
        $this->assertStringNotContainsString('Tab::make', $source);
        $this->assertStringNotContainsString('user_record_view_header', $source);
        $this->assertStringNotContainsString('RecordViewChrome', $source);

        foreach (self::SHARED_SECTIONS as $section) {
            $this->assertStringContainsString("Section::make('{$section}')", $source);
        }

        $this->assertStringContainsString("Section::make('Nhật ký chỉnh sửa')", $source);
    }

    public function test_user_pages_use_application_style_page_actions_and_heading(): void
    {
        $root = dirname(__DIR__, 2).'/app/Filament/Resources/Users/Pages/';
        $create = file_get_contents($root.'CreateUser.php');
        $edit = file_get_contents($root.'EditUser.php');
        $view = file_get_contents($root.'ViewUser.php');

        $this->assertStringContainsString('getCreateFormAction', $create);
        $this->assertStringNotContainsString('getFormActions', $create);
        $this->assertStringContainsString('getSaveFormAction', $edit);
        $this->assertStringNotContainsString("Action::make('saveUser')", $edit);
        $this->assertStringContainsString('return $this->record->name', $view);
        $this->assertStringNotContainsString('getHeading()', $view);
        $this->assertStringNotContainsString('getBreadcrumbs()', $view);
    }
}
