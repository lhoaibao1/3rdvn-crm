<?php
namespace App\Filament\Resources\AffiliateConversions\Pages;

use App\Filament\Resources\AffiliateConversions\AffiliateConversionResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Js;

class ListAffiliateConversions extends ListRecords
{
    protected static string $resource = AffiliateConversionResource::class;

    public function getHeading(): string
    {
        return 'Affiliate · HyperLead';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('copyShbFinanceLink')
                ->label('Copy link SHB Finance')
                ->icon(Heroicon::OutlinedClipboardDocument)
                ->action(function (mixed $livewire): void {
                    $livewire->js(
                        'navigator.clipboard.writeText('.Js::from($this->shbFinanceLink()).").then(() => new FilamentNotification().title('Đã sao chép link SHB Finance').success().send())",
                    );
                }),
            Action::make('openShbFinanceCampaign')
                ->label('Mở chiến dịch SHB Finance')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->url(fn (): string => $this->shbFinanceLink(), shouldOpenInNewTab: true),
        ];
    }

    private function shbFinanceLink(): string
    {
        $employeeCode = trim((string) auth()->user()?->employee_code);
        $baseUrl = 'https://riofin.asia/v2/h6ZUoKMr6OVLqyCgJ9UNQkEnUZFMnjA2D_Pt6iQOrjw?lp=shbfinance';

        return $employeeCode === '' ? $baseUrl : $baseUrl.'&aff_sub1='.rawurlencode($employeeCode);
    }
}
