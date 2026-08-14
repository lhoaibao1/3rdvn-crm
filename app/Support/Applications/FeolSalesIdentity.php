<?php

namespace App\Support\Applications;

use App\Models\User;
use Illuminate\Validation\ValidationException;

final class FeolSalesIdentity
{
    public function referralCode(User $user): string
    {
        $code = trim((string) data_get($user->sales_codes, 'fe-deeplink'));

        if (! preg_match('/^\d{5}$/', $code)) {
            throw ValidationException::withMessages([
                'payload.fields.referral_code' => 'Tài khoản chưa có mã bán hàng FE Deeplink hợp lệ gồm 5 số.',
            ]);
        }

        return $code;
    }
}
