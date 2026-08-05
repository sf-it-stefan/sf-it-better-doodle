<?php

/**
 * Test bootstrap.
 *
 * docker-compose injects DB_* and APP_ENV into the container as real environment
 * variables, so they land in $_SERVER. Laravel's Env repository reads $_SERVER
 * before $_ENV, and PHPUnit's <env force="true"> only rewrites getenv()/$_ENV —
 * so without this the suite boots against the live development Postgres database
 * and RefreshDatabase wipes it.
 *
 * Overriding $_SERVER here, before the framework boots, is the only place that
 * reliably wins.
 */

require __DIR__ . '/../vendor/autoload.php';

$overrides = [
    'APP_ENV' => 'testing',
    'DB_CONNECTION' => 'sqlite',
    'DB_DATABASE' => ':memory:',
    'DB_HOST' => '',
    'DB_PORT' => '',
    'DB_USERNAME' => '',
    'DB_PASSWORD' => '',
    'DB_URL' => '',
];

foreach ($overrides as $key => $value) {
    $_SERVER[$key] = $value;
    $_ENV[$key] = $value;
    putenv("{$key}={$value}");
}
