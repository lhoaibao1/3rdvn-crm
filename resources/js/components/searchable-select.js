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
    removeGestureGuard: null,

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
            openOnFocus: true,
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
        this.installGestureGuard()

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

        try {
            const results = await config.getOptionsUsing()

            if (
                this.destroyed
                || requestId !== this.requestSequence
                || !this.select
            ) {
                return
            }

            const prepared = this.prepareOptions(results)
            this.select.clearOptionGroups()
            this.registerGroups(prepared.groups)
            this.select.clearOptions()
            this.select.addOptions(prepared.options)
            this.select.refreshOptions(false)
            this.syncFromState(this.state)
        } catch {
            // Keep the last valid option set when the server cannot refresh.
        }
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

    installGestureGuard() {
        const dropdown = this.select?.dropdown_content

        if (!dropdown) {
            return
        }

        const hasPointerEvents = Boolean(window.PointerEvent)

        const swipeThreshold = 8
        let gesture = null
        let suppressOptionClickUntil = 0

        const onPointerDown = (event) => {
            if (event.isPrimary === false || event.button > 0) {
                return
            }

            gesture = {
                pointerId: event.pointerId,
                x: event.clientX,
                y: event.clientY,
                scrollTop: dropdown.scrollTop,
                moved: false,
            }
        }

        const onPointerMove = (event) => {
            if (!gesture || event.pointerId !== gesture.pointerId) {
                return
            }

            const moved = Math.hypot(
                event.clientX - gesture.x,
                event.clientY - gesture.y,
            ) >= swipeThreshold

            if (moved || Math.abs(dropdown.scrollTop - gesture.scrollTop) > 1) {
                gesture.moved = true
            }
        }

        const onPointerUp = (event) => {
            if (!gesture || event.pointerId !== gesture.pointerId) {
                return
            }

            if (gesture.moved) {
                suppressOptionClickUntil = performance.now() + 250
            }

            gesture = null
        }

        const onPointerCancel = () => {
            gesture = null
        }

        const touchAsPointer = (touch) => ({
            pointerId: touch.identifier,
            clientX: touch.clientX,
            clientY: touch.clientY,
            isPrimary: true,
            button: 0,
        })

        const onTouchStart = (event) => {
            const touch = event.touches[0]

            if (touch) {
                onPointerDown(touchAsPointer(touch))
            }
        }

        const onTouchMove = (event) => {
            const touch = Array.from(event.touches).find((item) => (
                item.identifier === gesture?.pointerId
            ))

            if (touch) {
                onPointerMove(touchAsPointer(touch))
            }
        }

        const onTouchEnd = (event) => {
            const touch = Array.from(event.changedTouches).find((item) => (
                item.identifier === gesture?.pointerId
            ))

            if (touch) {
                onPointerUp(touchAsPointer(touch))
            }
        }

        const onClickCapture = (event) => {
            if (
                performance.now() > suppressOptionClickUntil
                || !event.target.closest('[data-selectable]')
            ) {
                return
            }

            suppressOptionClickUntil = 0
            event.preventDefault()
            event.stopImmediatePropagation()
        }

        if (hasPointerEvents) {
            dropdown.addEventListener('pointerdown', onPointerDown, { passive: true })
            dropdown.addEventListener('pointermove', onPointerMove, { passive: true })
            dropdown.addEventListener('pointerup', onPointerUp, { passive: true })
            dropdown.addEventListener('pointercancel', onPointerCancel, { passive: true })
        } else {
            dropdown.addEventListener('touchstart', onTouchStart, { passive: true })
            dropdown.addEventListener('touchmove', onTouchMove, { passive: true })
            dropdown.addEventListener('touchend', onTouchEnd, { passive: true })
            dropdown.addEventListener('touchcancel', onPointerCancel, { passive: true })
            dropdown.addEventListener('mousedown', onPointerDown, { passive: true })
            dropdown.addEventListener('mousemove', onPointerMove, { passive: true })
            dropdown.addEventListener('mouseup', onPointerUp, { passive: true })
        }
        dropdown.addEventListener('click', onClickCapture, true)

        this.removeGestureGuard = () => {
            if (hasPointerEvents) {
                dropdown.removeEventListener('pointerdown', onPointerDown)
                dropdown.removeEventListener('pointermove', onPointerMove)
                dropdown.removeEventListener('pointerup', onPointerUp)
                dropdown.removeEventListener('pointercancel', onPointerCancel)
            } else {
                dropdown.removeEventListener('touchstart', onTouchStart)
                dropdown.removeEventListener('touchmove', onTouchMove)
                dropdown.removeEventListener('touchend', onTouchEnd)
                dropdown.removeEventListener('touchcancel', onPointerCancel)
                dropdown.removeEventListener('mousedown', onPointerDown)
                dropdown.removeEventListener('mousemove', onPointerMove)
                dropdown.removeEventListener('mouseup', onPointerUp)
            }
            dropdown.removeEventListener('click', onClickCapture, true)
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

        if (this.removeGestureGuard) {
            this.removeGestureGuard()
            this.removeGestureGuard = null
        }

        if (this.select) {
            this.select.destroy()
            this.select = null
        }
    },
})

export default crmSearchableSelectComponent
