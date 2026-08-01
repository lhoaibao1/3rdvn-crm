(() => {
    'use strict';

    const script = document.currentScript;
    const userId = script?.dataset.crmUserId;

    if (!userId) return;

    if (window.__crmFormDrafts?.version === 2) {
        window.__crmFormDrafts.refresh();
        return;
    }

    const ttl = Number(script.dataset.crmDraftTtl || 86_400_000);
    const prefix = `3rdvn:form-draft:v2:${userId}:`;
    const legacyPrefix = `3rdvn:form-draft:v1:${userId}:`;
    const sensitiveKeyPattern = /password|passwd|passphrase|token|secret|otp|api[_-]?key/i;
    const sensitivePathPattern = /(^|[._-])(password|passwd|passphrase|token|secret|otp|api[_-]?key)([._-]|$)/i;
    const userDateKeys = new Set(['date_of_birth', 'identity_issued_date', 'hire_date']);
    const restoredScopes = new WeakSet();
    const saveTimers = new Map();
    const dirtyScopes = new Map();
    const touchedKeys = new Set();
    const pendingSubmissions = new Map();
    const fileWarnings = new Set();
    let noticeTimer = null;
    let scanTimer = null;
    let livewireHookRegistered = false;
    let navigationStarted = false;
    let restoreSuppression = 0;
    let activeScope = null;

    const storage = resolveStorage();

    if (!storage) return;

    removeLegacyUi();
    purgeLegacyDrafts();

    function resolveStorage() {
        const candidates = [];

        try {
            candidates.push(window.localStorage);
        } catch (_) {
            // Browser storage can be disabled by policy.
        }

        try {
            candidates.push(window.sessionStorage);
        } catch (_) {
            // Keep trying the next available store.
        }

        return candidates.find((candidate) => {
            try {
                const key = `${prefix}probe`;
                candidate.setItem(key, '1');
                candidate.removeItem(key);

                return true;
            } catch (_) {
                return false;
            }
        }) || null;
    }

    function removeLegacyUi() {
        document.getElementById('crm-draft-indicator')?.remove();
        document.getElementById('crm-draft-styles')?.remove();
    }

    function purgeLegacyDrafts() {
        for (let index = storage.length - 1; index >= 0; index -= 1) {
            const key = storage.key(index);
            if (key?.startsWith(legacyPrefix)) storage.removeItem(key);
        }
    }

    function routeKey() {
        const url = new URL(window.location.href);
        const stableQuery = new URLSearchParams();

        ['project'].forEach((name) => {
            if (url.searchParams.has(name)) stableQuery.set(name, url.searchParams.get(name));
        });

        const query = stableQuery.toString();
        const path = url.pathname.replace(/\/+$/, '') || '/';

        return query ? `${path}?${query}` : path;
    }

    function normalizeLabel(value) {
        return String(value || '')
            .replace(/\s+/g, ' ')
            .trim()
            .toLocaleLowerCase('vi-VN')
            .slice(0, 120);
    }

    function scopeFor(element) {
        return element?.closest?.('.fi-modal-window, [role="dialog"]')
            || element?.closest?.('form')
            || element?.closest?.('.fi-page')
            || document.querySelector('.fi-page')
            || document.body;
    }

    function scopeLabel(scope) {
        if (scope.matches?.('.fi-modal-window, [role="dialog"]')) {
            const labelledBy = scope.getAttribute('aria-labelledby');
            const labelledElement = labelledBy ? document.getElementById(labelledBy) : null;
            const heading = labelledElement
                || scope.querySelector('.fi-modal-heading, [data-slot="heading"], h1, h2, h3');

            return `modal:${normalizeLabel(heading?.textContent) || 'action'}`;
        }

        if (scope.matches?.('form')) {
            const submitAction = [...scope.attributes]
                .find((attribute) => attribute.name === 'wire:submit' || attribute.name.startsWith('wire:submit.'))
                ?.value;

            return `form:${normalizeLabel(submitAction || scope.id || 'main')}`;
        }

        return 'main';
    }

    function hash(value) {
        let result = 2166136261;

        for (let index = 0; index < value.length; index += 1) {
            result ^= value.charCodeAt(index);
            result = Math.imul(result, 16777619);
        }

        return (result >>> 0).toString(36);
    }

    function keyForScope(scope) {
        return `${prefix}${hash(`${routeKey()}|${scopeLabel(scope)}`)}`;
    }

    function statePathFor(element) {
        if (!(element instanceof Element)) return null;

        const customSelect = element.matches('[data-crm-draft-state-path]')
            ? element
            : element.closest('[data-crm-draft-state-path]');
        const customPath = customSelect?.dataset.crmDraftStatePath;

        if (customPath) return customPath;

        const modelAttribute = [...element.attributes]
            .find((attribute) => attribute.name === 'wire:model' || attribute.name.startsWith('wire:model.'));

        return modelAttribute?.value || null;
    }

    function rootForPath(path) {
        if (!path || sensitivePathPattern.test(path)) return null;
        if (path === 'data' || path.startsWith('data.')) return 'data';

        return path.match(/^(mounted(?:Table|FormComponent)?Actions\.\d+\.data)(?:\.|$)/)?.[1] || null;
    }

    function relativePath(root, path) {
        return path === root ? '' : path.slice(root.length + 1);
    }

    function isTrackable(element, path) {
        if (!element || !rootForPath(path) || element.closest('[data-crm-draft-ignore]')) return false;

        if (element.matches('input')) {
            const type = String(element.type || '').toLowerCase();
            const autocomplete = String(element.autocomplete || '').toLowerCase();

            if (['password', 'submit', 'button', 'reset'].includes(type)) return false;
            if (autocomplete.includes('password') || autocomplete.includes('one-time-code')) return false;
        }

        if ('disabled' in element && element.disabled) return false;
        if ('readOnly' in element && element.readOnly) return false;

        return true;
    }

    function rootsInScope(scope) {
        const roots = new Map();
        const elements = scope.querySelectorAll('input, textarea, select, [data-crm-draft-state-path]');

        elements.forEach((element) => {
            const path = statePathFor(element);

            if (!isTrackable(element, path)) return;

            const root = rootForPath(path);
            const entry = roots.get(root) || { element, excludedPaths: new Set() };

            if (element.matches?.('input[type="file"]')) {
                const excludedPath = relativePath(root, path);
                if (excludedPath) entry.excludedPaths.add(excludedPath);
            } else if (!entry.element?.isConnected) {
                entry.element = element;
            }

            roots.set(root, entry);
        });

        return roots;
    }

    function componentFor(element) {
        const root = element?.closest?.('[wire\\:id]');
        const id = root?.getAttribute('wire:id');

        if (!id || !window.Livewire?.find) return null;

        try {
            return window.Livewire.find(id);
        } catch (_) {
            return null;
        }
    }

    function cloneValue(value, sanitizeSensitive = true) {
        try {
            const serialized = JSON.stringify(value, (key, item) => {
                if (sanitizeSensitive && key && sensitiveKeyPattern.test(key)) return undefined;
                if (typeof File !== 'undefined' && item instanceof File) return undefined;
                if (typeof Blob !== 'undefined' && item instanceof Blob) return undefined;

                return item;
            });

            return serialized === undefined ? undefined : JSON.parse(serialized);
        } catch (_) {
            return undefined;
        }
    }

    function pathSegments(path) {
        return String(path || '').split('.').filter(Boolean);
    }

    function getAtPath(value, path) {
        return pathSegments(path).reduce((current, segment) => current?.[segment], value);
    }

    function hasAtPath(value, path) {
        const segments = pathSegments(path);
        let current = value;

        for (const segment of segments) {
            if (current === null || typeof current !== 'object' || !(segment in current)) return false;
            current = current[segment];
        }

        return true;
    }

    function setAtPath(value, path, replacement) {
        const segments = pathSegments(path);
        const finalSegment = segments.pop();
        let current = value;

        for (const segment of segments) {
            if (current[segment] === null || typeof current[segment] !== 'object') {
                current[segment] = /^\d+$/.test(segment) ? [] : {};
            }
            current = current[segment];
        }

        if (finalSegment !== undefined) current[finalSegment] = cloneValue(replacement, false);
    }

    function deleteAtPath(value, path) {
        const segments = pathSegments(path);
        const finalSegment = segments.pop();
        let current = value;

        for (const segment of segments) {
            if (current === null || typeof current !== 'object' || !(segment in current)) return;
            current = current[segment];
        }

        if (current && typeof current === 'object' && finalSegment !== undefined) delete current[finalSegment];
    }

    function isObject(value) {
        return value !== null && typeof value === 'object';
    }

    function deepMerge(current, saved) {
        if (Array.isArray(saved)) {
            const result = Array.isArray(current) ? cloneValue(current, false) : [];
            Object.keys(saved).forEach((key) => {
                result[key] = deepMerge(result[key], saved[key]);
            });
            return result;
        }

        if (isObject(saved)) {
            const result = isObject(current) && !Array.isArray(current) ? cloneValue(current, false) : {};
            Object.entries(saved).forEach(([key, item]) => {
                if (!sensitiveKeyPattern.test(key)) result[key] = deepMerge(result[key], item);
            });
            return result;
        }

        return cloneValue(saved);
    }

    function normalizeDraftDates(value, key = null) {
        if (Array.isArray(value)) {
            return value.map((item) => normalizeDraftDates(item));
        }

        if (isObject(value)) {
            return Object.fromEntries(
                Object.entries(value).map(([childKey, item]) => [childKey, normalizeDraftDates(item, childKey)]),
            );
        }

        if (!userDateKeys.has(key) || typeof value !== 'string') return value;

        const match = value.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
        if (!match) return value;

        const [, day, month, year] = match;
        const normalized = `${year}-${month}-${day}`;
        const parsed = new Date(`${normalized}T00:00:00Z`);

        return parsed.getUTCFullYear() === Number(year)
            && parsed.getUTCMonth() + 1 === Number(month)
            && parsed.getUTCDate() === Number(day)
            ? normalized
            : value;
    }

    function payloadForScope(scope) {
        const roots = [];

        rootsInScope(scope).forEach(({ element, excludedPaths }, root) => {
            const component = componentFor(element);
            if (!component?.$wire?.$get) return;

            try {
                const value = cloneValue(component.$wire.$get(root));
                if (value === undefined) return;

                const exclusions = [...excludedPaths];
                exclusions.forEach((path) => deleteAtPath(value, path));
                roots.push({ root, value, excludedPaths: exclusions });
            } catch (_) {
                // Skip a root that Livewire no longer owns.
            }
        });

        const now = Date.now();

        return {
            version: 2,
            route: routeKey(),
            scope: scopeLabel(scope),
            savedAt: now,
            expiresAt: now + ttl,
            roots,
        };
    }

    function pruneDrafts(removeOldest = false) {
        const drafts = [];

        for (let index = storage.length - 1; index >= 0; index -= 1) {
            const key = storage.key(index);

            if (!key?.startsWith(prefix)) continue;

            try {
                const value = JSON.parse(storage.getItem(key));

                if (!value?.expiresAt || value.expiresAt <= Date.now()) {
                    storage.removeItem(key);
                    continue;
                }

                drafts.push({ key, savedAt: Number(value.savedAt || 0) });
            } catch (_) {
                storage.removeItem(key);
            }
        }

        if (removeOldest && drafts.length) {
            drafts.sort((left, right) => left.savedAt - right.savedAt);
            storage.removeItem(drafts[0].key);
        }
    }

    function showNotice(message, tone = 'error', hideAfter = 5000) {
        let notice = document.getElementById('crm-draft-notice');

        if (!document.getElementById('crm-draft-notice-styles')) {
            const style = document.createElement('style');
            style.id = 'crm-draft-notice-styles';
            style.textContent = `
                #crm-draft-notice {
                    position: fixed;
                    z-index: 2147482500;
                    left: 50%;
                    bottom: 18px;
                    transform: translateX(-50%);
                    max-width: min(92vw, 620px);
                    padding: 10px 14px;
                    border-radius: 10px;
                    background: rgba(15, 23, 42, .96);
                    color: white;
                    box-shadow: 0 12px 36px rgba(15, 23, 42, .22);
                    font: 600 13px/1.35 ui-sans-serif, system-ui, sans-serif;
                }
                #crm-draft-notice[hidden] { display: none !important; }
                #crm-draft-notice[data-tone="error"] { background: rgba(153, 27, 27, .97); }
                #crm-draft-notice[data-tone="restored"] { background: rgba(30, 64, 175, .97); }
            `;
            document.head.appendChild(style);
        }

        if (!notice) {
            notice = document.createElement('div');
            notice.id = 'crm-draft-notice';
            notice.hidden = true;
            notice.setAttribute('role', 'status');
            notice.setAttribute('aria-live', 'polite');
            document.body.appendChild(notice);
        }

        notice.dataset.tone = tone;
        notice.textContent = message;
        notice.hidden = false;

        if (noticeTimer) window.clearTimeout(noticeTimer);
        noticeTimer = window.setTimeout(() => {
            notice.hidden = true;
        }, hideAfter);
    }

    function writePayload(key, payload) {
        const serialized = JSON.stringify(payload);

        if (serialized.length > 524_288) {
            showNotice('Bản nháp quá lớn; phần file tải lên vẫn cần chọn lại.');
            return false;
        }

        try {
            storage.setItem(key, serialized);
            return true;
        } catch (_) {
            pruneDrafts(true);

            try {
                storage.setItem(key, serialized);
                return true;
            } catch (_retryError) {
                showNotice('Trình duyệt không còn chỗ lưu bản nháp.');
                return false;
            }
        }
    }

    function saveScope(scope, { force = false } = {}) {
        if (!scope?.isConnected) return null;

        const key = keyForScope(scope);
        if (!force && !dirtyScopes.has(key)) return null;

        const payload = payloadForScope(scope);
        if (!payload.roots.length || !writePayload(key, payload)) return null;

        dirtyScopes.delete(key);
        activeScope = scope;
        window.dispatchEvent(new CustomEvent('crm:draft-saved', {
            detail: { rootCount: payload.roots.length, savedAt: payload.savedAt },
        }));

        return key;
    }

    function markDirty(scope) {
        if (!scope?.isConnected) return;

        const key = keyForScope(scope);
        dirtyScopes.set(key, scope);
        touchedKeys.add(key);
        activeScope = scope;

        const timer = saveTimers.get(key);
        if (timer) window.clearTimeout(timer);

        saveTimers.set(key, window.setTimeout(() => {
            saveTimers.delete(key);
            saveScope(scope);
        }, 400));
    }

    function readDraft(scope) {
        const key = keyForScope(scope);
        const raw = storage.getItem(key);

        if (!raw) return null;

        try {
            const payload = JSON.parse(raw);

            if (
                payload?.version !== 2
                || payload.route !== routeKey()
                || payload.scope !== scopeLabel(scope)
                || payload.expiresAt <= Date.now()
                || !Array.isArray(payload.roots)
            ) {
                storage.removeItem(key);
                return null;
            }

            payload.roots = normalizeDraftDates(payload.roots);
            return { key, payload };
        } catch (_) {
            storage.removeItem(key);
            return null;
        }
    }

    async function restoreScope(scope) {
        if (!scope?.isConnected || restoredScopes.has(scope)) return;

        const availableRoots = rootsInScope(scope);
        if (!availableRoots.size) return;

        restoredScopes.add(scope);
        const draft = readDraft(scope);
        if (!draft) return;

        const updates = [];
        let restoredCount = 0;
        restoreSuppression += 1;

        draft.payload.roots.forEach(({ root, value, excludedPaths = [] }) => {
            const available = availableRoots.get(root);
            const component = componentFor(available?.element);
            if (!available || !component?.$wire?.$get || !component?.$wire?.$set) return;

            try {
                const current = cloneValue(component.$wire.$get(root), false);
                const merged = deepMerge(current, value);

                excludedPaths.forEach((path) => {
                    if (hasAtPath(current, path)) {
                        setAtPath(merged, path, getAtPath(current, path));
                    } else {
                        deleteAtPath(merged, path);
                    }
                });

                updates.push(component.$wire.$set(root, merged, false));
                restoredCount += 1;
            } catch (_) {
                // Leave this root at its server value.
            }
        });

        await Promise.allSettled(updates);
        window.setTimeout(() => {
            restoreSuppression = Math.max(0, restoreSuppression - 1);
        }, 900);

        if (!restoredCount) return;

        touchedKeys.add(draft.key);
        activeScope = scope;
        showNotice('Đã khôi phục dữ liệu nhập dở.', 'restored', 2800);
        window.dispatchEvent(new CustomEvent('crm:draft-restored', {
            detail: { rootCount: restoredCount, savedAt: draft.payload.savedAt },
        }));
    }

    function clearDraft(key) {
        if (!key) return;

        storage.removeItem(key);
        const timer = saveTimers.get(key);
        if (timer) window.clearTimeout(timer);
        saveTimers.delete(key);
        dirtyScopes.delete(key);
        touchedKeys.delete(key);
        pendingSubmissions.delete(key);
    }

    function markPending(scope) {
        const key = keyForScope(scope);
        if (!touchedKeys.has(key) && !storage.getItem(key)) return;

        const savedKey = saveScope(scope, { force: true });
        if (!savedKey) return;

        const firstRoot = rootsInScope(scope).values().next().value;
        const component = componentFor(firstRoot?.element);

        pendingSubmissions.set(savedKey, {
            at: Date.now(),
            componentId: component?.id || component?.__instance?.id || null,
            scope,
        });
    }

    function clearSuccessfulPending(componentId = null) {
        const now = Date.now();
        let cleared = false;

        pendingSubmissions.forEach((submission, key) => {
            const isRecent = now - submission.at < 60_000;
            const matchesComponent = !componentId || !submission.componentId || submission.componentId === componentId;

            if (!isRecent || !matchesComponent) return;

            clearDraft(key);
            cleared = true;
        });

        if (cleared) window.dispatchEvent(new CustomEvent('crm:draft-cleared'));
    }

    function pendingForComponent(componentId) {
        return [...pendingSubmissions.values()].some((submission) => (
            Date.now() - submission.at < 60_000
            && (!componentId || !submission.componentId || submission.componentId === componentId)
        ));
    }

    function registerLivewireHook() {
        if (livewireHookRegistered || !window.Livewire?.hook) return;

        livewireHookRegistered = true;
        window.Livewire.hook('commit', ({ component, succeed, fail }) => {
            const componentId = component?.id;

            succeed(({ effects }) => {
                if (effects?.redirect) clearSuccessfulPending(componentId);
                scheduleScan();
            });

            fail(() => {
                if (!pendingForComponent(componentId)) return;

                pendingSubmissions.forEach((submission) => {
                    if (!componentId || !submission.componentId || submission.componentId === componentId) {
                        saveScope(submission.scope, { force: true });
                    }
                });
                showNotice('Server đang lỗi; dữ liệu nhập vẫn được giữ trong bản nháp.', 'error', 6000);
            });
        });
    }

    function currentScopes() {
        const scopes = new Set();

        document.querySelectorAll('input, textarea, select, [data-crm-draft-state-path]').forEach((element) => {
            const path = statePathFor(element);
            if (isTrackable(element, path)) scopes.add(scopeFor(element));
        });

        return [...scopes].filter(Boolean);
    }

    function scan() {
        registerLivewireHook();
        currentScopes().forEach((scope) => restoreScope(scope));
    }

    function scheduleScan() {
        if (scanTimer) window.clearTimeout(scanTimer);
        scanTimer = window.setTimeout(scan, 120);
    }

    function isSuccessNotification(node) {
        return node instanceof Element && (
            node.matches('.fi-no-notification.fi-status-success')
            || Boolean(node.querySelector('.fi-no-notification.fi-status-success'))
        );
    }

    function nodeMayContainFields(node) {
        return node instanceof Element && (
            node.matches('input, textarea, select, [data-crm-draft-state-path], form, [role="dialog"]')
            || Boolean(node.querySelector('input, textarea, select, [data-crm-draft-state-path], form, [role="dialog"]'))
        );
    }

    function handleChange(event) {
        if (restoreSuppression > 0) return;

        const element = event.target;
        const path = statePathFor(element);
        if (!isTrackable(element, path)) return;

        const scope = scopeFor(element);
        const key = keyForScope(scope);
        markDirty(scope);

        if (element.matches?.('input[type="file"]') && !fileWarnings.has(key)) {
            fileWarnings.add(key);
            showNotice('Phần chữ đã được giữ; file tải lên cần chọn lại nếu trang bị lỗi.', 'error', 4500);
        }
    }

    document.addEventListener('input', handleChange, true);
    document.addEventListener('change', handleChange, true);

    document.addEventListener('submit', (event) => {
        const scope = scopeFor(event.target);
        if (rootsInScope(scope).size) markPending(scope);
    }, true);

    document.addEventListener('click', (event) => {
        const button = event.target.closest?.('button, [role="button"]');
        if (!button || button.disabled) return;

        const wireClick = [...button.attributes]
            .find((attribute) => attribute.name === 'wire:click' || attribute.name.startsWith('wire:click.'))
            ?.value || '';
        const isSubmit = button.matches('button[type="submit"], input[type="submit"]');
        const isFilamentAction = /callMounted(?:Table|FormComponent)?Action|\b(create|save)\b/i.test(wireClick);

        if (!isSubmit && !isFilamentAction) return;
        if (/hủy|đóng/i.test(button.textContent || '')) return;

        const scope = scopeFor(button);
        if (rootsInScope(scope).size) markPending(scope);
    }, true);

    document.addEventListener('livewire:init', registerLivewireHook);
    document.addEventListener('livewire:navigating', () => {
        navigationStarted = true;
        dirtyScopes.forEach((scope) => saveScope(scope));
    });
    document.addEventListener('livewire:navigated', () => {
        navigationStarted = false;
        activeScope = null;
        scheduleScan();
    });

    window.addEventListener('beforeunload', () => {
        if (navigationStarted) return;
        dirtyScopes.forEach((scope) => saveScope(scope));
    });
    window.addEventListener('offline', () => {
        dirtyScopes.forEach((scope) => saveScope(scope));
        showNotice('Mất kết nối; dữ liệu nhập vẫn được giữ trong bản nháp.', 'error', 6000);
    });

    const observer = new MutationObserver((mutations) => {
        let shouldScan = false;
        let hasSuccess = false;

        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (isSuccessNotification(node)) hasSuccess = true;
                if (nodeMayContainFields(node)) shouldScan = true;
            });
        });

        if (hasSuccess) clearSuccessfulPending();
        if (shouldScan) scheduleScan();
    });

    observer.observe(document.body, { childList: true, subtree: true });
    pruneDrafts();

    window.__crmFormDrafts = {
        version: 2,
        refresh: scheduleScan,
    };

    scan();
})();
