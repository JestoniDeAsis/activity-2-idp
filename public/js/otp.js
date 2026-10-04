(function () {
    'use strict';

    var form = document.getElementById('otp-form');
    if (!form) { return; }

    var code = document.getElementById('code');
    var error = form.querySelector('[data-error-for="code"]');
    var submit = document.getElementById('submit-btn');
    var resend = document.getElementById('resend-btn');

    // Digits only, max 6.
    code.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 6);
        code.classList.remove('invalid');
    });

    // Check the format first. The server checks the code itself.
    form.addEventListener('submit', function (e) {
        if (!/^\d{6}$/.test(code.value)) {
            e.preventDefault();
            error.textContent = 'Enter the 6-digit code.';
            code.classList.add('invalid');
            code.focus();
            return;
        }
        submit.disabled = true;
        submit.textContent = 'Verifying...';
    });

    // If the user comes back with the Back button, make the button usable again.
    window.addEventListener('pageshow', function () {
        submit.disabled = false;
        submit.textContent = 'Verify';
    });

    // "Resend OTP" becomes available after 60 seconds (the server enforces it too).
    var left = parseInt(resend.getAttribute('data-seconds'), 10) || 0;

    function tick() {
        if (left > 0) {
            resend.disabled = true;
            resend.textContent = 'Resend OTP in ' + left + 's';
            left -= 1;
            setTimeout(tick, 1000);
        } else {
            resend.disabled = false;
            resend.textContent = 'Resend OTP';
        }
    }

    tick();
})();