<?php

use Crmtwenty\Persian\Http\Controllers\EmailVerificationController;
use Crmtwenty\Persian\Http\Controllers\RegistrationController;
use Crmtwenty\Persian\Http\Controllers\SmtpTestController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'admin_locale'])
    ->prefix(config('app.admin_path'))
    ->group(function () {
        /**
         * Public sign-up (only when enabled in Configuration → Email → Sign-up).
         */
        Route::controller(RegistrationController::class)->prefix('register')->group(function () {
            Route::get('', 'create')->name('admin.register.create');

            Route::middleware('throttle:5,1')->post('', 'store')->name('admin.register.store');
        });

        /**
         * E-mail verification.
         */
        Route::controller(EmailVerificationController::class)->prefix('verify-email')->group(function () {
            Route::get('resend', 'create')->name('admin.verification.create');

            Route::middleware('throttle:3,1')->post('resend', 'store')->name('admin.verification.store');

            Route::middleware(['signed', 'throttle:10,1'])
                ->get('{id}/{hash}', 'verify')
                ->whereNumber('id')
                ->name('admin.verification.verify');
        });

        /**
         * "Send test e-mail" on the company e-mail configuration page.
         */
        Route::middleware('user')
            ->post('mail-settings/test', [SmtpTestController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('admin.configuration.smtp.test');
    });
