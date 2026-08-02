<?php

namespace Tests\Unit;

use App\Support\CustomerName;
use PHPUnit\Framework\TestCase;

class CustomerNameTest extends TestCase
{
    public function test_it_normalizes_spacing_and_uppercases_vietnamese_names(): void
    {
        $this->assertSame('NGUYỄN THỊ HƯƠNG', CustomerName::normalize('  Nguyễn   thị Hương  '));
        $this->assertNull(CustomerName::normalize('   '));
    }
}
