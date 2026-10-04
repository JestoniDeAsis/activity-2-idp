<?php

// Only used by LocationController for the State / City / ZIP dropdowns.
// Keys must match the country names in config/countries.php.
// zip_lookup = false: no ZIP list is offered for that country, so the user types it.
return [
    'countries' => [
        'Philippines' => ['iso' => 'PH', 'zip_lookup' => true],
        'United States' => ['iso' => 'US', 'zip_lookup' => true],
        'Canada' => ['iso' => 'CA', 'zip_lookup' => false],         // API has only the first 3 characters
        'United Kingdom' => ['iso' => 'GB', 'zip_lookup' => false], // API has only the outcode
        'Australia' => ['iso' => 'AU', 'zip_lookup' => true],
        'Singapore' => ['iso' => 'SG', 'zip_lookup' => false],
        'Japan' => ['iso' => 'JP', 'zip_lookup' => true],
        'South Korea' => ['iso' => 'KR', 'zip_lookup' => false],
        'India' => ['iso' => 'IN', 'zip_lookup' => true],
        'Malaysia' => ['iso' => 'MY', 'zip_lookup' => true],
        'Indonesia' => ['iso' => 'ID', 'zip_lookup' => false],
        'Germany' => ['iso' => 'DE', 'zip_lookup' => true],
        'France' => ['iso' => 'FR', 'zip_lookup' => true],
    ],
];