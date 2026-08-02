<?php

namespace Tests\Unit;

use App\Forms\Components\SearchableSelect;
use App\Forms\Components\SearchableSelectFilter;
use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;

class SearchableSelectFilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $container = new Container;
        $container->instance('translator', new class
        {
            public function get(string $key, array $replace = [], ?string $locale = null): string
            {
                return $key;
            }
        });
        Container::setInstance($container);
    }

    protected function tearDown(): void
    {
        Container::setInstance();

        parent::tearDown();
    }

    public function test_table_filters_use_the_shared_custom_select(): void
    {
        $field = SearchableSelectFilter::make('status')
            ->options(['new' => 'Mới'])
            ->searchable()
            ->getFormField();

        $this->assertInstanceOf(SearchableSelect::class, $field);
        $this->assertFalse($field->isNative());
    }

    public function test_mobile_select_stays_open_during_keyboard_viewport_changes(): void
    {
        $root = dirname(__DIR__, 2);
        $script = file_get_contents($root.'/resources/js/components/searchable-select.js');
        $styles = file_get_contents($root.'/resources/css/searchable-select.css');

        self::assertStringContainsString(
            "window.matchMedia('(max-width: 767px)').matches",
            $script,
        );
        self::assertStringContainsString('isMobileViewport() || !this.select?.isOpen', $script);
        self::assertStringNotContainsString(
            'const closeOnResize = () => this.select?.isOpen && this.select.close()',
            $script,
        );
        self::assertStringContainsString('body.crm-searchable-select-open {', $styles);
        self::assertStringContainsString('overflow: hidden;', $styles);
        self::assertStringContainsString('max-height: min(70svh, 560px);', $styles);
        self::assertStringContainsString('touch-action: manipulation;', $styles);
    }
}
