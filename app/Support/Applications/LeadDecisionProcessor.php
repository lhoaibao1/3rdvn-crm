<?php

namespace App\Support\Applications;

use App\Models\Application;
use App\Models\Lead;
use App\Models\User;
use App\Support\Notifications\LeadNotificationSender;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeadDecisionProcessor
{
    public static function process(Lead $lead, User $actor, array $data): ?Application
    {
        return DB::transaction(function () use ($lead, $actor, $data): ?Application {
            $lead = Lead::query()->lockForUpdate()->with(['salesProject', 'application'])->findOrFail($lead->getKey());

            if (filled($lead->converted_at)) {
                throw ValidationException::withMessages([
                    'status' => 'Lead đã chuyển Application nên không được quyết định lại.',
                ]);
            }

            $status = (string) ($data['status'] ?? '');

            if (! in_array($status, ['Từ chối', 'Khách hàng bị trùng', 'Khách hàng thoả mãn điều kiện'], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Vui lòng chọn quyết định xử lý hồ sơ.',
                ]);
            }

            $applicationCode = trim((string) Arr::get($data, 'application_code', ''));

            if ($status === 'Khách hàng thoả mãn điều kiện') {
                if ($applicationCode === '' && self::isCbpLead($lead)) {
                    $applicationCode = self::generateApplicationCode($lead);
                }

                if ($applicationCode === '') {
                    throw ValidationException::withMessages([
                        'application_code' => 'Vui lòng nhập mã hồ sơ.',
                    ]);
                }

                $exists = Application::withTrashed()->where('application_code', $applicationCode)->exists();

                if ($exists) {
                    throw ValidationException::withMessages([
                        'application_code' => 'Mã hồ sơ đã tồn tại.',
                    ]);
                }
            }

            $payload = is_array($lead->payload) ? $lead->payload : [];
            $payload['review'] = self::reviewPayload($lead, $status, $data, $applicationCode);

            $lead->forceFill([
                'status' => $status,
                'payload' => $payload,
                'note' => self::leadNote($lead, $status, $data, $applicationCode),
            ])->save();

            if ($status !== 'Khách hàng thoả mãn điều kiện') {
                return null;
            }

            $application = LeadApplicationConverter::convert($lead->refresh(), $actor, $applicationCode);
            LeadNotificationSender::leadConverted($lead->refresh(), $application);

            return $application;
        });
    }

    private static function reviewPayload(Lead $lead, string $status, array $data, string $applicationCode): array
    {
        $review = [
            'decision' => $status,
            'application_code' => $applicationCode ?: null,
            'review_note' => Arr::get($data, 'review_note'),
            'auto_note' => $status === 'Khách hàng thoả mãn điều kiện' ? self::qualifiedAutoNote($lead, $applicationCode) : null,
            'reviewed_at' => now()->toDateTimeString(),
            'reviewed_by_id' => auth()->id(),
        ];

        foreach (['decision_result', 'decision_result_label', 'blacklist_check', 'existing_check', 'exception_check', 'b11t_check'] as $key) {
            if (array_key_exists($key, $data) && filled($data[$key])) {
                $review[$key] = $data[$key];
            }
        }

        if (
            $status === 'Khách hàng thoả mãn điều kiện'
            && ! self::isCbpLead($lead)
            && (Arr::has($data, 'product') || Arr::has($data, 'pre_approved_amount'))
        ) {
            $review['product'] = Arr::get($data, 'product');
            $review['pre_approved_amount'] = self::digits(Arr::get($data, 'pre_approved_amount'));
            $review['pre_approved_months'] = Arr::get($data, 'pre_approved_months');
            $review['pre_approved_interest_rate'] = self::decimalString(Arr::get($data, 'pre_approved_interest_rate'));
        }

        return $review;
    }

    private static function leadNote(Lead $lead, string $status, array $data, string $applicationCode): ?string
    {
        if (self::isCbpLead($lead)) {
            return Arr::get($data, 'review_note', $lead->note);
        }

        if ($status === 'Khách hàng thoả mãn điều kiện') {
            return self::qualifiedAutoNote($lead, $applicationCode);
        }

        return Arr::get($data, 'review_note', $lead->note);
    }


    private static function isCbpLead(Lead $lead): bool
    {
        return $lead->salesProject?->slug === 'cbp';
    }

    private static function generateApplicationCode(Lead $lead): string
    {
        $prefix = strtoupper((string) ($lead->salesProject?->slug ?: 'APP')).now()->format('ymd');
        $next = Application::withTrashed()
            ->where('application_code', 'like', $prefix.'%')
            ->count() + 1;

        for ($sequence = $next; $sequence < $next + 100; $sequence++) {
            $code = $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);

            if (! Application::withTrashed()->where('application_code', $code)->exists()) {
                return $code;
            }
        }

        return $prefix.now()->format('His');
    }

    private static function qualifiedAutoNote(Lead $lead, string $applicationCode): string
    {
        $projectName = $lead->salesProject?->name ?: 'Dự án';

        return 'NVKD truy cập "'.$projectName.'" để tiếp tục hoàn thiện hồ sơ vay "'.$applicationCode.'".';
    }

    private static function digits(mixed $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        return $digits !== '' ? $digits : null;
    }

    private static function decimalString(mixed $value): ?string
    {
        $value = trim(str_replace(',', '.', (string) $value));

        return $value !== '' ? $value : null;
    }
}
