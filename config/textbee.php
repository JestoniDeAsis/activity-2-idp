<?php

// Textbee SMS gateway (your Android phone sends the SMS). Values come from .env.
return [
    'api_key' => env('TEXTBEE_API_KEY'),
    'device_id' => env('TEXTBEE_DEVICE_ID'),
    'base_url' => env('TEXTBEE_BASE_URL', 'https://api.textbee.dev/api/v1'),
];