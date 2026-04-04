<?php

return [
    'version' => '2.0.0',
    'enabled' => true,
    'login_url' => 'https://id.copyleaks.com/v3/account/login/api',
    'detect_url' => 'https://api.copyleaks.com/v2/writer-detector/{scanId}/check',
    'setting_keys' => [
        'api_key' => 'copyleaks_api_key',
        'email' => 'copyleaks_email',
        'enabled' => 'copyleaks_enabled',
        'debug_mode' => 'copyleaks_debug_mode',
    ],
];
