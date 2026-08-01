<?php

namespace App\Filament\Resources\SalesProjects\Schemas;

use App\Models\SalesProject;
use App\Support\Applications\ProjectWorkflowConfiguration;
use Filament\Facades\Filament;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class SalesProjectInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make('Dự án bán hàng')
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        TextEntry::make('name')->label('Tên dự án'),
                        TextEntry::make('slug')->label('Mã dự án')->badge(),
                        TextEntry::make('crmModule.label')->label('Module sử dụng')->placeholder('-'),
                        TextEntry::make('code_prefix')->label('Prefix mã')->placeholder('-'),
                        TextEntry::make('sort_order')->label('Thứ tự')->numeric(),
                        IconEntry::make('is_active')->label('Đang dùng')->boolean(),
                        TextEntry::make('description')->label('Ghi chú')->placeholder('-')->columnSpanFull(),
                    ]),
                Section::make('Chi tiết workflow')
                    ->description('Các bước đang được áp dụng thực tế cho dự án này.')
                    ->visible(fn (?SalesProject $record): bool => Filament::getCurrentPanel()?->getId() === 'uat'
                        && ProjectWorkflowConfiguration::supports($record?->slug))
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('workflow_overview')
                            ->hiddenLabel()
                            ->state(fn (SalesProject $record): HtmlString => self::workflowOverview($record))
                            ->html()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    private static function workflowOverview(SalesProject $project): HtmlString
    {
        $steps = ProjectWorkflowConfiguration::forProject($project);
        $labels = collect($steps)->mapWithKeys(fn (array $step): array => [$step['status'] => $step['label']]);

        $rows = collect($steps)->map(function (array $step) use ($labels): string {
            $next = collect($step['next_statuses'])
                ->map(fn (string $status): string => (string) ($labels[$status] ?? $status))
                ->implode(' · ');

            return '<div style="display:grid;grid-template-columns:minmax(180px,1.1fr) minmax(220px,1.5fr) minmax(180px,1fr);gap:16px;padding:13px 0;border-bottom:1px solid #e5e7eb">'
                .'<div><strong>'.e($step['label']).'</strong><div style="font-size:11px;color:#64748b;margin-top:3px">'.e($step['status']).'</div></div>'
                .'<div><div style="font-size:11px;font-weight:700;color:#64748b;margin-bottom:4px">CHUYỂN ĐẾN</div>'.e($next !== '' ? $next : 'Kết thúc').'</div>'
                .'<div><div style="font-size:11px;font-weight:700;color:#64748b;margin-bottom:4px">CÁCH CHUYỂN</div>'.e(ProjectWorkflowConfiguration::modeLabel($step['mode'])).'</div>'
                .'</div>';
        })->implode('');

        return new HtmlString('<div style="width:100%">'.$rows.'</div>');
    }
}
