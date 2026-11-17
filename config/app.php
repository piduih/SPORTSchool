<?php
declare(strict_types=1);
return [
    'name' => 'SPORTSchool',
    'env' => getenv('APP_ENV') ?: 'production',
    'debug' => filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOL),
    'url' => rtrim(getenv('APP_URL') ?: '', '/'),
    'timezone' => getenv('APP_TIMEZONE') ?: 'Asia/Kuala_Lumpur',
    'key' => getenv('APP_KEY') ?: '',
];
