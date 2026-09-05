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

    function preferredCountries() {
        return ['pk', 'ae', 'us', 'gb', 'ca', 'in', 'sa'];
    }

    function countryDataList() {
        if (window.intlTelInput && typeof window.intlTelInput.getCountryData === 'function') {
            return window.intlTelInput.getCountryData();
        }

        var select = document.querySelector('#country');
        if (!select) {
            return [];
        }

        return Array.prototype.map.call(select.options, function (option) {
            return {
                iso2: String(option.value || '').toLowerCase(),
                name: option.textContent,
                dialCode: '',
            };
        }).filter(function (row) {
            return row.iso2;
        });
    }

    function setCountrySelectValue(iso2) {
        var countrySelect = document.querySelector('#country');
        var code = String(iso2 || '').toUpperCase();
        if (!countrySelect || !code || countrySelect.value === code) {
            return;
        }
        if (!countrySelect.querySelector('option[value="' + code + '"]')) {
            return;
        }
        countrySelect.value = code;
        countrySelect.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function bindCountryPicker() {
        var root = document.querySelector('[data-country-picker]');
        var countrySelect = document.querySelector('#country');
        if (!root || !countrySelect || root.dataset.countryBound === '1') {
            return;
        }

        var trigger = root.querySelector('.auth-country-trigger');
        var dropdown = root.querySelector('.auth-country-dropdown');
        var search = root.querySelector('.auth-country-search');
        var list = root.querySelector('.auth-country-list');
        var flag = root.querySelector('[data-country-flag]');
        var nameEl = root.querySelector('[data-country-name]');
        if (!trigger || !dropdown || !search || !list) {
            return;
        }

        var countries = countryDataList();
        if (!countries.length) {
            return;
        }

        root.dataset.countryBound = '1';
        root.classList.add('is-enhanced');
        trigger.hidden = false;

        function paintTrigger(iso2) {
            var code = String(iso2 || '').toLowerCase();
            var row = countries.find(function (item) {
                return item.iso2 === code;
            });
            if (flag) {
                flag.className = 'iti__flag iti__' + code;
            }
            if (nameEl) {
                nameEl.textContent = row ? row.name : code.toUpperCase();
            }
        }

        function renderList(query) {
            var q = String(query || '').trim().toLowerCase();
            var preferred = preferredCountries();
            var matches = countries.filter(function (item) {
                if (!q) {
                    return true;
                }
                return item.name.toLowerCase().indexOf(q) !== -1
                    || item.iso2.indexOf(q) !== -1
                    || String(item.dialCode || '').indexOf(q) !== -1;
            });

            var preferredRows = matches.filter(function (item) {
                return preferred.indexOf(item.iso2) !== -1;
            }).sort(function (a, b) {
                return preferred.indexOf(a.iso2) - preferred.indexOf(b.iso2);
            });
            var rest = matches.filter(function (item) {
                return preferred.indexOf(item.iso2) === -1;
            });

            list.innerHTML = '';
            function addRow(item) {
                var li = document.createElement('li');
                li.setAttribute('role', 'option');
                li.dataset.iso2 = item.iso2;
                li.innerHTML = '<span class="iti__flag-box"><span class="iti__flag iti__'
                    + item.iso2 + '"></span></span><span>'
                    + item.name + '</span>'
                    + (item.dialCode ? '<span class="auth-country-dial">+' + item.dialCode + '</span>' : '');
                if (item.iso2 === String(countrySelect.value || '').toLowerCase()) {
                    li.classList.add('is-active');
                }
                list.appendChild(li);
            }
            preferredRows.forEach(addRow);
            if (preferredRows.length && rest.length) {
                var divider = document.createElement('li');
                divider.className = 'auth-country-divider';
                list.appendChild(divider);
            }
            rest.forEach(addRow);
        }

        function openDropdown() {
            dropdown.hidden = false;
            trigger.setAttribute('aria-expanded', 'true');
            renderList(search.value);
            search.focus();
        }

        function closeDropdown() {
            dropdown.hidden = true;
            trigger.setAttribute('aria-expanded', 'false');
        }

        trigger.addEventListener('click', function () {
            if (dropdown.hidden) {
                openDropdown();
            } else {
                closeDropdown();
            }
        });

        search.addEventListener('input', function () {
            renderList(search.value);
        });

        list.addEventListener('click', function (event) {
            var item = event.target.closest('li[data-iso2]');
            if (!item) {
                return;
            }
            setCountrySelectValue(item.dataset.iso2);
            closeDropdown();
        });

        document.addEventListener('click', function (event) {
            if (!root.contains(event.target)) {
                closeDropdown();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeDropdown();
            }
        });

        countrySelect.addEventListener('change', function () {
            paintTrigger(countrySelect.value);
        });

        paintTrigger(countrySelect.value || root.getAttribute('data-initial-country') || 'pk');
    }

    function syncCountrySelect(instances) {
        var countrySelect = document.querySelector('#country');
        if (!countrySelect) {
            return;
        }

        countrySelect.addEventListener('change', function () {
            var code = String(countrySelect.value || '').toLowerCase();
            if (!code) {
                return;
            }
            instances.forEach(function (entry) {
                if (!entry.iti) {
                    return;
                }
                try {
                    entry.iti.setCountry(code);
                } catch (e) {
                    // ignore unknown ISO codes
                }
            });
        });

        instances.forEach(function (entry) {
            if (!entry.input || !entry.iti) {
                return;
            }
            entry.input.addEventListener('countrychange', function () {
                var data = typeof entry.iti.getSelectedCountryData === 'function'
                    ? entry.iti.getSelectedCountryData()
                    : null;
                if (data && data.iso2) {
                    setCountrySelectValue(data.iso2);
                }
            });
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

        bindCountryPicker();

        nodes.forEach(function (input) {
            var iti = bindPhoneInput(input);
            if (iti) {
                instances.push({ input: input, iti: iti });
            }
        });

        if (instances.length) {
            syncCountrySelect(instances);
        }

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
