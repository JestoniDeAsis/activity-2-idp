(function () {
    'use strict';

    var form = document.getElementById('register-form');
    if (!form) { return; }

    var countries = window.APP_COUNTRIES || [];
    var allowedDomains = window.APP_EMAIL_DOMAINS || [];
    var zipLookup = window.APP_ZIP_LOOKUP || {};
    var urls = window.APP_LOCATION_URLS || {};
    var old = window.APP_OLD || {};
    var touched = {};
    var seq = { state: 0, city: 0, zip_code: 0 };
    var restoring = true; // true while the page rebuilds the dropdowns after a server error

    // Same character rule as RegisterRequest.php for State and City.
    var PLACE = /^[\p{L}\p{M}0-9\s.,'’\-()\/&]+$/u;

    function el(id) { return document.getElementById(id); }

    // State, City and ZIP each have a <select> (API list) and a hidden text twin (typing fallback).
    // Only the visible one is enabled, so only that one is submitted.
    function isText(id) { var t = el(id + '_text'); return !!t && !t.hidden; }
    function field(id) { return isText(id) ? el(id + '_text') : el(id); }
    function text(id) { return field(id).value.trim(); }

    function currentCountry() {
        var name = el('country').value;
        for (var i = 0; i < countries.length; i++) {
            if (countries[i].name === name) { return countries[i]; }
        }
        return null;
    }

    function checkName(label, v) {
        if (!v) { return label + ' is required.'; }
        if (v.length < 2 || v.length > 50) { return label + ' must be 2 to 50 characters.'; }
        if (!/^[A-Za-z\s'\-]+$/.test(v)) { return label + ' can only have letters, spaces, hyphens, and apostrophes.'; }
        return '';
    }

    // Same rules as RegisterRequest.php (the server is still the one that decides).
    var validators = {
        first_name: function () { return checkName('First name', text('first_name')); },
        last_name: function () { return checkName('Last name', text('last_name')); },

        middle_initial: function () {
            var v = text('middle_initial');
            if (v === '') { return ''; }
            return /^[A-Za-z]\.?$/.test(v) ? '' : 'Middle initial must be one letter with an optional period (e.g., A or A.).';
        },

        birthday: function () {
            var v = text('birthday');
            if (!v) { return 'Birthday is required.'; }
            var m = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(v);
            if (!m) { return 'Use the format MM/DD/YYYY.'; }
            var month = +m[1], day = +m[2], year = +m[3];
            var d = new Date(year, month - 1, day);
            if (year < 1900 || d.getFullYear() !== year || d.getMonth() !== month - 1 || d.getDate() !== day) {
                return 'That is not a valid date.';
            }
            var limit = new Date();
            limit.setHours(0, 0, 0, 0);
            limit.setFullYear(limit.getFullYear() - 13);
            if (d > limit) { return 'You must be at least 13 years old.'; }
            return '';
        },

        email: function () {
            var v = text('email').toLowerCase();
            if (!v) { return 'Email is required.'; }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) { return 'Enter a valid email address.'; }
            var domain = v.split('@').pop();
            if (allowedDomains.indexOf(domain) === -1) {
                return 'Only public email providers are allowed: ' + allowedDomains.join(', ') + '.';
            }
            return '';
        },

        country: function () {
            return currentCountry() ? '' : 'Please choose a country.';
        },

        mobile_number: function () {
            var v = text('mobile_number');
            var c = currentCountry();
            if (!v) { return 'Mobile number is required.'; }
            if (c && !new RegExp(c.phone).test(v)) { return c.phone_hint; }
            return '';
        },

        house_street: function () {
            var v = text('house_street');
            if (!v) { return 'House and street is required.'; }
            if (v.length > 255 || !/^[A-Za-z0-9\s.,#\/'&()\-]+$/.test(v)) { return 'House and street has characters that are not allowed.'; }
            return '';
        },

        state: function () {
            var v = text('state');
            if (!v) { return 'State is required.'; }
            if (v.length > 100 || !PLACE.test(v)) { return 'State has characters that are not allowed.'; }
            return '';
        },

        city: function () {
            var v = text('city');
            if (!v) { return 'City is required.'; }
            if (v.length > 100 || !PLACE.test(v)) { return 'City has characters that are not allowed.'; }
            return '';
        },

        zip_code: function () {
            var v = text('zip_code');
            var c = currentCountry();
            if (!v) { return 'ZIP code is required.'; }
            if (c && !new RegExp(c.zip).test(v)) { return c.zip_hint; }
            return '';
        },

        password: function () {
            var v = el('password').value;
            if (!v) { return 'Password is required.'; }
            if (v.length < 12) { return 'Password must be at least 12 characters.'; }
            if (!/[A-Z]/.test(v) || !/[a-z]/.test(v) || !/[0-9]/.test(v) || !/[^A-Za-z0-9]/.test(v)) {
                return 'Password must include an uppercase letter, a lowercase letter, a number, and a special character.';
            }
            return '';
        },

        password_confirmation: function () {
            var v = el('password_confirmation').value;
            if (!v) { return 'Please confirm your password.'; }
            if (v !== el('password').value) { return 'The passwords do not match.'; }
            return '';
        }
    };

    function showError(id, message) {
        var box = form.querySelector('[data-error-for="' + id + '"]');
        if (box) { box.textContent = message; }
        el(id).classList.remove('invalid');
        var twin = el(id + '_text');
        if (twin) { twin.classList.remove('invalid'); }
        if (message !== '') { field(id).classList.add('invalid'); }
    }

    function run(id) { showError(id, validators[id]()); }

    // ---------- Dependent dropdowns: Country > State > City > ZIP ----------

    function option(value, label) {
        var o = document.createElement('option');
        o.value = value;
        o.textContent = label;
        return o;
    }

    function setNote(id, message) {
        var n = form.querySelector('[data-note-for="' + id + '"]');
        if (!n) { return; }
        n.textContent = message || '';
        n.hidden = !message;
    }

    // Back to a disabled, unselected <select> with one placeholder option.
    // Also cancels any request still in flight for this field.
    function resetField(id, placeholder, disabled) {
        seq[id] += 1;
        var sel = el(id);
        var txt = el(id + '_text');
        sel.innerHTML = '';
        sel.appendChild(option('', placeholder));
        sel.value = '';
        sel.hidden = false;
        sel.disabled = disabled;
        txt.value = '';
        txt.hidden = true;
        txt.disabled = true;
        setNote(id, '');
        if (!restoring) {
            delete touched[id];
            showError(id, '');
        }
    }

    function fillSelect(id, items, placeholder) {
        var sel = el(id);
        sel.innerHTML = '';
        sel.appendChild(option('', placeholder));
        items.forEach(function (it) { sel.appendChild(option(it.value, it.label)); });
        sel.value = '';
        sel.disabled = false;
    }

    function hasOption(sel, value) {
        for (var i = 0; i < sel.options.length; i++) {
            if (sel.options[i].value === value) { return true; }
        }
        return false;
    }

    // The list could not be loaded: let the user type the value instead.
    function useText(id, value, note) {
        el(id).hidden = true;
        el(id).disabled = true;
        var txt = el(id + '_text');
        txt.hidden = false;
        txt.disabled = false;
        txt.value = value || '';
        setNote(id, note || '');
    }

    function getJSON(url, params) {
        var query = Object.keys(params).map(function (k) {
            return encodeURIComponent(k) + '=' + encodeURIComponent(params[k]);
        }).join('&');
        return fetch(url + '?' + query, { headers: { 'Accept': 'application/json' } }).then(function (r) {
            if (!r.ok) { throw new Error('Request failed'); }
            return r.json();
        });
    }

    // Resolves true (list loaded), false (switched to typing) or null (a newer request replaced this one).
    function load(id, url, params, toItems, oldValue, label) {
        resetField(id, 'Loading ' + label + '...', true);
        var n = ++seq[id];

        function fallback() {
            useText(id, oldValue, 'We could not load the ' + label + ' list, so please type it.');
            return false;
        }

        return getJSON(url, params).then(function (data) {
            if (n !== seq[id]) { return null; }
            var items = Array.isArray(data) ? toItems(data) : [];
            if (!items.length) { return fallback(); }
            fillSelect(id, items, 'Select ' + label);
            if (oldValue && hasOption(el(id), oldValue)) { el(id).value = oldValue; }
            return true;
        }).catch(function () {
            if (n !== seq[id]) { return null; }
            return fallback();
        });
    }

    function stateItems(list) {
        return list.map(function (s) { return { value: s.name, label: s.name }; });
    }

    function plainItems(list) {
        return list.map(function (v) { return { value: String(v), label: String(v) }; });
    }

    // o = values to put back after a server error ({} when the user just changed a dropdown).
    function startState(o) {
        resetField('city', 'Select a state / province first', true);
        resetField('zip_code', 'Select a city first', true);

        var c = currentCountry();
        if (!c) {
            resetField('state', 'Select a country first', true);
            return Promise.resolve();
        }

        return load('state', urls.states, { country: c.name }, stateItems, o.state, 'state / province').then(function (r) {
            if (r === false) {
                useText('city', o.city);
                useText('zip_code', o.zip_code);
            } else if (r && field('state').value) {
                return startCity(o);
            }
        });
    }

    function startCity(o) {
        resetField('zip_code', 'Select a city first', true);

        var c = currentCountry();
        var state = field('state').value;
        if (!c || !state) {
            resetField('city', 'Select a state / province first', true);
            return Promise.resolve();
        }

        return load('city', urls.cities, { country: c.name, state: state }, plainItems, o.city, 'city').then(function (r) {
            if (r === false) {
                useText('zip_code', o.zip_code);
            } else if (r && field('city').value) {
                return startZip(o);
            }
        });
    }

    function startZip(o) {
        var c = currentCountry();
        var city = field('city').value;
        if (!c || !city) {
            resetField('zip_code', 'Select a city first', true);
            return Promise.resolve();
        }

        if (!zipLookup[c.name]) {
            useText('zip_code', o.zip_code, 'ZIP codes are not listed for this country, so please type yours.');
            return Promise.resolve();
        }

        return load('zip_code', urls.zips, { country: c.name, state: field('state').value, city: city }, plainItems, o.zip_code, 'ZIP code');
    }

    function syncCountry() {
        var c = currentCountry();
        el('dial_code').textContent = c ? c.dial : '+';
        el('mobile_number').disabled = !c;
        el('mobile_number').placeholder = c ? '' : 'Select a country first';
    }

    // Birthday: keep only digits and add the slashes while typing.
    el('birthday').addEventListener('input', function () {
        var d = this.value.replace(/\D/g, '').slice(0, 8);
        var out = d.slice(0, 2);
        if (d.length > 2) { out += '/' + d.slice(2, 4); }
        if (d.length > 4) { out += '/' + d.slice(4); }
        this.value = out;
    });

    // Mobile: digits only.
    el('mobile_number').addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '');
    });

    // Live feedback (the typing twins get the same checks).
    Object.keys(validators).forEach(function (id) {
        [el(id), el(id + '_text')].forEach(function (input) {
            if (!input) { return; }
            input.addEventListener('blur', function () { touched[id] = true; run(id); });
            input.addEventListener('input', function () {
                if (touched[id]) { run(id); }
                if (id === 'password' && touched.password_confirmation) { run('password_confirmation'); }
            });
            input.addEventListener('change', function () { if (touched[id]) { run(id); } });
        });
    });

    // Country change: update the prefix and rebuild the dropdowns below it.
    el('country').addEventListener('change', function () {
        syncCountry();
        touched.country = true;
        run('country');
        if (touched.mobile_number) { run('mobile_number'); }
        startState({});
    });

    el('state').addEventListener('change', function () { startCity({}); });
    el('city').addEventListener('change', function () { startZip({}); });

    // Show / hide password.
    Array.prototype.forEach.call(form.querySelectorAll('[data-toggle-for]'), function (btn) {
        btn.addEventListener('click', function () {
            var input = el(btn.getAttribute('data-toggle-for'));
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.textContent = show ? 'Hide' : 'Show';
            btn.setAttribute('aria-pressed', show ? 'true' : 'false');
        });
    });

    // Errors that came from the server: mark the fields red so live checks keep working.
    Array.prototype.forEach.call(form.querySelectorAll('[data-error-for]'), function (box) {
        if (box.textContent.trim() !== '') {
            var id = box.getAttribute('data-error-for');
            touched[id] = true;
            if (el(id)) { el(id).classList.add('invalid'); }
        }
    });

    // Strong password suggestion.
    function randomInt(max) {
        var a = new Uint32Array(1);
        var limit = Math.floor(4294967296 / max) * max;
        do { crypto.getRandomValues(a); } while (a[0] >= limit);
        return a[0] % max;
    }

    function pick(chars) { return chars.charAt(randomInt(chars.length)); }

    function generatePassword(length) {
        var upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        var lower = 'abcdefghijkmnopqrstuvwxyz';
        var digits = '23456789';
        var special = '!@#$%^&*-_=+?';
        var all = upper + lower + digits + special;
        var chars = [pick(upper), pick(lower), pick(digits), pick(special)];
        while (chars.length < length) { chars.push(pick(all)); }
        for (var i = chars.length - 1; i > 0; i--) {
            var j = randomInt(i + 1);
            var tmp = chars[i]; chars[i] = chars[j]; chars[j] = tmp;
        }
        return chars.join('');
    }

    el('suggest-btn').addEventListener('click', function () {
        var pw = generatePassword(16);
        el('password').value = pw;
        el('password_confirmation').value = pw;
        el('suggest-value').textContent = pw;
        el('suggest-box').hidden = false;
        touched.password = true;
        touched.password_confirmation = true;
        run('password');
        run('password_confirmation');
    });

    // Submit: check everything first. The server checks again anyway.
    form.addEventListener('submit', function (e) {
        var firstBad = null;
        Object.keys(validators).forEach(function (id) {
            var msg = validators[id]();
            showError(id, msg);
            if (msg && !firstBad) { firstBad = field(id); }
        });
        if (firstBad) {
            e.preventDefault();
            firstBad.focus();
            return;
        }
        var btn = el('submit-btn');
        btn.disabled = true;
        btn.textContent = 'Creating account...';
    });

    // If the user comes back with the Back button, make the button usable again.
    window.addEventListener('pageshow', function () {
        var btn = el('submit-btn');
        btn.disabled = false;
        btn.textContent = 'Create account';
    });

    // First paint: with no country everything below it stays disabled.
    // After a server error the country is still set, so rebuild the lists and put the old picks back.
    syncCountry();
    if (currentCountry()) {
        startState({ state: old.state || '', city: old.city || '', zip_code: old.zip_code || '' })
            .then(function () { restoring = false; }, function () { restoring = false; });
    } else {
        restoring = false;
    }
})();