<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    public function test_common_http_errors_use_the_crm_error_experience(): void
    {
        foreach ([400, 401, 403, 404, 405, 408, 409, 410, 419, 422, 429, 500, 501, 502, 503, 504] as $status) {
            $html = view("errors.{$status}")->render();

            $this->assertStringContainsString("Mã phản hồi {$status}", $html);
            $this->assertStringContainsString('3RD-VN CRM', $html);
            $this->assertStringNotContainsString('Stack trace', $html);
        }
    }

    public function test_missing_routes_render_the_custom_404_page(): void
    {
        $this->get('/__missing-crm-page__')
            ->assertNotFound()
            ->assertSee('Không tìm thấy trang')
            ->assertSee('3RD-VN CRM');
    }

    public function test_forbidden_responses_render_the_custom_403_page(): void
    {
        Route::get('/__error-page-test__/forbidden', fn () => abort(403));

        $this->get('/__error-page-test__/forbidden')
            ->assertForbidden()
            ->assertSee('Không có quyền truy cập');
    }
}
