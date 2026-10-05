<?php

// Philippine holidays. Nager.Date only marks them "Public", so the type is worked out
// from the holiday name. Keywords are lowercase, with apostrophes removed.
// Order of checks: Islamic first, then Regular. Anything else is a Special Non-Working Day.
// If a holiday lands in the wrong group, add or remove a keyword here.
return [
    'country' => 'PH',
    'min_year' => 2020,
    'max_year' => 2027,

    // Nager.Date has no Islamic holidays for the Philippines, so Eid'l Fitr and Eid'l Adha
    // come from Calendarific (free key in .env: CALENDARIFIC_API_KEY).
    'calendarific' => [
        'key' => env('CALENDARIFIC_API_KEY'),
        'url' => 'https://calendarific.com/api/v2/holidays',
    ],

    'islamic' => [
        'eid',
        'fitr',
        'adha',
        'ramadan',
        'islamic',
        'mawlid',
        'hijri',
        'muharram',
    ],

    'regular' => [
        'new years day',
        'maundy thursday',
        'good friday',
        'araw ng kagitingan',
        'day of valor',
        'labour day',
        'labor day',
        'araw ng paggawa',
        'independence day',
        'araw ng kasarinlan',
        'national heroes',
        'araw ng mga bayani',
        'bonifacio',
        'christmas day',
        'araw ng pasko',
        'rizal day',
    ],
];