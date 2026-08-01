<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class UserFormLayoutTest extends TestCase
{
    public function test_user_create_and_edit_use_one_page_section_layout(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2).'/app/Filament/Resources/Users/Schemas/UserForm.php',
        );

        $this->assertStringContainsString('SearchableSelect as Select', $source);
        $this->assertStringContainsString('->columns(12)', $source);
        $this->assertStringNotContainsString('Tabs::make', $source);
        $this->assertStringNotContainsString('Tab::make', $source);

        foreach ([
            'Thông tin chính',
            'Công việc',
            'Dự án bán hàng',
            'Kênh',
            'Quản lý trực tiếp',
            'Địa chỉ hiện tại',
            'Tài khoản nhận lương',
            'Vai trò',
        ] as $section) {
            $this->assertStringContainsString("Section::make('{$section}')", $source);
        }
    }

    public function test_user_view_uses_the_same_one_page_sections(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2).'/app/Filament/Resources/Users/Schemas/UserInfolist.php',
        );

        $this->assertStringContainsString('->columns(12)', $source);
        $this->assertStringNotContainsString('Tabs::make', $source);
        $this->assertStringNotContainsString('Tab::make', $source);
        $this->assertStringContainsString("Section::make('Thông tin chính')", $source);
        $this->assertStringContainsString("Section::make('Quản lý trực tiếp')", $source);
        $this->assertStringContainsString("Section::make('Nhật ký chỉnh sửa')", $source);
    }
}
