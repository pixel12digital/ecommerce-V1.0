<?php

return [
    'mode' => $_ENV['APP_MODE'] ?? 'multi',
    'default_tenant_id' => (int)($_ENV['DEFAULT_TENANT_ID'] ?? 1),
    'url' => $_ENV['APP_URL'] ?? 'http://localhost',
    'env' => $_ENV['APP_ENV'] ?? 'development',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'session_name' => $_ENV['SESSION_NAME'] ?? 'ECOMMERCE_SESSION',
];



