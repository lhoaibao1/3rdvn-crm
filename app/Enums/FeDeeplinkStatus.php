<?php

namespace App\Enums;

enum FeDeeplinkStatus: string
{
    case PENDING_SUBMISSION = 'pending_submission';
    case ELIGIBLE = 'eligible';
    case INELIGIBLE = 'ineligible';
    case APP_DOWNLOAD = 'app_download';
    case START_REGISTRATION = 'start_registration';
    case APP_LOGIN = 'app_login';
    case PRE_SCREENING_FAILURE = 'pre_screening_failure';
    case START_LOAN_ONBOARDING = 'start_loan_onboarding';
    case REFERRAL_CODE = 'referral_code';
    case PENDING_ESIGN = 'pending_esign';
    case PENDING_OFFER = 'pending_offer';
    case PENDING_DISBURSEMENT = 'pending_disbursement';
    case PL_DISBURSED = 'pl_disbursed';
    case HARD_REJECT = 'hard_reject';
    case SOFT_REJECT = 'soft_reject';
    case CANCELLATION = 'cancellation';
    case DROP_OFF = 'drop_off';

    public function label(): string
    {
        return match ($this) {
            self::PENDING_SUBMISSION => 'Chờ gửi đối tác',
            self::ELIGIBLE => 'Eligible',
            self::INELIGIBLE => 'InEligible',
            self::APP_DOWNLOAD => 'App download',
            self::START_REGISTRATION => 'Start registration',
            self::APP_LOGIN => 'App login',
            self::PRE_SCREENING_FAILURE => 'Pre-screening failure',
            self::START_LOAN_ONBOARDING => 'Start loan onboarding',
            self::REFERRAL_CODE => 'Referral code',
            self::PENDING_ESIGN => 'Pending eSign',
            self::PENDING_OFFER => 'Pending Offer',
            self::PENDING_DISBURSEMENT => 'Pending Disbursement',
            self::PL_DISBURSED => 'PL Disbursed',
            self::HARD_REJECT => 'Hard Reject',
            self::SOFT_REJECT => 'Soft Reject',
            self::CANCELLATION => 'Cancellation',
            self::DROP_OFF => 'Drop-Off',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ELIGIBLE, self::PL_DISBURSED => 'success',
            self::INELIGIBLE, self::PRE_SCREENING_FAILURE, self::HARD_REJECT,
            self::SOFT_REJECT, self::CANCELLATION => 'danger',
            self::PENDING_ESIGN, self::PENDING_OFFER, self::PENDING_DISBURSEMENT => 'warning',
            self::PENDING_SUBMISSION => 'gray',
            default => 'info',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::INELIGIBLE,
            self::PRE_SCREENING_FAILURE,
            self::REFERRAL_CODE,
            self::PL_DISBURSED,
            self::HARD_REJECT,
            self::CANCELLATION,
        ], true);
    }

    public function permitsFirstDeeplinkCapture(): bool
    {
        return $this === self::ELIGIBLE;
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $status): array => [
            $status->value => $status->label(),
        ])->all();
    }

    public static function fromPartnerLabel(?string $label): ?self
    {
        $normalized = strtolower(trim((string) $label));
        $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized) ?: '';

        return self::tryFrom(trim($normalized, '_'));
    }

    public static function labelFor(?string $value): string
    {
        return self::tryFrom((string) $value)?->label() ?? ($value ?: '-');
    }

    public static function colorFor(?string $value): string
    {
        return self::tryFrom((string) $value)?->color() ?? 'gray';
    }
}
