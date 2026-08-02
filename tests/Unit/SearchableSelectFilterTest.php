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
}
