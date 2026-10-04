(function () {
    'use strict';

    var form = document.getElementById('login-form');
    if (!form) { return; }

    var touched = {};

    function el(id) { return document.getElementById(id); }

    // Client side only checks the format of the email and that the password is not empty.
    // No password length check on purpose (the PDF asks for this to avoid timing hints).
    var validators = {
        email: function () {
            var v = el('email').value.trim();
            if (!v) { return 'Email is required.'; }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) { return 'Enter a valid email address.'; }
            return '';
        },
        password: function () {
            return el('password').value === '' ? 'Password is required.' : '';
        }
    };

    function showError(id, message) {
        var box = form.querySelector('[data-error-for="' + id + '"]');
        if (box) { box.textContent = message; }
        el(id).classList.toggle('invalid', message !== '');
    }

    function run(id) { showError(id, validators[id]()); }

    Object.keys(validators).forEach(function (id) {
        var input = el(id);
        input.addEventListener('blur', function () { touched[id] = true; run(id); });
        input.addEventListener('input', function () { if (touched[id]) { run(id); } });
    });

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

    form.addEventListener('submit', function (e) {
        var firstBad = null;
        Object.keys(validators).forEach(function (id) {
            var msg = validators[id]();
            showError(id, msg);
            if (msg && !firstBad) { firstBad = el(id); }
        });
        if (firstBad) {
            e.preventDefault();
            firstBad.focus();
            return;
        }
        var btn = el('submit-btn');
        btn.disabled = true;
        btn.textContent = 'Logging in...';
    });

    // If the user comes back with the Back button, make the button usable again.
    window.addEventListener('pageshow', function () {
        var btn = el('submit-btn');
        btn.disabled = false;
        btn.textContent = 'Log in';
    });
})();