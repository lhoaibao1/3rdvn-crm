@php
    $fieldWrapperView = $getFieldWrapperView();
    $extraInputAttributeBag = $getExtraInputAttributeBag();
    $canSelectPlaceholder = $canSelectPlaceholder();
    $isAutofocused = $isAutofocused();
    $isDisabled = $isDisabled();
    $isMultiple = $isMultiple();
    $isReorderable = $isReorderable();
    $isSearchable = $isSearchable();
    $hasDynamicOptions = $hasDynamicOptions();
    $canOptionLabelsWrap = $canOptionLabelsWrap();
    $isHtmlAllowed = $isHtmlAllowed();
    $isNative = (! ($isSearchable || $isMultiple || $isHtmlAllowed) && $isNative());
    $isPrefixInline = $isPrefixInline();
    $isSuffixInline = $isSuffixInline();
    $key = $getKey();
    $id = $getId();
    $prefixActions = $getPrefixActions();
    $prefixIcon = $getPrefixIcon();
    $prefixIconColor = $getPrefixIconColor();
    $prefixLabel = $getPrefixLabel();
    $suffixActions = $getSuffixActions();
    $suffixIcon = $getSuffixIcon();
    $suffixIconColor = $getSuffixIconColor();
    $suffixLabel = $getSuffixLabel();
    $statePath = $getStatePath();
    $state = $getRawState();
    $livewireKey = $getLivewireKey();
@endphp

@if ($isNative)
    @include('filament-forms::components.select')
@else
    <x-dynamic-component
        :component="$fieldWrapperView"
        :field="$field"
        class="fi-fo-select-wrp"
    >
        <x-filament::input.wrapper
            :disabled="$isDisabled"
            :inline-prefix="$isPrefixInline"
            :inline-suffix="$isSuffixInline"
            :prefix="$prefixLabel"
            :prefix-actions="$prefixActions"
            :prefix-icon="$prefixIcon"
            :prefix-icon-color="$prefixIconColor"
            :suffix="$suffixLabel"
            :suffix-actions="$suffixActions"
            :suffix-icon="$suffixIcon"
            :suffix-icon-color="$suffixIconColor"
            :valid="! $errors->has($statePath)"
            x-on:focus-input.stop="$el.querySelector('.ts-control input, .ts-control')?.focus()"
            :attributes="
                \Filament\Support\prepare_inherited_attributes($getExtraAttributeBag())
                    ->class([
                        'fi-fo-select',
                        'fi-fo-select-has-inline-prefix' => $isPrefixInline && (count($prefixActions) || $prefixIcon || filled($prefixLabel)),
                    ])
            "
        >
            <div
                x-load
                x-load-src="{{ \Illuminate\Support\Facades\Vite::asset('resources/js/components/searchable-select.js') }}"
                x-data="crmSearchableSelectComponent({
                    canOptionLabelsWrap: @js($canOptionLabelsWrap),
                    canSelectPlaceholder: @js($canSelectPlaceholder),
                    getOptionLabelUsing: async () => {
                        return await Livewire.fireAction(
                            $wire.__instance,
                            'callSchemaComponentMethod',
                            [@js($key), 'getOptionLabel'],
                            { async: true },
                        )
                    },
                    getOptionLabelsUsing: async () => {
                        return await Livewire.fireAction(
                            $wire.__instance,
                            'callSchemaComponentMethod',
                            [@js($key), 'getOptionLabelsForJs'],
                            { async: true },
                        )
                    },
                    getOptionsUsing: async () => {
                        return await Livewire.fireAction(
                            $wire.__instance,
                            'callSchemaComponentMethod',
                            [@js($key), 'getOptionsForJs'],
                            { async: true },
                        )
                    },
                    getSearchResultsUsing: async (search) => {
                        return await Livewire.fireAction(
                            $wire.__instance,
                            'callSchemaComponentMethod',
                            [@js($key), 'getSearchResultsForJs', { search }],
                            { async: true },
                        )
                    },
                    hasDynamicOptions: @js($hasDynamicOptions),
                    hasDynamicSearchResults: @js($hasDynamicSearchResults()),
                    initialOptionLabel: @js((blank($state) || $isMultiple) ? null : $getOptionLabel()),
                    initialOptionLabels: @js((filled($state) && $isMultiple) ? $getOptionLabelsForJs() : []),
                    initialState: @js($state),
                    isAutofocused: @js($isAutofocused),
                    isDisabled: @js($isDisabled),
                    isMultiple: @js($isMultiple),
                    isReorderable: @js($isReorderable),
                    isSearchable: @js($isSearchable),
                    loadingMessage: @js($getLoadingMessage()),
                    maxItems: @js($getMaxItems()),
                    noOptionsMessage: @js($getNoOptionsMessage()),
                    noSearchResultsMessage: @js($getNoSearchResultsMessage()),
                    options: @js($getOptionsForJs()),
                    optionsLimit: @js($getOptionsLimit()),
                    placeholder: @js($getPlaceholder()),
                    searchDebounce: @js($getSearchDebounce()),
                    searchingMessage: @js($getSearchingMessage()),
                    searchPrompt: @js($getSearchPrompt()),
                    searchableOptionFields: @js($getSearchableOptionFields()),
                    state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
                    statePath: @js($statePath),
                })"
                x-on:keydown.esc="handleEscape($event)"
                x-on:set-select-property="setDisabled($event.detail.isDisabled)"
                wire:ignore
                wire:key="{{ $livewireKey }}.crm-searchable-select.{{
                    substr(md5(serialize([
                        $isDisabled,
                        $isReorderable,
                    ])), 0, 64)
                }}"
                {{
                    $attributes
                        ->merge($getExtraAlpineAttributes(), escape: false)
                        ->class(['fi-select-input', 'crm-searchable-select'])
                }}
            >
                <select
                    x-ref="input"
                    {{
                        $extraInputAttributeBag
                            ->merge([
                                'aria-label' => $getLabel(),
                                'disabled' => $isDisabled,
                                'id' => $id,
                                'multiple' => $isMultiple,
                            ], escape: false)
                            ->class(['crm-searchable-select-source'])
                    }}
                ></select>
            </div>
        </x-filament::input.wrapper>
    </x-dynamic-component>
@endif
