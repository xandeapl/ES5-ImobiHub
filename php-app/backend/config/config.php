<?php

declare(strict_types=1);

$env = [];
$envPath = dirname(__DIR__, 2) . '/.env';

if (is_file($envPath) && is_readable($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $key = trim($parts[0]);
        $value = trim($parts[1]);

        if ($key === '') {
            continue;
        }

        if (strlen($value) >= 2) {
            $first = $value[0];
            $last = $value[strlen($value) - 1];
            if (($first === '"' && $last === '"') || ($first === '\'' && $last === '\'')) {
                $value = substr($value, 1, -1);
            }
        }

        $env[$key] = $value;
    }
}

$cfg = static function (string $key, ?string $default = null) use ($env): ?string {
    if (array_key_exists($key, $env)) {
        return $env[$key];
    }

    $v = getenv($key);
    return $v === false ? $default : $v;
};

return [
    'db_path'          => __DIR__ . '/../../data/imobihub.sqlite',
    'upload_dir'       => __DIR__ . '/../../public/uploads',
    'upload_web_prefix' => '/uploads',
    'app_name'         => 'ImobiHub',
    'app_url'          => $cfg('IMOBIHUB_APP_URL', 'http://localhost:8000'),
    'contact_whatsapp' => $cfg('IMOBIHUB_CONTACT_WHATSAPP', '5541999998888'),
    'mail'             => [
        'host'      => $cfg('IMOBIHUB_SMTP_HOST', ''),
        'port'      => (int) ($cfg('IMOBIHUB_SMTP_PORT', '587') ?? '587'),
        'secure'    => $cfg('IMOBIHUB_SMTP_SECURE', 'tls'),
        'username'  => $cfg('IMOBIHUB_SMTP_USER', ''),
        'password'  => $cfg('IMOBIHUB_SMTP_PASS', ''),
        'from'      => $cfg('IMOBIHUB_MAIL_FROM', 'no-reply@imobihub.local'),
        'from_name' => $cfg('IMOBIHUB_MAIL_FROM_NAME', 'ImobiHub'),
    ],
];
