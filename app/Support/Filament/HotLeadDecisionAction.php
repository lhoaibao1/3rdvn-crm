<?php

namespace App\Support\Filament;

use App\Models\Lead;
use App\Models\SalesProject;
use App\Support\HotLeads\HotLeadConverter;
use App\Support\Permissions\HotLeadAccess;
use App\Support\Permissions\SalesProjectAccess;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use App\Forms\Components\SearchableSelect as Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

class HotLeadDecisionAction
{
    public static function make(?callable $recordResolver = null): Action
    {
        return Action::make('processHotLead')
            ->label('Xử lý hồ sơ')
            ->icon(Heroicon::OutlinedClipboardDocumentCheck)
            ->color('warning')
            ->visible(fn (?Lead $record = null): bool => self::canProcess(self::resolveRecord($recordResolver, $record)))
            ->modalHeading(fn (?Lead $record = null): string => 'Xử lý Lead nóng '.(self::resolveRecord($recordResolver, $record)?->lead_code ?: ''))
            ->extraModalWindowAttributes(['class' => 'crm-lead-modal crm-lead-process-modal'])
            ->modalWidth('3xl')
            ->modalSubmitActionLabel('Lưu quyết định')
            ->modalCancelActionLabel('Hủy')
            ->fillForm(fn (?Lead $record = null): array => self::initialData(self::resolveRecord($recordResolver, $record)))
            ->form(fn (?Lead $record = null): array => self::form(self::resolveRecord($recordResolver, $record)))
            ->action(function (array $data, ?Lead $record = null) use ($recordResolver): void {
                $lead = self::resolveRecord($recordResolver, $record);

                if (! $lead instanceof Lead || ! auth()->user()) {
                    return;
                }

                $application = HotLeadConverter::process($lead, auth()->user(), $data);
                $applicationLabel = $application
                    ? trim(($application->salesProject?->name ?: 'Dự án').' - '.($application->application_code ?: '#'.$application->getKey()))
                    : null;

                Notification::make()
                    ->title('Đã lưu quyết định xử lý')
                    ->body($applicationLabel ? 'Đã chuyển sang '.$applicationLabel.'.' : 'Lead nóng đã được cập nhật Không thoả điều kiện.')
                    ->success()
                    ->send();
            });
    }

    private static function resolveRecord(?callable $recordResolver, ?Lead $record): ?Lead
    {
        return $recordResolver ? $recordResolver($record) : $record;
    }

    private static function canProcess(?Lead $lead): bool
    {
        return $lead instanceof Lead && auth()->user() && HotLeadAccess::canProcess(auth()->user(), $lead);
    }

    private static function initialData(?Lead $lead): array
    {
        $review = is_array($lead?->payload) ? ($lead->payload['review'] ?? []) : [];

        return [
            'decision' => $review['decision'] ?? ($lead?->status === 'Khách hàng thoả mãn điều kiện' ? 'qualified' : null),
            'target_sales_project_id' => $review['target_sales_project_id'] ?? $lead?->application?->sales_project_id,
            'application_code' => $review['application_code'] ?? $lead?->application?->application_code,
            'decision_note' => $review['decision_note'] ?? $lead?->note,
        ];
    }

    private static function form(?Lead $lead): array
    {
        return [
            Placeholder::make('processor_display')
                ->label('Người được phân xử lý')
                ->content($lead?->assignedSale?->name ?: 'Admin'),
            Placeholder::make('creator_display')
                ->label('Người tạo Lead')
                ->content($lead?->createdBy?->name ?: '-'),
            Select::make('decision')
                ->label('Quyết định')
                ->options([
                    'qualified' => 'Thoả điều kiện',
                    'rejected' => 'Không thoả điều kiện',
                ])
                ->required()
                ->live()
                ->native(false),
            Select::make('target_sales_project_id')
                ->label('Dự án xử lý')
                ->options(fn (): array => self::targetProjectOptions())
                ->searchable()
                ->preload()
                ->native(false)
                ->required(fn (Get $get): bool => $get('decision') === 'qualified')
                ->visible(fn (Get $get): bool => $get('decision') === 'qualified'),
            TextInput::make('application_code')
                ->label('Mã hồ sơ/Application')
                ->maxLength(255)
                ->required(fn (Get $get): bool => $get('decision') === 'qualified')
                ->visible(fn (Get $get): bool => $get('decision') === 'qualified'),
            Placeholder::make('profile_result_display')
                ->label('Kết quả sau xử lý')
                ->content(fn (Get $get): string => $get('decision') === 'qualified' ? 'Chuyển Lead nóng sang dự án đã chọn' : 'Đóng Lead nóng')
                ->visible(fn (Get $get): bool => filled($get('decision'))),
            Textarea::make('decision_note')
                ->label('Ghi chú quyết định')
                ->rows(3)
                ->columnSpanFull(),
        ];
    }

    private static function targetProjectOptions(): array
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        return SalesProject::query()
            ->where('is_active', true)
            ->whereHas('crmModule', fn ($query) => $query->where('slug', 'applications'))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->filter(fn (SalesProject $project): bool => SalesProjectAccess::canAccessProject($user, $project))
            ->mapWithKeys(fn (SalesProject $project): array => [$project->getKey() => $project->name])
            ->all();
    }
}
