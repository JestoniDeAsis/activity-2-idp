(function () {
    'use strict';

    var root = document.getElementById('holidays-root');
    var panel = document.getElementById('panel-holidays');
    if (!root || !panel) { return; }

    var base = root.getAttribute('data-url');
    var minYear = parseInt(root.getAttribute('data-min-year'), 10);
    var maxYear = parseInt(root.getAttribute('data-max-year'), 10);

    var TYPES = [
        { key: 'regular', title: 'Regular Holidays', badge: 'Regular Holiday' },
        { key: 'special', title: 'Special Non-Working Days', badge: 'Special Non-Working Day' },
        { key: 'islamic', title: 'Islamic Holidays', badge: 'Islamic Holiday' }
    ];

    var cache = {};      // year -> server answer, kept only in this page's memory
    var token = 0;       // ignores answers that arrive after a newer request
    var started = false;
    var select, status, results;

    // Builds elements with textContent only, so nothing from the API is ever treated as HTML.
    function h(tag, className, content) {
        var node = document.createElement(tag);
        if (className) { node.className = className; }
        if (content !== undefined) { node.textContent = content; }
        return node;
    }

    function build() {
        root.innerHTML = '';

        var bar = h('div', 'holiday-toolbar');

        var label = h('label', '', 'Year');
        label.setAttribute('for', 'holiday-year');

        select = h('select');
        select.id = 'holiday-year';
        var current = new Date().getFullYear();
        var initial = Math.min(Math.max(current, minYear), maxYear);
        for (var y = minYear; y <= maxYear; y++) {
            var opt = h('option', '', String(y));
            opt.value = String(y);
            if (y === initial) { opt.selected = true; }
            select.appendChild(opt);
        }

        var legend = h('div', 'holiday-legend');
        TYPES.forEach(function (t) {
            legend.appendChild(h('span', 'badge badge-' + t.key, t.badge));
        });

        bar.appendChild(label);
        bar.appendChild(select);
        bar.appendChild(legend);

        status = h('p', 'hint holiday-status');
        status.setAttribute('role', 'status');
        results = h('div', 'holiday-results');

        root.appendChild(bar);
        root.appendChild(status);
        root.appendChild(results);

        select.addEventListener('change', function () {
            load(parseInt(select.value, 10));
        });
    }

    function showError(year, message) {
        results.innerHTML = '';
        status.textContent = message || ('Could not load the holidays for ' + year + '.');

        var retry = h('button', 'btn btn-secondary', 'Try again');
        retry.type = 'button';
        retry.addEventListener('click', function () { load(year); });
        results.appendChild(retry);
    }

    function dateParts(iso) {
        var p = iso.split('-');
        var d = new Date(parseInt(p[0], 10), parseInt(p[1], 10) - 1, parseInt(p[2], 10));
        return {
            month: d.toLocaleDateString('en-US', { month: 'short' }),
            day: String(d.getDate()),
            weekday: d.toLocaleDateString('en-US', { weekday: 'short' })
        };
    }

    function card(item, type) {
        var parts = dateParts(item.date);

        var box = h('article', 'holiday-card holiday-card-' + type.key);

        var date = h('div', 'holiday-date');
        date.appendChild(h('span', 'holiday-month', parts.month));
        date.appendChild(h('span', 'holiday-day', parts.day));
        date.appendChild(h('span', 'holiday-weekday', parts.weekday));

        var info = h('div', 'holiday-info');
        info.appendChild(h('div', 'holiday-name', item.name));
        if (item.local_name && item.local_name !== item.name) {
            info.appendChild(h('div', 'hint', item.local_name));
        }
        if (item.tentative) {
            info.appendChild(h('div', 'hint', 'Tentative date'));
        }
        info.appendChild(h('span', 'badge badge-' + type.key, type.badge));

        box.appendChild(date);
        box.appendChild(info);
        return box;
    }

    // Short note under the Islamic title: the moon-sighting warning and the Calendarific credit.
    function islamicNote() {
        var note = h('p', 'hint', 'Dates are proclaimed each year after the moon sighting and can shift by a day. Source: ');
        var link = h('a', '', 'Calendarific');
        link.href = 'https://calendarific.com';
        link.target = '_blank';
        link.rel = 'noopener noreferrer';
        note.appendChild(link);
        note.appendChild(document.createTextNode('.'));
        return note;
    }

    function render(year, data) {
        var holidays = data.holidays;

        results.innerHTML = '';
        status.textContent = holidays.length + ' official holidays in ' + year + '.';

        TYPES.forEach(function (type) {
            var items = holidays.filter(function (x) { return x.type === type.key; });

            var section = h('section', 'holiday-section');
            var title = h('h3', 'holiday-section-title', type.title);
            title.appendChild(h('span', 'holiday-count', String(items.length)));
            section.appendChild(title);

            if (!items.length) {
                var empty = (type.key === 'islamic' && data.islamic_error)
                    ? data.islamic_error
                    : 'None listed for ' + year + '.';
                section.appendChild(h('p', 'hint', empty));
            } else {
                if (type.key === 'islamic') { section.appendChild(islamicNote()); }
                var grid = h('div', 'holiday-grid');
                items.forEach(function (item) { grid.appendChild(card(item, type)); });
                section.appendChild(grid);
            }

            results.appendChild(section);
        });
    }

    function load(year) {
        var n = ++token;
        results.innerHTML = '';

        if (cache[year]) {
            render(year, cache[year]);
            return;
        }

        status.textContent = 'Loading holidays for ' + year + '...';

        fetch(base + '/' + year, { headers: { 'Accept': 'application/json' } })
            .then(function (r) {
                return r.json().then(
                    function (body) { return { ok: r.ok, body: body || {} }; },
                    function () { return { ok: false, body: {} }; }
                );
            })
            .then(function (res) {
                if (n !== token) { return; }
                if (!res.ok || !Array.isArray(res.body.holidays)) {
                    showError(year, res.body.message);
                    return;
                }
                // A year with an Islamic-data problem is not kept, so choosing it again retries.
                if (!res.body.islamic_error) { cache[year] = res.body; }
                render(year, res.body);
            })
            .catch(function () {
                if (n !== token) { return; }
                showError(year);
            });
    }

    // Load the first year when the Holidays tab is shown for the first time
    // (works for the tab click, the arrow keys, and the "Philippine Holidays" menu link).
    function maybeStart() {
        if (started || panel.hidden) { return; }
        started = true;
        build();
        load(parseInt(select.value, 10));
    }

    new MutationObserver(maybeStart).observe(panel, { attributes: true, attributeFilter: ['hidden'] });
    maybeStart();
})();