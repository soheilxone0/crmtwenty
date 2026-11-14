<?php

namespace Crmtwenty\Persian\Support;

use Throwable;
use Webkul\User\Models\Role;

/**
 * Settings of Configuration → Email → Sign-up & e-mail verification.
 */
class Registration
{
    public const PREFIX = 'email.signup.settings.';

    /**
     * Read one setting, falling back to the field default.
     */
    protected static function get(string $field, mixed $default = null): mixed
    {
        try {
            $value = core()->getConfigData(static::PREFIX.$field);
        } catch (Throwable) {
            return $default;
        }

        return $value ?? $default;
    }

    /**
     * Visitors may create an account from the login page.
     */
    public static function isEnabled(): bool
    {
        return (bool) static::get('enabled', false);
    }

    /**
     * Self-registered users must confirm their e-mail address before signing in.
     */
    public static function requiresVerification(): bool
    {
        return (bool) static::get('require_verification', true);
    }

    /**
     * An administrator must activate self-registered users.
     */
    public static function requiresApproval(): bool
    {
        return (bool) static::get('require_approval', false);
    }

    /**
     * Name of the limited role created for self-registered users.
     */
    public const SIGNUP_ROLE_NAME = 'کارشناس فروش';

    /**
     * Role given to self-registered users.
     *
     * Without an explicit choice the administrator role is never handed out:
     * a limited "sales" role is created on first use instead.
     */
    public static function defaultRoleId(): int
    {
        $roleId = static::get('default_role');

        if ($roleId && Role::query()->whereKey($roleId)->exists()) {
            return (int) $roleId;
        }

        $role = Role::query()->where('permission_type', 'custom')->orderBy('id')->first()
            ?? Role::query()->create([
                'name' => static::SIGNUP_ROLE_NAME,
                'description' => trans('persian::app.registration.role-description'),
                'permission_type' => 'custom',
                'permissions' => static::signupPermissions(),
            ]);

        return (int) $role->id;
    }

    /**
     * Day-to-day sales permissions, without settings or deletions.
     */
    public static function signupPermissions(): array
    {
        return [
            'dashboard',
            'leads', 'leads.create', 'leads.create.quick-create', 'leads.view', 'leads.edit',
            'quotes', 'quotes.create', 'quotes.create.quick-create', 'quotes.edit', 'quotes.print',
            'mail', 'mail.inbox', 'mail.draft', 'mail.outbox', 'mail.sent', 'mail.trash',
            'mail.compose', 'mail.compose.quick-create', 'mail.view', 'mail.edit',
            'activities', 'activities.create', 'activities.edit',
            'contacts', 'contacts.persons', 'contacts.persons.create', 'contacts.persons.create.quick-create',
            'contacts.persons.edit', 'contacts.persons.view',
            'contacts.organizations', 'contacts.organizations.create',
            'contacts.organizations.create.quick-create', 'contacts.organizations.edit',
            'products', 'products.view',
        ];
    }

    /**
     * Options for the "default role" select on the configuration page.
     */
    public function roles(): array
    {
        try {
            return Role::query()
                ->orderBy('id')
                ->get(['id', 'name'])
                ->map(fn (Role $role): array => [
                    'title' => (string) $role->getAttribute('name'),
                    'value' => (string) $role->getKey(),
                ])
                ->all();
        } catch (Throwable) {
            return [];
        }
    }
}
