<?php

namespace App\Support\Applications;

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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

    public function publicRegistrationUrl(User $user): string
    {
        $baseUrl = rtrim((string) config('services.feol_bridge.public_registration_url'), '?&');

        return $baseUrl.'?'.http_build_query([
            'ref' => $this->referralCode($user),
        ]);
    }

    public function userForReferralCode(string $salesCode): User
    {
        if (! preg_match('/^\d{5}$/', $salesCode)) {
            throw (new ModelNotFoundException)->setModel(User::class);
        }

        $users = User::query()
            ->where('sales_codes->fe-deeplink', $salesCode)
            ->whereJsonContains('sales_projects', 'fe-deeplink')
            ->whereNotIn('employment_status', [
                'inactive',
                User::STATUS_DEACTIVE,
                'resigned',
                User::STATUS_DELETED,
            ])
            ->limit(2)
            ->get();

        if ($users->count() !== 1) {
            throw (new ModelNotFoundException)->setModel(User::class);
        }

        return $users->first();
    }
}
