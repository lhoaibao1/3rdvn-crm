<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('affiliate_conversions')
            ->select(['id', 'raw_payload'])
            ->orderBy('id')
            ->chunkById(200, function ($conversions): void {
                foreach ($conversions as $conversion) {
                    $payload = is_string($conversion->raw_payload)
                        ? json_decode($conversion->raw_payload, true)
                        : (array) $conversion->raw_payload;
                    $updates = [];

                    foreach ([
                        'click_time',
                        'conversion_time',
                        'conversion_modified_time',
                        'conversion_status_updated_time',
                    ] as $field) {
                        $value = $payload[$field] ?? null;
                        if (is_numeric($value) && (int) $value > 10_000_000_000) {
                            $updates[$field] = CarbonImmutable::createFromTimestampMs((int) $value)
                                ->setTimezone('Asia/Ho_Chi_Minh')
                                ->toDateTimeString();
                        }
                    }

                    if ($updates !== []) {
                        DB::table('affiliate_conversions')->where('id', $conversion->id)->update($updates);
                    }
                }
            });
    }

    public function down(): void
    {
        // Partner timestamps are restored from raw_payload on the next callback.
    }
};
