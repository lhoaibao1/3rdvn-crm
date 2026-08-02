import TomSelect from 'tom-select/dist/js/tom-select.complete.js'

const emptyValue = (value) => value === null || value === undefined || value === ''

const valueKey = (value) => (emptyValue(value) ? '' : String(value))

const textLabel = (value) => {
    if (value === null || value === undefined) {
        return ''
    }

    const template = document.createElement('template')
    template.innerHTML = String(value)

    return (template.content.textContent || '').trim()
}

const sameState = (left, right, multiple) => {
    if (multiple) {
        const leftValues = Array.isArray(left) ? left.map(valueKey) : []
        const rightValues = Array.isArray(right) ? right.map(valueKey) : []

        return JSON.stringify(leftValues) === JSON.stringify(rightValues)
    }

    return valueKey(left) === valueKey(right)
}

const messageElement = (message, className) => {
    const element = document.createElement('div')
    element.className = className
    element.textContent = message || ''

    return element
}

const crmSearchableSelectComponent = (config) => ({
    select: null,
    state: config.state,
    rawValues: new Map(),
    requestSequence: 0,
    destroyed: false,
    syncingFromLivewire: false,
    removeViewportListeners: null,
    removeControlClickListener: null,
    dynamicOptionsSignature: null,

    init() {
        const input = this.$refs.input

        if (!input) {
            return
        }

        if (input.tomselect) {
            input.tomselect.destroy()
        }

        const prepared = this.prepareOptions(config.options)
        this.appendInitialSelection(prepared)

        const plugins = {}

        if (config.isSearchable) {
            plugins.dropdown_input = {}
        }

        if (config.canSelectPlaceholder && !config.isMultiple) {
            plugins.clear_button = { title: 'Xóa lựa chọn' }
        }

        if (config.isMultiple) {
            plugins.remove_button = { title: 'Xóa lựa chọn' }

            if (config.isReorderable) {
                plugins.drag_drop = {}
            }
        }

        this.select = new TomSelect(input, {
            allowEmptyOption: config.canSelectPlaceholder,
            closeAfterSelect: !config.isMultiple,
            controlInput: config.isSearchable
                ? '<input type="text" autocomplete="off" size="1" />'
                : null,
            create: false,
            disabledField: 'isDisabled',
            dropdownParent: 'body',
            hidePlaceholder: true,
            labelField: 'label',
            loadThrottle: Number(config.searchDebounce) || 300,
            lockOptgroupOrder: true,
            maxItems: config.isMultiple ? (config.maxItems || null) : 1,
            maxOptions: Number(config.optionsLimit) || 50,
            openOnFocus: false,
            optgroupField: 'optgroup',
            optgroupLabelField: 'label',
            optgroupValueField: 'value',
            optgroups: prepared.groups,
            options: prepared.options,
            placeholder: config.placeholder || '',
            plugins,
            preload: false,
            searchField: config.isSearchable
                ? this.searchFields(config.searchableOptionFields)
                : [],
            selectOnTab: false,
            shouldLoad: (query) => (
                config.hasDynamicSearchResults
                && config.isSearchable
                && query.trim().length >= 1
            ),
            valueField: 'value',
            load: config.hasDynamicSearchResults
                ? async (query, callback) => {
                    const requestId = ++this.requestSequence

                    try {
                        const results = await config.getSearchResultsUsing(query)

                        if (this.destroyed || requestId !== this.requestSequence) {
                            callback([])
                            return
                        }

                        const normalized = this.prepareOptions(results)
                        this.registerGroups(normalized.groups)
                        callback(normalized.options)
                    } catch {
                        if (requestId === this.requestSequence) {
                            callback([])
                        }
                    }
                }
                : undefined,
            render: {
                no_results: (data) => messageElement(
                    config.hasDynamicSearchResults && !String(data.input || '').trim()
                        ? config.searchPrompt
                        : config.noSearchResultsMessage,
                    'crm-searchable-select-message',
                ),
                loading: () => messageElement(
                    config.searchingMessage || config.loadingMessage,
                    'crm-searchable-select-message',
                ),
                option: (data) => {
                    const option = messageElement(data.label, 'crm-searchable-select-option')

                    if (data.isDisabled) {
                        option.setAttribute('aria-disabled', 'true')
                    }

                    return option
                },
                item: (data) => messageElement(
                    data.label,
                    'crm-searchable-select-item',
                ),
                optgroup_header: (data) => messageElement(
                    data.label,
                    'crm-searchable-select-group',
                ),
            },
            onChange: (value) => this.updateLivewireState(value),
            onDropdownOpen: () => {
                document.body.classList.add('crm-searchable-select-open')

                if (config.hasDynamicOptions) {
                    this.refreshDynamicOptions()
                }
            },
            onDropdownClose: () => {
                document.body.classList.remove('crm-searchable-select-open')
            },
        })

        this.select.wrapper.classList.add('crm-searchable-select-control')
        this.select.dropdown.classList.add('crm-searchable-select-dropdown')
        this.dynamicOptionsSignature = this.optionsSignature(prepared.options)
        this.installViewportListeners()
        this.installControlClickListener()

        this.syncFromState(this.state)
        this.setDisabled(config.isDisabled)

        this.$watch('state', (value) => {
            this.syncFromState(value)
        })

        if (config.isAutofocused) {
            this.select.focus()
        }
    },

    searchFields(fields) {
        const normalized = Array.isArray(fields)
            ? fields.filter((field) => typeof field === 'string' && field.length)
            : []

        return [...new Set(['label', ...normalized])]
    },

    prepareOptions(items) {
        const options = []
        const groups = []

        if (!Array.isArray(items)) {
            return { options, groups }
        }

        items.forEach((item, groupIndex) => {
            if (!item || typeof item !== 'object') {
                return
            }

            if (Array.isArray(item.options)) {
                const groupValue = 'group-' + groupIndex + '-' + textLabel(item.label)

                groups.push({
                    value: groupValue,
                    label: textLabel(item.label),
                })

                item.options.forEach((option) => {
                    const normalized = this.prepareOption(option, groupValue)

                    if (normalized) {
                        options.push(normalized)
                    }
                })

                return
            }

            const normalized = this.prepareOption(item)

            if (normalized) {
                options.push(normalized)
            }
        })

        return { options, groups }
    },

    prepareOption(option, group = null) {
        if (!option || typeof option !== 'object' || option.value === undefined) {
            return null
        }

        const key = valueKey(option.value)

        this.rawValues.set(key, option.value)

        const normalized = {
            value: key,
            label: textLabel(option.label),
            isDisabled: Boolean(option.isDisabled || option.disabled),
        }

        if (group !== null) {
            normalized.optgroup = group
        }

        this.searchFields(config.searchableOptionFields).forEach((field) => {
            if (field !== 'label') {
                normalized[field] = textLabel(option[field] ?? option.label)
            }
        })

        return normalized
    },

    appendInitialSelection(prepared) {
        const currentValues = config.isMultiple
            ? (Array.isArray(config.initialState) ? config.initialState : [])
            : [config.initialState]

        const labels = config.isMultiple
            ? (Array.isArray(config.initialOptionLabels) ? config.initialOptionLabels : [])
            : [{
                value: config.initialState,
                label: config.initialOptionLabel,
            }]

        currentValues.forEach((value, index) => {
            if (emptyValue(value)) {
                return
            }

            const key = valueKey(value)

            this.rawValues.set(key, value)

            if (prepared.options.some((option) => option.value === key)) {
                return
            }

            const labelOption = labels.find((option) => (
                option && typeof option === 'object' && valueKey(option.value) === key
            ))

            const label = labelOption?.label
                ?? (config.isMultiple ? labels[index]?.label : config.initialOptionLabel)
                ?? value

            prepared.options.push({
                value: key,
                label: textLabel(label),
                isDisabled: false,
            })
        })
    },

    registerGroups(groups) {
        if (!this.select || !Array.isArray(groups)) {
            return
        }

        groups.forEach((group) => {
            if (!this.select.optgroups[group.value]) {
                this.select.addOptionGroup(group.value, group)
            }
        })
    },

    async refreshDynamicOptions() {
        const requestId = ++this.requestSequence
        const stateBeforeRequest = this.select?.getValue()

        try {
            const results = await config.getOptionsUsing()

            if (
                this.destroyed
                || requestId !== this.requestSequence
                || !this.select
                || !sameState(stateBeforeRequest, this.select.getValue(), config.isMultiple)
            ) {
                return
            }

            const prepared = this.prepareOptions(results)
            const signature = this.optionsSignature(prepared.options)

            if (signature === this.dynamicOptionsSignature) {
                return
            }

            this.select.clearOptionGroups()
            this.registerGroups(prepared.groups)
            this.select.clearOptions()
            this.select.addOptions(prepared.options)
            this.dynamicOptionsSignature = signature
            this.select.refreshOptions(false)
            await this.syncFromState(this.state)
        } catch {
            // Keep the last valid option set when the server cannot refresh.
        }
    },

    optionsSignature(options) {
        return JSON.stringify((options || []).map((option) => [
            option.value,
            option.label,
            Boolean(option.isDisabled),
            option.optgroup || null,
        ]))
    },

    updateLivewireState(value) {
        if (this.syncingFromLivewire) {
            return
        }

        const nextState = config.isMultiple
            ? (Array.isArray(value) ? value : []).map((item) => (
                this.rawValues.has(valueKey(item))
                    ? this.rawValues.get(valueKey(item))
                    : item
            ))
            : (
                emptyValue(value)
                    ? null
                    : (this.rawValues.has(valueKey(value))
                        ? this.rawValues.get(valueKey(value))
                        : value)
            )

        if (!sameState(this.state, nextState, config.isMultiple)) {
            this.state = nextState
        }
    },

    async syncFromState(value) {
        if (!this.select) {
            return
        }

        await this.hydrateMissingLabels(value)

        if (!this.select || this.destroyed) {
            return
        }

        const selected = config.isMultiple
            ? (Array.isArray(value) ? value.map(valueKey).filter(Boolean) : [])
            : valueKey(value)

        const current = this.select.getValue()

        if (sameState(current, selected, config.isMultiple)) {
            return
        }

        this.syncingFromLivewire = true
        this.select.setValue(selected, true)
        this.syncingFromLivewire = false
    },

    async hydrateMissingLabels(value) {
        if (!this.select) {
            return
        }

        const values = config.isMultiple
            ? (Array.isArray(value) ? value : [])
            : [value]

        const missing = values.filter((item) => (
            !emptyValue(item) && !this.select.options[valueKey(item)]
        ))

        if (!missing.length) {
            return
        }

        try {
            if (config.isMultiple) {
                const labels = await config.getOptionLabelsUsing()
                const prepared = this.prepareOptions(labels)
                this.registerGroups(prepared.groups)
                this.select.addOptions(prepared.options)
            } else {
                const label = await config.getOptionLabelUsing()
                const rawValue = missing[0]
                const key = valueKey(rawValue)

                this.rawValues.set(key, rawValue)
                this.select.addOption({
                    value: key,
                    label: textLabel(label ?? rawValue),
                    isDisabled: false,
                })
            }
        } catch {
            // The next dynamic refresh can hydrate the missing label.
        }
    },

    setDisabled(disabled) {
        config.isDisabled = Boolean(disabled)

        if (!this.select) {
            return
        }

        config.isDisabled ? this.select.disable() : this.select.enable()
    },

    installControlClickListener() {
        const openFromUserClick = (event) => {
            if (
                config.isDisabled
                || this.select?.isOpen
                || event.target.closest('.remove, .clear-button')
            ) {
                return
            }

            this.select?.open()
        }

        this.select?.control.addEventListener('click', openFromUserClick)
        this.removeControlClickListener = () => {
            this.select?.control.removeEventListener('click', openFromUserClick)
        }
    },

    installViewportListeners() {
        const closeOnViewportChange = (event) => {
            if (!this.select?.isOpen || this.select.dropdown.contains(event.target)) {
                return
            }

            this.select.close()
        }
        const closeOnResize = () => this.select?.isOpen && this.select.close()

        document.addEventListener('scroll', closeOnViewportChange, true)
        window.addEventListener('resize', closeOnResize, { passive: true })

        this.removeViewportListeners = () => {
            document.removeEventListener('scroll', closeOnViewportChange, true)
            window.removeEventListener('resize', closeOnResize)
        }
    },

    handleEscape(event) {
        if (!this.select?.isOpen) {
            return
        }

        event.stopPropagation()
        this.select.close()
    },

    destroy() {
        this.destroyed = true
        this.requestSequence += 1
        document.body.classList.remove('crm-searchable-select-open')

        if (this.removeViewportListeners) {
            this.removeViewportListeners()
            this.removeViewportListeners = null
        }

        if (this.removeControlClickListener) {
            this.removeControlClickListener()
            this.removeControlClickListener = null
        }

        if (this.select) {
            this.select.destroy()
            this.select = null
        }
    },
})

export default crmSearchableSelectComponent
