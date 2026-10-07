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

    var LABELS = { regular: 'Regular Holiday', special: 'Special Non-Working Day', islamic: 'Islamic Holiday' };
    var MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    var WEEKDAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

    var cache = {};      // year -> server answer, kept only in this page's memory
    var token = 0;       // ignores answers that arrive after a newer request
    var started = false;
    var select, status, results;

    var current = null;  // what is on screen now: { year, holidays }
    var calMonth = 0;    // month shown in the calendar, 0 to 11
    var calBox = null;

    // Builds elements with textContent only, so nothing from the API is ever treated as HTML.
    function h(tag, className, content) {
        var node = document.createElement(tag);
        if (className) { node.className = className; }
        if (content !== undefined) { node.textContent = content; }
        return node;
    }

    function pad(n) { return (n < 10 ? '0' : '') + n; }

    function build() {
        root.innerHTML = '';

        var bar = h('div', 'holiday-toolbar');

        var label = h('label', '', 'Year');
        label.setAttribute('for', 'holiday-year');

        select = h('select');
        select.id = 'holiday-year';
        var currentYear = new Date().getFullYear();
        var initial = Math.min(Math.max(currentYear, minYear), maxYear);
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

    // ---------- Month calendar ----------

    function drawCalendar() {
        var year = current.year;
        var monthKey = year + '-' + pad(calMonth + 1) + '-';

        var byDate = {};
        current.holidays.forEach(function (x) {
            if (!byDate[x.date]) { byDate[x.date] = []; }
            byDate[x.date].push(x);
        });

        calBox.innerHTML = '';

        // Header: previous / month name / next (inside the selected year only).
        var head = h('div', 'cal-head');

        var prev = h('button', 'cal-nav', '\u2039');
        prev.type = 'button';
        prev.setAttribute('aria-label', 'Previous month');
        prev.disabled = calMonth === 0;
        prev.addEventListener('click', function () { calMonth -= 1; drawCalendar(); });

        var next = h('button', 'cal-nav', '\u203A');
        next.type = 'button';
        next.setAttribute('aria-label', 'Next month');
        next.disabled = calMonth === 11;
        next.addEventListener('click', function () { calMonth += 1; drawCalendar(); });

        head.appendChild(prev);
        head.appendChild(h('div', 'cal-title', MONTHS[calMonth] + ' ' + year));
        head.appendChild(next);

        // Details under the grid: the month's holidays, or the day that was clicked.
        var detail = h('div', 'cal-detail');
        detail.setAttribute('aria-live', 'polite');

        function showList(list, emptyText) {
            detail.innerHTML = '';
            if (!list.length) {
                detail.appendChild(h('div', 'hint', emptyText));
                return;
            }
            list.forEach(function (x) {
                var p = dateParts(x.date);
                var line = h('div', 'cal-line');
                line.appendChild(h('span', 'badge badge-' + x.type, LABELS[x.type]));
                line.appendChild(document.createTextNode(
                    ' ' + p.month + ' ' + p.day + ' (' + p.weekday + ') ' + x.name + (x.tentative ? ' (tentative date)' : '')
                ));
                detail.appendChild(line);
            });
        }

        function bind(cell, list) {
            cell.addEventListener('click', function () { showList(list, ''); });
        }

        // Grid: weekday names, blank cells before the 1st, then the days.
        var grid = h('div', 'cal-grid');
        WEEKDAYS.forEach(function (w) { grid.appendChild(h('div', 'cal-dow', w)); });

        var first = new Date(year, calMonth, 1).getDay();
        var days = new Date(year, calMonth + 1, 0).getDate();
        var now = new Date();
        var todayKey = now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-' + pad(now.getDate());

        for (var i = 0; i < first; i++) {
            grid.appendChild(h('div', 'cal-cell cal-empty'));
        }

        for (var d = 1; d <= days; d++) {
            var key = monthKey + pad(d);
            var list = byDate[key];
            var cell;

            if (list) {
                var names = list.map(function (x) { return x.name; }).join(', ');
                cell = h('button', 'cal-cell cal-holiday cal-' + list[0].type, String(d));
                cell.type = 'button';
                cell.title = names;
                cell.setAttribute('aria-label', MONTHS[calMonth] + ' ' + d + ': ' + names);
                bind(cell, list);
            } else {
                cell = h('div', 'cal-cell', String(d));
            }

            if (key === todayKey) { cell.classList.add('cal-today'); }
            grid.appendChild(cell);
        }

        calBox.appendChild(head);
        calBox.appendChild(grid);
        calBox.appendChild(detail);

        var monthList = current.holidays.filter(function (x) { return x.date.indexOf(monthKey) === 0; });
        showList(monthList, 'No holidays in ' + MONTHS[calMonth] + '.');
    }

    // ---------- Cards ----------

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
            info.appendChild(h('div', 'hint', 'Tentative date (not proclaimed yet)'));
        } else if (item.source) {
            info.appendChild(h('div', 'hint', item.source));
        }
        info.appendChild(h('span', 'badge badge-' + type.key, type.badge));

        box.appendChild(date);
        box.appendChild(info);
        return box;
    }

    // Short note under the Islamic title: the moon-sighting warning and the Calendarific credit.
    function islamicNote() {
        return h('p', 'hint', 'Dates follow the presidential proclamations, based on the National Commission on Muslim Filipinos (NCMF) recommendation. Dates marked tentative are not proclaimed yet and can shift by a day.');
    }

    function render(year, data) {
        var holidays = data.holidays;

        // A new year starts at January (or the current month for the current year).
        // Re-showing the same year keeps the month the user was looking at.
        if (!current || current.year !== year) {
            var now = new Date();
            calMonth = (year === now.getFullYear()) ? now.getMonth() : 0;
        }
        current = { year: year, holidays: holidays };

        results.innerHTML = '';
        status.textContent = holidays.length + ' official holidays in ' + year + '.';

        calBox = h('section', 'calendar');
        calBox.setAttribute('aria-label', 'Holiday calendar');
        results.appendChild(calBox);
        drawCalendar();

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