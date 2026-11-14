<?php

use Crmtwenty\Platform\Http\Controllers\AccountController;
use Crmtwenty\Platform\Http\Controllers\CompanyController;
use Crmtwenty\Platform\Http\Controllers\DashboardController;
use Crmtwenty\Platform\Http\Controllers\PasswordController;
use Crmtwenty\Platform\Http\Controllers\SessionController;
use Crmtwenty\Platform\Http\Controllers\SetupController;
use Crmtwenty\Platform\Http\Middleware\PlatformAuthenticate;
use Illuminate\Support\Facades\Route;

/**
 * Platform administration panel (manages companies).
 */
Route::middleware('web')->prefix(config('platform.path', 'platform'))->name('platform.')->group(function () {
    Route::get('setup', [SetupController::class, 'create'])->name('setup.create');
    Route::post('setup', [SetupController::class, 'store'])->middleware('throttle:10,1')->name('setup.store');

    Route::get('login', [SessionController::class, 'create'])->name('session.create');
    Route::post('login', [SessionController::class, 'store'])->middleware('throttle:20,1')->name('session.store');

    Route::get('forgot-password', [PasswordController::class, 'request'])->name('password.request');
    Route::post('forgot-password', [PasswordController::class, 'email'])->middleware('throttle:5,1')->name('password.email');
    Route::get('reset-password/{token}', [PasswordController::class, 'reset'])->name('password.reset');
    Route::post('reset-password', [PasswordController::class, 'update'])->middleware('throttle:5,1')->name('password.update');

    Route::middleware(PlatformAuthenticate::class)->group(function () {
        Route::get('', [DashboardController::class, 'index'])->name('dashboard');

        Route::post('logout', [SessionController::class, 'destroy'])->name('session.destroy');

        Route::get('companies', [CompanyController::class, 'index'])->name('companies.index');
        Route::get('companies/create', [CompanyController::class, 'create'])->name('companies.create');
        Route::post('companies', [CompanyController::class, 'store'])->name('companies.store');
        Route::get('companies/{id}', [CompanyController::class, 'edit'])->whereNumber('id')->name('companies.edit');
        Route::put('companies/{id}', [CompanyController::class, 'update'])->whereNumber('id')->name('companies.update');
        Route::put('companies/{id}/password', [CompanyController::class, 'password'])->whereNumber('id')->name('companies.password');
        Route::delete('companies/{id}', [CompanyController::class, 'destroy'])->whereNumber('id')->name('companies.destroy');

        Route::get('account', [AccountController::class, 'edit'])->name('account.edit');
        Route::put('account', [AccountController::class, 'update'])->name('account.update');
        Route::post('admins', [AccountController::class, 'storeAdmin'])->name('admins.store');
        Route::delete('admins/{id}', [AccountController::class, 'destroyAdmin'])->whereNumber('id')->name('admins.destroy');
    });
});
