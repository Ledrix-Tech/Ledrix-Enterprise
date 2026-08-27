(function () {
    'use strict';

    function initialCountryFromPage() {
        var countrySelect = document.querySelector('#country');
        if (countrySelect && countrySelect.value) {
            return String(countrySelect.value).toLowerCase();
        }
        return 'pk';
    }

    function bindPhoneInput(phoneInput) {
        if (!phoneInput || phoneInput.dataset.itiBound === '1' || !window.intlTelInput) {
            return null;
        }

        var preferred = ['pk', 'ae', 'us', 'gb', 'ca', 'in', 'sa'];
        var initial = (phoneInput.getAttribute('data-initial-country') || initialCountryFromPage() || 'pk').toLowerCase();
        var existing = (phoneInput.value || '').trim();

        // Avoid putting full E.164 into the national field when separateDialCode is on
        // (breaks old() reloads and makes isValidNumber fail).
        if (existing.indexOf('+') === 0) {
            phoneInput.value = '';
        }

        var iti = window.intlTelInput(phoneInput, {
            initialCountry: initial,
            preferredCountries: preferred,
            separateDialCode: true,
            nationalMode: true,
            autoPlaceholder: 'aggressive',
            formatOnDisplay: true,
            utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@25.3.0/build/js/utils.js',
        });

        phoneInput.dataset.itiBound = '1';

        if (existing.indexOf('+') === 0 && typeof iti.setNumber === 'function') {
            try {
                iti.setNumber(existing);
            } catch (e) {
                // ignore malformed old values
            }
        }

        var wrapper = phoneInput.closest('.iti');
        if (wrapper) {
            wrapper.style.width = '100%';
            wrapper.style.maxWidth = '100%';
        }
        phoneInput.style.width = '100%';

        return iti;
    }

    function syncCountrySelect(iti) {
        var countrySelect = document.querySelector('#country');
        if (!countrySelect || !iti) {
            return;
        }

        countrySelect.addEventListener('change', function () {
            var code = String(countrySelect.value || '').toLowerCase();
            if (code) {
                try {
                    iti.setCountry(code);
                } catch (e) {
                    // ignore unknown ISO codes
                }
            }
        });
    }

    function attachFormGuard(form, instances) {
        if (!form || form.dataset.phoneGuardBound === '1') {
            return;
        }
        form.dataset.phoneGuardBound = '1';

        form.addEventListener('submit', function (event) {
            var invalid = false;

            instances.forEach(function (entry) {
                var input = entry.input;
                var iti = entry.iti;
                if (!input || !iti) {
                    return;
                }

                var raw = (input.value || '').trim();
                var required = input.hasAttribute('required') || input.getAttribute('data-phone-required') === '1';

                if (!raw) {
                    if (required) {
                        invalid = true;
                        input.classList.add('is-invalid');
                        input.setCustomValidity('Phone number with country code is required.');
                    } else {
                        input.value = '';
                        input.setCustomValidity('');
                        input.classList.remove('is-invalid');
                    }
                    return;
                }

                // Prefer E.164; fall back to dial + national if utils not ready.
                var e164 = '';
                try {
                    e164 = iti.getNumber() || '';
                } catch (e) {
                    e164 = '';
                }

                if (!e164) {
                    var data = typeof iti.getSelectedCountryData === 'function'
                        ? iti.getSelectedCountryData()
                        : null;
                    var dial = data && data.dialCode ? String(data.dialCode) : '';
                    var digits = raw.replace(/\D/g, '').replace(/^0+/, '');
                    if (dial && digits) {
                        e164 = '+' + dial + digits;
                    }
                }

                if (typeof iti.isValidNumber === 'function' && window.intlTelInputUtils && !iti.isValidNumber()) {
                    // Still accept if we built a plausible E.164 (server re-checks).
                    if (!/^\+[1-9]\d{7,14}$/.test(e164)) {
                        invalid = true;
                        input.classList.add('is-invalid');
                        input.setCustomValidity('Enter a valid phone number for the selected country.');
                        return;
                    }
                }

                if (!/^\+[1-9]\d{7,14}$/.test(e164)) {
                    invalid = true;
                    input.classList.add('is-invalid');
                    input.setCustomValidity('Enter a valid phone number for the selected country.');
                    return;
                }

                input.setCustomValidity('');
                input.classList.remove('is-invalid');
                input.value = e164;
            });

            if (invalid) {
                event.preventDefault();
                var firstBad = form.querySelector('.is-invalid');
                if (firstBad) {
                    firstBad.focus();
                    firstBad.reportValidity();
                }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var nodes = document.querySelectorAll(
            'input[data-phone-input], #phone, #billing-phone, #billing_phone'
        );
        var instances = [];

        nodes.forEach(function (input) {
            var iti = bindPhoneInput(input);
            if (iti) {
                instances.push({ input: input, iti: iti });
                if (input.id === 'phone' || input.getAttribute('data-phone-sync-country') === '1') {
                    syncCountrySelect(iti);
                }
            }
        });

        if (!instances.length) {
            return;
        }

        var forms = new Set();
        instances.forEach(function (entry) {
            if (entry.input.form) {
                forms.add(entry.input.form);
            }
        });
        forms.forEach(function (form) {
            attachFormGuard(form, instances.filter(function (entry) {
                return entry.input.form === form;
            }));
        });
    });
})();
