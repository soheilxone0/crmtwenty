<?php

namespace Crmtwenty\Persian\Support;

use Closure;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * «ایمیل شرکت» — the company SMTP account configured in
 * Configuration → Email → Company e-mail (SMTP).
 *
 * When a host is saved there, it replaces the MAIL_* values from .env for
 * every e-mail the CRM sends (password reset, verification, notifications,
 * quotes, …). Leaving the host empty falls back to .env.
 */
class MailSettings
{
    /**
     * Config code prefix in the `core_config` table.
     */
    public const PREFIX = 'email.smtp.account.';

    /**
     * Fields stored for the account.
     */
    public const FIELDS = [
        'host',
        'port',
        'encryption',
        'username',
        'password',
        'verify_peer',
        'from_address',
        'from_name',
    ];

    /**
     * Per-request cache of the saved values.
     */
    protected static ?array $values = null;

    /**
     * Optional source of settings used when none are saved for the current workspace
     * (the multi-company platform points it at the main installation's account).
     */
    public static ?Closure $fallback = null;

    /**
     * The mail configuration from .env, restored when no account applies.
     */
    protected static ?array $original = null;

    /**
     * The values saved in the admin panel (empty array when none / no database yet).
     */
    public static function saved(): array
    {
        if (static::$values !== null) {
            return static::$values;
        }

        try {
            $rows = DB::table('core_config')
                ->where('code', 'like', static::PREFIX.'%')
                ->pluck('value', 'code')
                ->all();
        } catch (Throwable) {
            // Installer / database not migrated yet.
            return [];
        }

        $values = [];

        foreach (static::FIELDS as $field) {
            $values[$field] = $rows[static::PREFIX.$field] ?? null;
        }

        if (blank($values['host']) && static::$fallback) {
            try {
                $values = (static::$fallback)() ?: $values;
            } catch (Throwable) {
                // Keep the empty values.
            }
        }

        return static::$values = $values;
    }

    /**
     * Forget the cached values (after saving the configuration).
     */
    public static function flush(): void
    {
        static::$values = null;
    }

    /**
     * Whether a company SMTP account has been saved in the panel.
     */
    public static function isConfiguredInPanel(): bool
    {
        return filled(static::saved()['host'] ?? null);
    }

    /**
     * Whether the application can send e-mail at all (panel or .env).
     */
    public static function canSend(): bool
    {
        if (static::isConfiguredInPanel()) {
            return true;
        }

        $mailer = config('mail.default');

        if (in_array($mailer, ['log', 'array', null], true)) {
            return false;
        }

        if ($mailer === 'smtp') {
            return filled(config('mail.mailers.smtp.host'))
                && ! in_array(config('mail.mailers.smtp.host'), ['mailhog', 'smtp.mailgun.org'], true);
        }

        return true;
    }

    /**
     * Apply saved (or given) settings to the runtime mail configuration.
     */
    public static function apply(?array $settings = null): void
    {
        static::$original ??= [
            'mail.default' => config('mail.default'),
            'mail.mailers.smtp' => config('mail.mailers.smtp'),
            'mail.from.address' => config('mail.from.address'),
            'mail.from.name' => config('mail.from.name'),
        ];

        $settings ??= static::saved();

        if (blank($settings['host'] ?? null)) {
            config(static::$original);

            return;
        }

        config(array_merge(static::$original, static::toConfig($settings)));
    }

    /**
     * Translate panel values into Laravel `mail.*` config entries.
     */
    public static function toConfig(array $settings): array
    {
        $port = (int) ($settings['port'] ?? 0) ?: 587;

        $encryption = strtolower((string) ($settings['encryption'] ?? 'auto'));

        $smtp = [
            'transport' => 'smtp',
            'host' => trim((string) $settings['host']),
            'port' => $port,
            'username' => filled($settings['username'] ?? null) ? trim((string) $settings['username']) : null,
            'password' => filled($settings['password'] ?? null) ? (string) $settings['password'] : null,
            'timeout' => 20,
            'verify_peer' => (bool) ($settings['verify_peer'] ?? true),
            'local_domain' => parse_url((string) config('app.url'), PHP_URL_HOST) ?: null,
        ];

        switch ($encryption) {
            case 'ssl':
                // Implicit TLS, usually port 465.
                $smtp['scheme'] = 'smtps';

                break;

            case 'tls':
                // STARTTLS is required, usually port 587.
                $smtp['scheme'] = 'smtp';
                $smtp['require_tls'] = true;

                break;

            case 'none':
                $smtp['scheme'] = 'smtp';
                $smtp['auto_tls'] = false;

                break;

            default:
                // Auto: implicit TLS on 465, opportunistic STARTTLS otherwise.
                $smtp['scheme'] = $port === 465 ? 'smtps' : 'smtp';
        }

        $fromAddress = $settings['from_address'] ?? null;

        if (blank($fromAddress) && filter_var($smtp['username'], FILTER_VALIDATE_EMAIL)) {
            $fromAddress = $smtp['username'];
        }

        $config = [
            'mail.default' => 'smtp',
            'mail.mailers.smtp' => $smtp,
        ];

        if (filled($fromAddress)) {
            $config['mail.from.address'] = trim((string) $fromAddress);
        }

        if (filled($settings['from_name'] ?? null)) {
            $config['mail.from.name'] = trim((string) $settings['from_name']);
        }

        return $config;
    }
}
