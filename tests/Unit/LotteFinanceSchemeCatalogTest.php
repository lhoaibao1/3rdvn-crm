<?php

namespace Tests\Unit;

use App\Support\LotteFinanceSchemeCatalog;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class LotteFinanceSchemeCatalogTest extends TestCase
{
    public function test_initial_catalog_is_not_truncated_to_the_old_150_item_limit(): void
    {
        $method = new ReflectionMethod(LotteFinanceSchemeCatalog::class, 'topOptions');
        $limit = $method->getParameters()[0]->getDefaultValue();

        $this->assertSame(1000, $limit);
        $this->assertGreaterThan(150, $limit);
    }

    public function test_sparse_api_data_does_not_erase_existing_scheme_details(): void
    {
        $method = new ReflectionMethod(LotteFinanceSchemeCatalog::class, 'mergeScheme');
        $method->setAccessible(true);

        $merged = $method->invoke(null, [
            'scheme_code' => 'SC001',
            'product' => 'Cash Loan',
            'interest_options' => [['label' => '18%']],
        ], [
            'scheme_code' => 'SC001',
            'product' => '',
            'interest_options' => [],
            'sid' => 'scheme-id',
        ]);

        $this->assertSame('Cash Loan', $merged['product']);
        $this->assertSame([['label' => '18%']], $merged['interest_options']);
        $this->assertSame('scheme-id', $merged['sid']);
    }

    public function test_scheme_api_parser_accepts_all_supported_collection_shapes(): void
    {
        $method = new ReflectionMethod(LotteFinanceSchemeCatalog::class, 'schemeItems');
        $method->setAccessible(true);
        $items = [['code' => 'SC001']];

        $this->assertSame($items, $method->invoke(null, ['data' => ['schemeList' => ['items' => $items]]]));
        $this->assertSame($items, $method->invoke(null, ['data' => ['result' => $items]]));
    }
}
