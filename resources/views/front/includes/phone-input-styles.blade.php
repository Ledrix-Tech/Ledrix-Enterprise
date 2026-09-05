<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@25.3.0/build/css/intlTelInput.css">
<style>
    .auth-phone-field,
    .mkt-phone-field {
        width: 100%;
    }
    .auth-phone-field .iti,
    .mkt-phone-field .iti {
        width: 100%;
        max-width: 100%;
    }
    .auth-phone-field .iti__country-list,
    .mkt-phone-field .iti__country-list {
        z-index: 30;
    }
    .auth-phone-field .form-control,
    .mkt-phone-field .form-control {
        width: 100%;
    }
    .auth-country-field {
        position: relative;
        width: 100%;
    }
    .auth-country-field.is-enhanced .auth-country-native {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        border: 0;
    }
    .auth-country-trigger {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        width: 100%;
        min-height: 46px;
        padding: 0.45rem 0.9rem;
        border: 1px solid #cbd5e1;
        border-radius: 12px;
        background: #fff;
        color: #1e293b;
        text-align: left;
    }
    .auth-country-trigger:focus {
        outline: 0;
        border-color: var(--ledrix-primary-light, #6366f1);
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.12);
    }
    .auth-country-trigger .iti__arrow {
        margin-left: auto;
    }
    .auth-country-name {
        font-size: 0.95rem;
        font-weight: 500;
    }
    .auth-country-dropdown {
        position: absolute;
        left: 0;
        right: 0;
        top: calc(100% + 4px);
        z-index: 40;
        background: #fff;
        border: 1px solid #cbd5e1;
        border-radius: 12px;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.12);
        overflow: hidden;
    }
    .auth-country-search {
        width: 100%;
        border: 0;
        border-bottom: 1px solid #e2e8f0;
        padding: 0.65rem 0.85rem;
        font-size: 0.9rem;
    }
    .auth-country-search:focus {
        outline: 0;
    }
    .auth-country-list {
        list-style: none;
        margin: 0;
        padding: 0.25rem 0;
        max-height: 240px;
        overflow-y: auto;
    }
    .auth-country-list li {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        padding: 0.4rem 0.85rem;
        cursor: pointer;
        font-size: 0.9rem;
    }
    .auth-country-list li:hover,
    .auth-country-list li.is-active {
        background: #eef2ff;
    }
    .auth-country-list .auth-country-divider {
        height: 1px;
        padding: 0;
        margin: 0.25rem 0;
        background: #e2e8f0;
        cursor: default;
        pointer-events: none;
    }
    .auth-country-list .auth-country-dial {
        margin-left: auto;
        color: #64748b;
        font-size: 0.8rem;
    }
</style>
