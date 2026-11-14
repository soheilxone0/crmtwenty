<?php

namespace Crmtwenty\Platform\Models;

use Crmtwenty\Platform\Support\Tenancy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A company (workspace) on the platform. Its CRM data lives in its own set of tables,
 * named with `table_prefix` on top of the installation's base prefix.
 *
 * @property int $id
 * @property string $name
 * @property string $table_prefix
 * @property string $status
 * @property int|null $manager_user_id
 * @property string|null $manager_name
 * @property string|null $manager_email
 * @property string|null $manager_username
 * @property string|null $phone
 * @property string|null $notes
 * @property string|null $migrated_revision
 * @property \Illuminate\Support\Carbon|null $last_activity_at
 * @property \Illuminate\Support\Carbon|null $created_at
 */
class Company extends Model
{
    public const STATUS_PROVISIONING = 'provisioning';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    protected $connection = Tenancy::CONNECTION;

    protected $table = 'platform_companies';

    protected $fillable = [
        'name',
        'table_prefix',
        'status',
        'manager_user_id',
        'manager_name',
        'manager_email',
        'manager_username',
        'phone',
        'notes',
        'migrated_revision',
        'last_activity_at',
    ];

    protected $casts = [
        'last_activity_at' => 'datetime',
    ];

    public function logins(): HasMany
    {
        return $this->hasMany(Login::class, 'company_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * The original single-company installation, which keeps the unprefixed tables.
     */
    public function isPrimary(): bool
    {
        return $this->table_prefix === '';
    }
}
