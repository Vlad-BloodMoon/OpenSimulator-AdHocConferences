<?php
declare(strict_types=1);
return [
    'dsn' => 'mysql:host=127.0.0.1;port=3306;dbname=opensim_adhoc;charset=utf8mb4',
    'db_user' => 'root',
    'db_password' => 'TEST_ROOT_PASSWORD_ONLY',
    'existing_cred_key_file' => __DIR__ . '/../tests/test-voice-key.txt',
    'allowed_ips' => ['127.0.0.1'],
    'max_members' => 25,
    'ttl_seconds' => 21600,
];
