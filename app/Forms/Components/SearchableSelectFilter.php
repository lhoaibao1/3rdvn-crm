<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Select as BaseSelect;
use Filament\Tables\Filters\SelectFilter;

class SearchableSelectFilter extends SelectFilter
{
    public function getFormField(): BaseSelect
    {
        $field = SearchableSelect::make($this->isMultiple() ? 'values' : 'value')
            ->label($this->getLabel())
            ->multiple($this->isMultiple())
            ->placeholder($this->getPlaceholder())
            ->searchable($this->getSearchable())
            ->selectablePlaceholder($this->canSelectPlaceholder())
            ->preload($this->isPreloaded())
            ->native(false)
            ->optionsLimit($this->getOptionsLimit());

        if ($this->queriesRelationships()) {
            $field
                ->relationship(
                    $this->getRelationshipName(),
                    $this->getRelationshipTitleAttribute(),
                    $this->modifyRelationshipQueryUsing,
                )
                ->getSearchResultsUsing(fn (BaseSelect $component, ?string $search): array => $this->getSearchResultsFromRelationship($component, $search))
                ->options(fn (BaseSelect $component): ?array => $this->getOptionsFromRelationship($component))
                ->getOptionLabelUsing(fn (BaseSelect $component) => $this->getOptionLabelFromRelationship($component))
                ->getOptionLabelsUsing(fn (BaseSelect $component, array $values): array => $this->getOptionLabelsFromRelationship($component, $values))
                ->forceSearchCaseInsensitive($this->isSearchForcedCaseInsensitive());
        } else {
            $field->options(fn (): array => $this->getOptions());
        }

        if ($this->getOptionLabelUsing) {
            $field->getOptionLabelUsing($this->getOptionLabelUsing);
        }

        if ($this->getOptionLabelsUsing) {
            $field->getOptionLabelsUsing($this->getOptionLabelsUsing);
        }

        if ($this->getOptionLabelFromRecordUsing) {
            $field->getOptionLabelFromRecordUsing($this->getOptionLabelFromRecordUsing);
        }

        if ($this->getSearchResultsUsing) {
            $field->getSearchResultsUsing($this->getSearchResultsUsing);
        }

        if (filled($defaultState = $this->getDefaultState())) {
            $field->default($defaultState);
        }

        return $field;
    }
}
