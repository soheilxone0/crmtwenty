<?php

namespace Crmtwenty\Platform\Http\Controllers;

use Crmtwenty\Platform\Support\Logins;
use Crmtwenty\Platform\Support\Tenancy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SessionController extends Controller
{
    public function __construct(protected Tenancy $tenancy) {}

    public function create(): View|RedirectResponse
    {
        if (! $this->tenancy->enabled()) {
            return redirect()->route('platform.setup.create');
        }

        if (auth()->guard('platform')->check()) {
            return redirect()->route('platform.dashboard');
        }

        return view('platform::auth.login');
    }

    public function store(): RedirectResponse
    {
        $this->validate(request(), [
            'login' => 'required|string|max:190',
            'password' => 'required|string',
        ]);

        $login = Logins::normalize(request('login'));

        $key = 'platform-login|'.hash('sha256', $login.'|'.request()->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'login' => trans('platform::app.auth.throttled', ['seconds' => RateLimiter::availableIn($key)]),
            ]);
        }

        $credentials = [
            (str_contains($login, '@') ? 'email' : 'username') => $login,
            'password' => (string) request('password'),
            'status' => 1,
        ];

        if (! auth()->guard('platform')->attempt($credentials, (bool) request('remember'))) {
            RateLimiter::hit($key, 300);

            throw ValidationException::withMessages([
                'login' => trans('platform::app.auth.failed'),
            ]);
        }

        RateLimiter::clear($key);

        request()->session()->regenerate();

        auth()->guard('platform')->user()->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('platform.dashboard'));
    }

    public function destroy(): RedirectResponse
    {
        auth()->guard('platform')->logout();

        request()->session()->regenerateToken();

        return redirect()->route('platform.session.create');
    }
}
