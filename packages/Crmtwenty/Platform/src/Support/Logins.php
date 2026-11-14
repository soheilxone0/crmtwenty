<?php

namespace Crmtwenty\Platform\Support;

use Crmtwenty\Platform\Models\Company;
use Crmtwenty\Platform\Models\Login;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Keeps the platform-wide sign-in index in step with the companies' users.
 */
class Logins
{
    public function __construct(protected Tenancy $tenancy) {}

    /**
     * Normalise a username or e-mail address for lookups.
     */
    public static function normalize(?string $value): string
    {
        return Str::lower(trim((string) $value));
    }

    /**
     * Find the index entry for a username or e-mail address.
     */
    public function find(?string $login): ?Login
    {
        $login = static::normalize($login);

        if ($login === '') {
            return null;
        }

        try {
            return Login::query()
                ->where(str_contains($login, '@') ? 'email' : 'username', $login)
                ->first();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Whether a username or e-mail address is used by anybody other than the given user.
     */
    public function taken(string $column, ?string $value, ?int $companyId, ?int $userId): bool
    {
        $value = static::normalize($value);

        if ($value === '') {
            return false;
        }

        return Login::query()
            ->where($column, $value)
            ->where(function ($query) use ($companyId, $userId) {
                $query->where('company_id', '!=', (int) $companyId)
                    ->orWhere('user_id', '!=', (int) $userId);
            })
            ->exists();
    }

    /**
     * The company whose data is active right now (the primary company when none was activated).
     */
    public function currentCompany(): ?Company
    {
        return $this->tenancy->current()
            ?? Company::query()->where('table_prefix', '')->first();
    }

    /**
     * Record (or update) one user of a company.
     */
    public function put(Company $company, int $userId, string $email, ?string $username): void
    {
        Login::query()->updateOrCreate(
            ['company_id' => $company->id, 'user_id' => $userId],
            ['email' => static::normalize($email), 'username' => filled($username) ? static::normalize($username) : null],
        );
    }

    public function forget(Company $company, int $userId): void
    {
        Login::query()->where('company_id', $company->id)->where('user_id', $userId)->delete();
    }

    /**
     * Rebuild the entries of a whole company from its users table.
     *
     * Returns the sign-in names that could not be indexed because another company uses them.
     */
    public function syncCompany(Company $company): array
    {
        $users = DB::connection(Tenancy::CONNECTION)
            ->table($company->table_prefix.'users')
            ->get();

        $conflicts = [];

        $ids = [];

        foreach ($users as $user) {
            $username = property_exists($user, 'username') ? $user->username : null;

            if ($this->taken('email', $user->email, $company->id, $user->id)
                || $this->taken('username', $username, $company->id, $user->id)) {
                $conflicts[] = $user->email;

                continue;
            }

            $this->put($company, (int) $user->id, (string) $user->email, $username);

            $ids[] = $user->id;
        }

        Login::query()
            ->where('company_id', $company->id)
            ->whereNotIn('user_id', $ids ?: [0])
            ->delete();

        return $conflicts;
    }
}
