<?php

return [
    /*
     * App UUID and API key
     */
    'uuid' => env('FLUENT_APP_ID'),
    'api_token' => env('FLUENT_API_TOKEN'),
    'api_url' => env('FLUENT_API_URL', 'https://api.fluent.li'),

    /*
     * Extra languages
     * Use if your locales don't match our locales (eg. we call Spanish 'es' and you call it 'es_AR')
     */
    'languages' => [
    ]
];