<?php

namespace Tests\Unit;

use App\Filament\Resources\Applications\Schemas\LotteFinanceFields;
use App\Models\User;
use App\Support\Applications\LotteFinanceWorkflow;
use App\Support\SalesLineSnapshot;
use PHPUnit\Framework\TestCase;

class LotteFinanceWorkflowTest extends TestCase
{
    public function test_sale_submission_note_prefers_module_note_when_present(): void
    {
        $payload = [
            'module_fields' => ['note' => 'Đã chuẩn bị đầy đủ chứng từ'],
            'fields' => ['note' => 'Không dùng ghi chú này'],
        ];

        $this->assertSame('Đã chuẩn bị đầy đủ chứng từ', LotteFinanceWorkflow::saleSubmissionNote($payload));
    }

    public function test_sale_submission_note_falls_back_to_default_when_missing(): void
    {
        $payload = [
            'fields' => ['customer_name' => 'Nguyễn Văn A'],
        ];

        $this->assertSame('Sale đã hoàn thiện thông tin và chứng từ.', LotteFinanceWorkflow::saleSubmissionNote($payload));
    }

    public function test_view_uses_the_same_labels_as_the_lotte_form(): void
    {
        $personal = collect(LotteFinanceFields::personalEntries())->keyBy(fn ($entry) => $entry->getName());
        $work = collect(LotteFinanceFields::workEntries())->keyBy(fn ($entry) => $entry->getName());
        $disbursement = collect(LotteFinanceFields::disbursementEntries())->keyBy(fn ($entry) => $entry->getName());

        $this->assertSame('Nam', self::formatEntryState($personal['payload.fields.gender'], 'MALE'));
        $this->assertSame('Toàn thời gian', self::formatEntryState($work['payload.fields.employment_type'], 'FULL_TIME'));
        $this->assertSame(
            'Giải ngân qua tài khoản ngân hàng',
            self::formatEntryState($disbursement['payload.fields.disbursement_method'], 'bank'),
        );
        $this->assertStringContainsString(
            'Vietcombank',
            self::formatEntryState($disbursement['payload.fields.bank_name'], 'VCB'),
        );
    }

    public function test_sales_hierarchy_snapshot_contains_every_management_level(): void
    {
        $user = new class extends User
        {
            public function hasRole($roles, ?string $guard = null): bool
            {
                return false;
            }
        };
        $user->setRawAttributes([
            'id' => 74,
            'team_id' => 8,
            'team_leader_id' => 3,
            'am_id' => 23,
            'zd_id' => 47,
        ]);

        $this->assertSame([
            'team_id' => 8,
            'team_leader_id' => 3,
            'am_id' => 23,
            'zd_id' => 47,
        ], SalesLineSnapshot::hierarchyFromUser($user));
    }

    private static function formatEntryState(object $entry, mixed $state): mixed
    {
        $reflection = new \ReflectionClass($entry);
        $property = $reflection->getProperty('formatStateUsing');
        $property->setAccessible(true);

        return $property->getValue($entry)($state);
    }
}
