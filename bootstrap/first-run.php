<?php

/**
 * crmtwenty: first run on shared hosting (Plesk "Git pull & deploy").
 *
 * A fresh checkout has no .env file (it is never stored in Git), and without
 * one Laravel has no encryption key, so even the web installer could not be
 * shown. On the very first request a .env is created from .env.example with a
 * freshly generated APP_KEY; the web installer at /install then fills in the
 * database and the rest. Nothing happens once .env exists.
 */
$basePath = dirname(__DIR__);

$envPath = $basePath.'/.env';

if (! file_exists($envPath) && file_exists($basePath.'/.env.example') && is_writable($basePath)) {
    $contents = (string) file_get_contents($basePath.'/.env.example');

    $key = 'base64:'.base64_encode(random_bytes(32));

    $contents = preg_match('/^APP_KEY=.*$/m', $contents)
        ? preg_replace('/^APP_KEY=.*$/m', 'APP_KEY='.$key, $contents)
        : "APP_KEY={$key}\n".$contents;

    if (isset($_SERVER['HTTP_HOST']) && preg_match('/^[A-Za-z0-9.\-]+(:\d+)?$/', $_SERVER['HTTP_HOST'])) {
        $scheme = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

        $contents = preg_replace('/^APP_URL=.*$/m', 'APP_URL='.$scheme.'://'.$_SERVER['HTTP_HOST'], $contents);
    }

    if (@file_put_contents($envPath, $contents, LOCK_EX) !== false) {
        @chmod($envPath, 0640);
    }
}
