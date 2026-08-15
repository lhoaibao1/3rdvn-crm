<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    public function test_http_error_views_keep_the_shared_animated_experience(): void
    {
        foreach ([400,401,402,403,404,405,406,407,408,409,410,411,412,413,414,415,416,417,418,419,421,422,423,424,425,426,428,429,431,451,500,501,502,503,504,505,506,507,508,510,511] as $status) {
            $html = view("errors.{$status}")->render();
            $this->assertStringContainsString("ERROR_CORE://SYSTEM_STATUS", $html);
            $this->assertStringContainsString((string) $status, $html);
            $this->assertStringNotContainsString('Stack trace', $html);
        }
    }

    public function test_missing_routes_render_the_shared_404_page(): void
    {
        $this->get('/__missing-crm-page__')
            ->assertNotFound()
            ->assertSee('Không tìm thấy đường dẫn')
            ->assertSee('ERROR_CORE://SYSTEM_STATUS');
    }

    public function test_forbidden_responses_render_the_shared_403_page(): void
    {
        Route::get('/__error-page-test__/forbidden', fn () => abort(403));
        $this->get('/__error-page-test__/forbidden')
            ->assertForbidden()
            ->assertSee('Không có quyền truy cập');
    }
}
