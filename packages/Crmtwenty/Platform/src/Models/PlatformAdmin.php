<?php

namespace Crmtwenty\Platform\Models;

use Crmtwenty\Platform\Notifications\PlatformResetPassword;
use Crmtwenty\Platform\Support\Tenancy;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Administrator of the platform (manages companies, not CRM data).
 *
 * @property int $id
 * @property string $name
 * @property string $username
 * @property string $email
 * @property string $password
 * @property bool $status
 * @property \Illuminate\Support\Carbon|null $last_login_at
 */
class PlatformAdmin extends Authenticatable
{
    use Notifiable;

    protected $connection = Tenancy::CONNECTION;

    protected $table = 'platform_admins';

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'status',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'status' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    /**
     * @param  string  $token
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new PlatformResetPassword($token));
    }
}
