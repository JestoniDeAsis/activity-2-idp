<?php

/*
|--------------------------------------------------------------------------
| Special (Non-Working) Days
|--------------------------------------------------------------------------
| Pick where the special days come from with ONE line in .env:
|
|   HOLIDAYS_SPECIAL_MODE=nager          Nager.Date only (what the API gives)
|   HOLIDAYS_SPECIAL_MODE=manual         Google/proclamation list below (default)
|   HOLIDAYS_SPECIAL_MODE=calendarific   Calendarific API (needs CALENDARIFIC_KEY)
|
| Combine sources with "+" (the first one wins when two give the same date):
|   HOLIDAYS_SPECIAL_MODE=manual+nager          fixed list + Nager.Date
|   HOLIDAYS_SPECIAL_MODE=manual+calendarific   fixed list + Calendarific
|   HOLIDAYS_SPECIAL_MODE=manual+nager+calendarific   all three
|
| Regular and Islamic days are NOT affected by this setting.
|
| manual: for every year listed in 'years', that list REPLACES the special
| days from Nager. Years not listed still use Nager. To remove the manual
| list entirely, comment out everything between the BEGIN/END markers
| below (or delete this file's 'years' data); the mode then just falls back
| to Nager.
|
| calendarific: replaces Nager's special days with Calendarific's. If the
| request fails, it falls back to Nager for that request.
*/

return [

    'mode' => env('HOLIDAYS_SPECIAL_MODE', 'manual'),

    // Islamic days (Eid'l Fitr / Eid'l Adha): manual, calendarific, nager, or combined with "+" (e.g. manual+calendarific). See .env examples
    'islamic_mode' => env('HOLIDAYS_ISLAMIC_MODE', 'manual'),

    'calendarific' => [
        'key' => env('CALENDARIFIC_KEY'),
        // Calendarific's own holiday types to keep (it also lists observances, seasons, local days).
        // If special days are missing, check what type Calendarific gives them and add it here.
        'types' => ['National holiday'],
        // Cache per year so the free request quota isn't used up (seconds).
        'cache_ttl' => 60 * 60 * 24,
    ],

    'years' => [

        // ===== BEGIN GOOGLE-BASED SPECIAL DAYS =====

        2020 => [
            'source' => 'Proclamation No. 845',
            'days' => [
                '01-25' => 'Chinese New Year',
                '02-25' => 'EDSA People Power Revolution Anniversary',
                '04-11' => 'Black Saturday',
                '08-21' => 'Ninoy Aquino Day',
                '11-01' => "All Saints' Day",
                '11-02' => "All Souls' Day",
                '12-08' => 'Feast of the Immaculate Conception of Mary',
                '12-24' => 'Christmas Eve',
                '12-31' => 'Last Day of the Year',
            ],
        ],

        // All Souls' Day, Christmas Eve and Last Day of the Year became special WORKING days (Proc. 1107)
        2021 => [
            'source' => 'Proclamation No. 1107',
            'days' => [
                '02-12' => 'Chinese New Year',
                '02-25' => 'EDSA People Power Revolution Anniversary',
                '04-03' => 'Black Saturday',
                '08-21' => 'Ninoy Aquino Day',
                '11-01' => "All Saints' Day",
                '12-08' => 'Feast of the Immaculate Conception of Mary',
            ],
        ],

        2022 => [
            'source' => 'Proclamation No. 1236',
            'days' => [
                '02-01' => 'Chinese New Year',
                '02-25' => 'EDSA People Power Revolution Anniversary',
                '04-16' => 'Black Saturday',
                '08-21' => 'Ninoy Aquino Day',
                '11-01' => "All Saints' Day",
                '12-08' => 'Feast of the Immaculate Conception of Mary',
            ],
        ],

        2023 => [
            'source' => 'Proclamation Nos. 42 and 90',
            'days' => [
                '01-02' => 'Special Non-Working Day after New Year',
                '02-25' => 'EDSA People Power Revolution Anniversary',
                '04-08' => 'Black Saturday',
                '08-21' => 'Ninoy Aquino Day',
                '11-01' => "All Saints' Day",
                '11-02' => "All Souls' Day",
                '12-08' => 'Feast of the Immaculate Conception of Mary',
                '12-31' => 'Last Day of the Year',
            ],
        ],

        2024 => [
            'source' => 'Proclamation No. 368',
            'days' => [
                '02-10' => 'Chinese New Year',
                '03-30' => 'Black Saturday',
                '08-21' => 'Ninoy Aquino Day',
                '11-01' => "All Saints' Day",
                '11-02' => "All Souls' Day",
                '12-08' => 'Feast of the Immaculate Conception of Mary',
                '12-24' => 'Christmas Eve',
                '12-31' => 'Last Day of the Year',
            ],
        ],

        2025 => [
            'source' => 'Proclamation Nos. 727, 729 and 878',
            'days' => [
                '01-29' => 'Chinese New Year',
                '04-19' => 'Black Saturday',
                '05-12' => 'National and Local Elections',
                '07-27' => 'Iglesia ni Cristo Founding Anniversary',
                '08-21' => 'Ninoy Aquino Day',
                '10-31' => "All Saints' Day Eve",
                '11-01' => "All Saints' Day",
                '12-08' => 'Feast of the Immaculate Conception of Mary',
                '12-24' => 'Christmas Eve',
                '12-31' => 'Last Day of the Year',
            ],
        ],

        2026 => [
            'source' => 'Special non-working days (nationwide)',
            'days' => [
                '02-17' => 'Chinese New Year',
                '04-04' => 'Black Saturday',
                '08-21' => 'Ninoy Aquino Day',
                '11-01' => "All Saints' Day",
                '11-02' => "All Souls' Day",
                '12-08' => 'Feast of the Immaculate Conception of Mary',
                '12-24' => 'Christmas Eve',
                '12-31' => 'Last Day of the Year',
            ],
        ],

        // Feb 25 (EDSA) is a special WORKING day in 2027, so it is not listed
        2027 => [
            'source' => 'Proclamation No. 1427',
            'days' => [
                '02-06' => 'Chinese New Year',
                '03-27' => 'Black Saturday',
                '08-21' => 'Ninoy Aquino Day',
                '11-01' => "All Saints' Day",
                '11-02' => "All Souls' Day",
                '12-08' => 'Feast of the Immaculate Conception of Mary',
                '12-24' => 'Christmas Eve',
                '12-31' => 'Last Day of the Year',
            ],
        ],

        // ===== END GOOGLE-BASED SPECIAL DAYS =====

    ],
];