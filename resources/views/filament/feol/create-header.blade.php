<style>
    .fi-page:has(.feol-partner-form-card) .fi-page-content {
        width: min(100%, 760px);
        margin-inline: auto;
    }

    .fi-page:has(.feol-partner-form-card) .crm-record-form-frame {
        grid-template-columns: minmax(0, 1fr) !important;
        width: 100%;
    }

    .feol-create-brand {
        display: flex;
        justify-content: center;
        padding: 4px 16px 2px;
    }

    .feol-create-brand img {
        width: min(100%, 220px);
        height: auto;
        display: block;
    }

    .feol-partner-form-card {
        width: 100%;
        min-width: 0;
        border: 1px solid #d9e1ec !important;
        border-radius: 8px !important;
        background: #fff !important;
        box-shadow: none !important;
    }

    .feol-partner-form-card input {
        min-height: 40px;
    }

    @media (max-width: 767px) {
        .fi-page:has(.feol-partner-form-card) .fi-page-content {
            width: 100%;
        }

        .feol-create-brand img {
            width: min(100%, 180px);
        }
    }
</style>

<div class="feol-create-brand" aria-label="FE CREDIT">
    <img src="{{ asset('images/fe-credit.svg') }}" alt="FE CREDIT">
</div>
