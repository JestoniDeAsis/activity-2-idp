<?php

// Only used by LocationController for the State / City / ZIP dropdowns.
// Keys must match the country names in config/countries.php.
// zip_lookup = false: no ZIP list is offered for that country, so the user types it.
// The ZIP format check itself comes from config/countries.php, not from this file.
// If CountriesNow does not know a country name or has no states for it, the dropdown comes back
// empty and register.js lets the user type the value instead.
return [
    'countries' => [
        'Argentina' => ['iso' => 'AR', 'zip_lookup' => false],
        'Australia' => ['iso' => 'AU', 'zip_lookup' => true],
        'Austria' => ['iso' => 'AT', 'zip_lookup' => false],
        'Bangladesh' => ['iso' => 'BD', 'zip_lookup' => false],
        'Belgium' => ['iso' => 'BE', 'zip_lookup' => false],
        'Brazil' => ['iso' => 'BR', 'zip_lookup' => false],
        'Canada' => ['iso' => 'CA', 'zip_lookup' => false],         // API has only the first 3 characters
        'Chile' => ['iso' => 'CL', 'zip_lookup' => false],
        'China' => ['iso' => 'CN', 'zip_lookup' => false],
        'Colombia' => ['iso' => 'CO', 'zip_lookup' => false],
        'Denmark' => ['iso' => 'DK', 'zip_lookup' => false],
        'Egypt' => ['iso' => 'EG', 'zip_lookup' => false],
        'France' => ['iso' => 'FR', 'zip_lookup' => true],
        'Germany' => ['iso' => 'DE', 'zip_lookup' => true],
        'Greece' => ['iso' => 'GR', 'zip_lookup' => false],
        'India' => ['iso' => 'IN', 'zip_lookup' => true],
        'Indonesia' => ['iso' => 'ID', 'zip_lookup' => false],
        'Ireland' => ['iso' => 'IE', 'zip_lookup' => false],
        'Israel' => ['iso' => 'IL', 'zip_lookup' => false],
        'Italy' => ['iso' => 'IT', 'zip_lookup' => false],
        'Japan' => ['iso' => 'JP', 'zip_lookup' => true],
        'Kenya' => ['iso' => 'KE', 'zip_lookup' => false],
        'Malaysia' => ['iso' => 'MY', 'zip_lookup' => true],
        'Mexico' => ['iso' => 'MX', 'zip_lookup' => false],
        'Netherlands' => ['iso' => 'NL', 'zip_lookup' => false],
        'New Zealand' => ['iso' => 'NZ', 'zip_lookup' => false],
        'Nigeria' => ['iso' => 'NG', 'zip_lookup' => false],
        'Norway' => ['iso' => 'NO', 'zip_lookup' => false],
        'Pakistan' => ['iso' => 'PK', 'zip_lookup' => false],
        'Philippines' => ['iso' => 'PH', 'zip_lookup' => false],    // province and city come from PSGC, ZIP is typed
        'Poland' => ['iso' => 'PL', 'zip_lookup' => false],
        'Portugal' => ['iso' => 'PT', 'zip_lookup' => false],
        'Saudi Arabia' => ['iso' => 'SA', 'zip_lookup' => false],
        'South Africa' => ['iso' => 'ZA', 'zip_lookup' => false],
        'South Korea' => ['iso' => 'KR', 'zip_lookup' => false],
        'Spain' => ['iso' => 'ES', 'zip_lookup' => false],
        'Sri Lanka' => ['iso' => 'LK', 'zip_lookup' => false],
        'Sweden' => ['iso' => 'SE', 'zip_lookup' => false],
        'Switzerland' => ['iso' => 'CH', 'zip_lookup' => false],
        'Thailand' => ['iso' => 'TH', 'zip_lookup' => false],
        'Turkey' => ['iso' => 'TR', 'zip_lookup' => false],
        'United Kingdom' => ['iso' => 'GB', 'zip_lookup' => false], // API has only the outcode
        'United States' => ['iso' => 'US', 'zip_lookup' => true],
        'Vietnam' => ['iso' => 'VN', 'zip_lookup' => false],
    ],
];