<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

it('diagnoses login throttling', function () {
    URL::forceRootUrl('http://localhost');
    Cache::flush();

    $email = 'diag-'.Str::random(6).'@example.invalid';
    $key = 'admin-login|'.hash('sha256', Str::lower($email).'|127.0.0.1');
    $log = [];

    for ($i = 1; $i <= 6; $i++) {
        $response = test()->post('/admin/login', ['email' => $email, 'password' => 'wrong']);
        $log[] = $i.':'.$response->getStatusCode().' loc='.$response->headers->get('Location')
            .' attempts='.RateLimiter::attempts($key)
            .' store='.config('cache.default').' limiter='.var_export(config('cache.limiter'), true)
            .' errors='.json_encode(session('errors')?->all()).' flash='.json_encode(session('error'))
            .' now='.now()->toIso8601String();
    }

    expect(implode(' | ', $log))->toBe('');
});
