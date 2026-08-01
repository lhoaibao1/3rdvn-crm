@auth
    @php($draftScript = public_path('js/crm-form-drafts.js'))
    <script
        src="{{ asset('js/crm-form-drafts.js') }}?v={{ is_file($draftScript) ? filemtime($draftScript) : now()->timestamp }}"
        data-crm-user-id="{{ auth()->id() }}"
        data-crm-draft-ttl="86400000"
        data-navigate-once
        defer
    ></script>
@endauth
