<?php

namespace Crmtwenty\Platform\Models;

use Crmtwenty\Platform\Support\Tenancy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Platform-wide index of sign-in names: tells the login form which company a username or
 * e-mail address belongs to. Kept in sync with every company's users table.
 *
 * @property int $id
 * @property int $company_id
 * @property int $user_id
 * @property string $email
 * @property string|null $username
 */
class Login extends Model
{
    protected $connection = Tenancy::CONNECTION;

    protected $table = 'platform_logins';

    protected $fillable = [
        'company_id',
        'user_id',
        'email',
        'username',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
