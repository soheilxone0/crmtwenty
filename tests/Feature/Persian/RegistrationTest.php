<?php

use Crmtwenty\Persian\Mail\SmtpTestMail;
use Crmtwenty\Persian\Mail\VerifyEmailMail;
use Crmtwenty\Persian\Support\MailSettings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

/**
 * crmtwenty: public sign-up, e-mail verification and the company e-mail (SMTP) settings.
 */
uses(DatabaseTransactions::class);

function persianSetConfig(array $values): void
{
    foreach ($values as $code => $value) {
        DB::table('core_config')->updateOrInsert(
            ['code' => $code],
            ['value' => $value, 'created_at' => now(), 'updated_at' => now()]
        );
    }
}

function persianAdmin(): User
{
    $role = Role::create([
        'name' => 'Persian Probe '.bin2hex(random_bytes(4)),
        'description' => 'Created by the Persian edition tests.',
        'permission_type' => 'all',
    ]);

    return User::create([
        'name' => 'مدیر',
        'email' => 'persian-admin-'.bin2hex(random_bytes(4)).'@example.com',
        'password' => bcrypt('secret123'),
        'status' => 1,
        'role_id' => $role->id,
        'view_permission' => 'global',
    ]);
}

beforeEach(function () {
    URL::forceRootUrl('http://localhost');

    Cache::flush();

    Mail::fake();
});

it('hides the sign-up page until it is enabled', function () {
    persianSetConfig(['email.signup.settings.enabled' => '0']);

    test()->get(route('admin.register.create'))->assertNotFound();
});

it('registers a user, sends the activation e-mail and activates the account', function () {
    persianSetConfig([
        'email.signup.settings.enabled' => '1',
        'email.signup.settings.require_verification' => '1',
        'email.signup.settings.require_approval' => '0',
    ]);

    test()->get(route('admin.register.create'))->assertOk();

    test()->post(route('admin.register.store'), [
        'name' => 'سهیل',
        'email' => 'New.User@Example.com',
        'password' => 'secret1234',
        'password_confirmation' => 'secret1234',
    ])->assertRedirect(route('admin.session.create'));

    $user = User::query()->where('email', 'new.user@example.com')->firstOrFail();

    expect((int) $user->status)->toBe(0)
        ->and($user->email_verified_at)->toBeNull()
        ->and((bool) $user->self_registered)->toBeTrue()
        ->and($user->role)->not->toBeNull()
        ->and($user->role->permission_type)->toBe('custom');

    Mail::assertSent(VerifyEmailMail::class, fn ($mail) => $mail->hasTo('new.user@example.com'));

    /**
     * Signing in before activation is refused with a clear message.
     */
    test()->post(route('admin.session.store'), [
        'email' => 'new.user@example.com',
        'password' => 'secret1234',
    ])->assertRedirect(route('admin.session.create'))
        ->assertSessionHas('warning');

    expect(auth()->guard('user')->check())->toBeFalse();

    /**
     * A tampered link is rejected, the real one activates the account.
     */
    test()->get(URL::temporarySignedRoute('admin.verification.verify', now()->addHour(), [
        'id' => $user->id,
        'hash' => sha1('someone@else.com'),
    ]))->assertForbidden();

    test()->get(VerifyEmailMail::verificationUrl($user))
        ->assertRedirect(route('admin.session.create'));

    $user->refresh();

    expect((int) $user->status)->toBe(1)
        ->and($user->email_verified_at)->not->toBeNull();
});

it('rejects weak passwords and duplicate e-mails', function () {
    persianSetConfig(['email.signup.settings.enabled' => '1']);

    $admin = persianAdmin();

    test()->post(route('admin.register.store'), [
        'name' => 'x',
        'email' => $admin->email,
        'password' => 'short',
        'password_confirmation' => 'short',
    ])->assertSessionHasErrors(['email', 'password']);

    Mail::assertNothingSent();
});

it('silently drops bot submissions caught by the honeypot', function () {
    persianSetConfig(['email.signup.settings.enabled' => '1']);

    test()->post(route('admin.register.store'), [
        'name' => 'bot',
        'email' => 'bot@example.com',
        'password' => 'secret1234',
        'password_confirmation' => 'secret1234',
        'website' => 'http://spam.example',
    ])->assertRedirect(route('admin.session.create'));

    expect(User::query()->where('email', 'bot@example.com')->exists())->toBeFalse();
});

it('maps the company e-mail settings to the smtp mailer', function () {
    $config = MailSettings::toConfig([
        'host' => 'mail.arounidea.com',
        'port' => '465',
        'encryption' => 'auto',
        'username' => 'crm@arounidea.com',
        'password' => 'secret',
        'from_name' => 'CRM',
    ]);

    expect($config['mail.mailers.smtp']['host'])->toBe('mail.arounidea.com')
        ->and($config['mail.mailers.smtp']['scheme'])->toBe('smtps')
        ->and($config['mail.from.address'])->toBe('crm@arounidea.com');
});

it('sends a test e-mail from the configuration page', function () {
    test()->actingAs(persianAdmin(), 'user')
        ->postJson(route('admin.configuration.smtp.test'), [
            'to' => 'owner@example.com',
            'host' => 'smtp.example.com',
            'port' => 587,
            'encryption' => 'tls',
            'username' => 'crm@example.com',
            'password' => 'secret',
        ])->assertOk();

    Mail::assertSent(SmtpTestMail::class, fn ($mail) => $mail->hasTo('owner@example.com'));
});

it('shows the company e-mail section in the configuration', function () {
    test()->actingAs(persianAdmin(), 'user')
        ->get(route('admin.configuration.index', ['slug' => 'email', 'slug2' => 'smtp']))
        ->assertOk()
        ->assertSee('email[smtp][account][host]', false);
});

it('renders the panel in persian, right-to-left, with the sign-up link', function () {
    persianSetConfig([
        'general.general.locale_settings.locale' => 'fa',
        'email.signup.settings.enabled' => '1',
    ]);

    test()->get(route('admin.session.create'))
        ->assertOk()
        ->assertSee('dir="rtl"', false)
        ->assertSee('ثبت‌نام کنید')
        ->assertSee(route('admin.register.create'), false);

    test()->get(route('admin.register.create'))
        ->assertOk()
        ->assertSee('ساخت حساب کاربری');
});

it('shows the company e-mail page in persian', function () {
    persianSetConfig(['general.general.locale_settings.locale' => 'fa']);

    test()->actingAs(persianAdmin(), 'user')
        ->get(route('admin.configuration.index', ['slug' => 'email', 'slug2' => 'smtp']))
        ->assertOk()
        ->assertSee('ایمیل شرکت (SMTP)')
        ->assertSee('ارسال ایمیل آزمایشی');
});
