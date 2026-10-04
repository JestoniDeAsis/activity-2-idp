(function () {
    'use strict';

    var navToggle = document.getElementById('nav-toggle');
    var navMenu = document.getElementById('nav-menu');
    var profile = document.getElementById('profile');
    var profileBtn = document.getElementById('profile-btn');
    var profileMenu = document.getElementById('profile-menu');
    var modal = document.getElementById('modal');
    var tabs = Array.prototype.slice.call(document.querySelectorAll('.tab'));
    var panels = Array.prototype.slice.call(document.querySelectorAll('.tab-panel'));
    var lastFocus = null;

    // ---------- Hamburger ----------
    navToggle.addEventListener('click', function () {
        var open = navMenu.classList.toggle('open');
        navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    // ---------- Profile dropdown ----------
    function setProfile(open) {
        profileMenu.hidden = !open;
        profileBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    profileBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        setProfile(profileMenu.hidden);
    });

    document.addEventListener('click', function (e) {
        if (!profile.contains(e.target)) { setProfile(false); }
    });

    // Dashboard, Profile and Settings have no pages yet: the links do nothing.
    Array.prototype.forEach.call(document.querySelectorAll('.nav-link:not([data-open-modal])'), function (a) {
        a.addEventListener('click', function (e) { e.preventDefault(); });
    });

    // ---------- Tabs ----------
    function showTab(name) {
        tabs.forEach(function (t) {
            var on = t.getAttribute('data-tab') === name;
            t.classList.toggle('active', on);
            t.setAttribute('aria-selected', on ? 'true' : 'false');
            t.tabIndex = on ? 0 : -1;
        });
        panels.forEach(function (p) {
            p.hidden = p.id !== 'panel-' + name;
        });
    }

    tabs.forEach(function (t, i) {
        t.addEventListener('click', function () { showTab(t.getAttribute('data-tab')); });
        t.addEventListener('keydown', function (e) {
            var next = null;
            if (e.key === 'ArrowRight') { next = tabs[(i + 1) % tabs.length]; }
            if (e.key === 'ArrowLeft') { next = tabs[(i - 1 + tabs.length) % tabs.length]; }
            if (next) {
                e.preventDefault();
                showTab(next.getAttribute('data-tab'));
                next.focus();
            }
        });
    });

    // ---------- Modal ----------
    function openModal(tab) {
        lastFocus = document.activeElement;
        showTab(tab || 'accounts');
        modal.hidden = false;
        document.body.classList.add('modal-open');
        navMenu.classList.remove('open');
        navToggle.setAttribute('aria-expanded', 'false');
        setProfile(false);
        modal.querySelector('.modal-close').focus();
    }

    function closeModal() {
        modal.hidden = true;
        document.body.classList.remove('modal-open');
        if (lastFocus && lastFocus.focus) { lastFocus.focus(); }
    }

    Array.prototype.forEach.call(document.querySelectorAll('[data-open-modal]'), function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            openModal(btn.getAttribute('data-open-modal'));
        });
    });

    Array.prototype.forEach.call(document.querySelectorAll('[data-close-modal]'), function (btn) {
        btn.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            if (!modal.hidden) { closeModal(); }
            setProfile(false);
            return;
        }

        // Keep Tab inside the open modal.
        if (e.key === 'Tab' && !modal.hidden) {
            var items = modal.querySelectorAll('button:not([disabled]), [href], select, input, [tabindex]:not([tabindex="-1"])');
            var visible = Array.prototype.filter.call(items, function (n) { return n.offsetParent !== null; });
            if (!visible.length) { return; }
            var first = visible[0];
            var last = visible[visible.length - 1];
            if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
        }
    });
})();