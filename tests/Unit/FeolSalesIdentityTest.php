<?php

namespace Tests\Unit;

use App\Models\User;
use App\Support\Applications\FeolSalesIdentity;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\TestCase;

class FeolSalesIdentityTest extends TestCase
{
    public function test_it_uses_the_logged_in_users_fe_deeplink_sales_code(): void
    {
        $user = new User(['sales_codes' => ['fe-deeplink' => '26801']]);

        self::assertSame('26801', (new FeolSalesIdentity)->referralCode($user));
    }

    public function test_it_rejects_a_missing_or_invalid_fe_deeplink_sales_code(): void
    {
        $this->expectException(ValidationException::class);

        (new FeolSalesIdentity)->referralCode(new User(['sales_codes' => ['fe-deeplink' => 'ABC']]));
    }
}
