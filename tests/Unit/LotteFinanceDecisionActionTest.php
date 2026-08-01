<?php

namespace Tests\Unit;

use App\Http\Controllers\Crm\TableColumnPreferenceController;
use App\Models\Application;
use App\Models\SalesProject;
use App\Support\Applications\LotteFinanceWorkflow;
use App\Support\Filament\LotteFinanceDecisionAction;
use App\Forms\Components\SearchableSelect as Select;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use PHPUnit\Framework\TestCase;

class LotteFinanceDecisionActionTest extends TestCase
{
    public function test_uw_call_form_contains_the_dynamic_approved_amount(): void
    {
        $record = new Application;
        $record->status = LotteFinanceWorkflow::UW_CALL;
        $record->payload = ['review' => ['approved_amount' => '25000000']];
        $record->setRelation('salesProject', new SalesProject(['slug' => 'lotte-finance']));

        $method = new \ReflectionMethod(LotteFinanceDecisionAction::class, 'form');
        $method->setAccessible(true);
        $schema = $method->invoke(null, $record);

        $this->assertSame(['next_status', 'approved_amount', 'processing_note'], array_map(
            fn (object $component): string => $component->getName(),
            $schema,
        ));
        $this->assertSame(Select::class, get_class($schema[0]));

        $property = (new \ReflectionClass($schema[1]))->getProperty('isVisible');
        $property->setAccessible(true);
        $visibility = $property->getValue($schema[1]);
        $get = fn (string $status): Get => new class($status) extends Get
        {
            public function __construct(private readonly string $nextStatus) {}

            public function __invoke(string|Component $path = '', bool $isAbsolute = false): mixed
            {
                return $path === 'next_status' ? $this->nextStatus : null;
            }
        };

        $this->assertTrue($visibility($get(LotteFinanceWorkflow::UW_APPROVAL)));
        $this->assertFalse($visibility($get(LotteFinanceWorkflow::UW_FIELD)));
    }

    public function test_lotte_transition_options_match_the_required_workflow(): void
    {
        $method = new \ReflectionMethod(LotteFinanceDecisionAction::class, 'nextStatusOptions');
        $method->setAccessible(true);

        $this->assertSame([
            LotteFinanceWorkflow::UW_APPROVAL => 'UW Approval',
            LotteFinanceWorkflow::UW_REJECTED => 'UW Rej',
            LotteFinanceWorkflow::UW_FIELD => 'UW Field',
            LotteFinanceWorkflow::RETURNED_TO_SALE => 'Trả về Sale',
        ], $method->invoke(null, LotteFinanceWorkflow::UW_CALL));
        $this->assertSame([
            LotteFinanceWorkflow::ESIGN => 'eSign',
            LotteFinanceWorkflow::RETURNED_TO_SALE => 'Trả về Sale',
        ], $method->invoke(null, LotteFinanceWorkflow::UW_APPROVAL));
        $this->assertSame([
            LotteFinanceWorkflow::POST_APPROVAL => 'Post Approval',
            LotteFinanceWorkflow::RETURNED_TO_SALE => 'Trả về Sale',
        ], $method->invoke(null, LotteFinanceWorkflow::ESIGN));
        $this->assertSame([
            LotteFinanceWorkflow::DISBURSED => 'Đã giải ngân',
            LotteFinanceWorkflow::RETURNED_TO_SALE => 'Trả về Sale',
        ], $method->invoke(null, LotteFinanceWorkflow::POST_APPROVAL));
        $this->assertSame([], $method->invoke(null, LotteFinanceWorkflow::DISBURSED));
    }

    public function test_lotte_table_column_order_can_be_saved(): void
    {
        $tables = (new \ReflectionClass(TableColumnPreferenceController::class))->getConstant('TABLES');

        $this->assertContains('applications.lotte-finance', $tables);
    }
}
