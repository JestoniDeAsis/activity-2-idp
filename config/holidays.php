<?php

// Philippine holidays. Nager.Date (external API) supplies the Regular and Special days; it only
// marks them "Public", so the type is worked out from the holiday name. Keywords are lowercase,
// with apostrophes removed. Order of checks: Islamic, then Regular. Anything else is a Special Non-Working Day.
return [
    'country' => 'PH',
    'min_year' => 2020,
    'max_year' => 2027,

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

    // Eid'l Fitr and Eid'l Adha: the dates that were officially proclaimed (Republic Acts 9177 and 9849
    // make them regular holidays, with a movable date set each year by presidential proclamation,
    // on the recommendation of the National Commission on Muslim Filipinos).
    // These replace any Islamic entries from the API, because APIs only guess the date.
    // 'tentative' => true: the proclamation for that year has not been issued yet.
    // When it is, change the date, set 'tentative' => false and put the proclamation in 'source'.
    'islamic_dates' => [
        2020 => [
            ['date' => '2020-05-25', 'name' => "Eid'l Fitr", 'local_name' => 'Feast of Ramadhan', 'source' => 'Proclamation No. 944, s. 2020'],
            ['date' => '2020-07-31', 'name' => "Eid'l Adha", 'local_name' => 'Feast of Sacrifice', 'source' => 'Presidential declaration, July 30, 2020'],
        ],
        2021 => [
            ['date' => '2021-05-13', 'name' => "Eid'l Fitr", 'local_name' => 'Feast of Ramadhan', 'source' => 'Proclamation No. 1142, s. 2021'],
            ['date' => '2021-07-20', 'name' => "Eid'l Adha", 'local_name' => 'Feast of Sacrifice', 'source' => 'Proclamation No. 1189, s. 2021'],
        ],
        2022 => [
            ['date' => '2022-05-03', 'name' => "Eid'l Fitr", 'local_name' => 'Feast of Ramadhan', 'source' => 'Proclamation No. 1356, s. 2022'],
            ['date' => '2022-07-09', 'name' => "Eid'l Adha", 'local_name' => 'Feast of Sacrifice', 'source' => 'Proclamation No. 2, s. 2022'],
        ],
        2023 => [
            ['date' => '2023-04-21', 'name' => "Eid'l Fitr", 'local_name' => 'Feast of Ramadhan', 'source' => 'Proclamation No. 201, s. 2023'],
            ['date' => '2023-06-28', 'name' => "Eid'l Adha", 'local_name' => 'Feast of Sacrifice', 'source' => 'Proclamation No. 258, s. 2023'],
        ],
        2024 => [
            ['date' => '2024-04-10', 'name' => "Eid'l Fitr", 'local_name' => 'Feast of Ramadhan', 'source' => 'Proclamation No. 514, s. 2024'],
            ['date' => '2024-06-17', 'name' => "Eid'l Adha", 'local_name' => 'Feast of Sacrifice', 'source' => 'Proclamation No. 579, s. 2024'],
        ],
        2025 => [
            ['date' => '2025-04-01', 'name' => "Eid'l Fitr", 'local_name' => 'Feast of Ramadhan', 'source' => 'Proclamation No. 839, s. 2025'],
            ['date' => '2025-06-06', 'name' => "Eid'l Adha", 'local_name' => 'Feast of Sacrifice', 'source' => 'Proclamation No. 911, s. 2025'],
        ],
        2026 => [
            ['date' => '2026-03-20', 'name' => "Eid'l Fitr", 'local_name' => 'Feast of Ramadhan', 'source' => 'Presidential proclamation, March 2026'],
            ['date' => '2026-05-27', 'name' => "Eid'l Adha", 'local_name' => 'Feast of Sacrifice', 'source' => 'Proclamation No. 1264, s. 2026'],
        ],
        2027 => [
            ['date' => '2027-03-10', 'name' => "Eid'l Fitr", 'local_name' => 'Feast of Ramadhan', 'tentative' => true],
            ['date' => '2027-05-17', 'name' => "Eid'l Adha", 'local_name' => 'Feast of Sacrifice', 'tentative' => true],
        ],
    ],
];