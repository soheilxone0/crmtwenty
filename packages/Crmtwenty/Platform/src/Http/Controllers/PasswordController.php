<?php

namespace Crmtwenty\Platform\Http\Controllers;

use Crmtwenty\Platform\Models\PlatformAdmin;
use Crmtwenty\Platform\Support\Logins;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

/**
 * Forgotten password of a platform administrator.
 */
class PasswordController extends Controller
{
    public function request(): View
    {
        return view('platform::auth.forgot-password');
    }

    public function email(): RedirectResponse
    {
        $this->validate(request(), ['email' => 'required|email']);

        try {
            Password::broker('platform_admins')->sendResetLink([
                'email' => Logins::normalize(request('email')),
            ]);
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->with('error', trans('platform::app.auth.mail-failed'));
        }

        /**
         * Same answer whether or not the address exists.
         */
        return back()->with('success', trans('platform::app.auth.reset-link-sent'));
    }

    public function reset(string $token): View
    {
        return view('platform::auth.reset-password', [
            'token' => $token,
            'email' => request('email'),
        ]);
    }

    public function update(): RedirectResponse
    {
        $this->validate(request(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = Password::broker('platform_admins')->reset(
            [
                'email' => Logins::normalize(request('email')),
                'password' => request('password'),
                'password_confirmation' => request('password_confirmation'),
                'token' => request('token'),
            ],
            function (PlatformAdmin $admin, string $password) {
                $admin->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($admin));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withInput(request()->only('email'))->withErrors(['email' => trans($status)]);
        }

        return redirect()->route('platform.session.create')
            ->with('success', trans('platform::app.auth.password-changed'));
    }
}
