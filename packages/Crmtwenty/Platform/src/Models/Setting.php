<?php

namespace Crmtwenty\Platform\Models;

use Crmtwenty\Platform\Support\Tenancy;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * Key/value settings of the platform.
 *
 * @property string $key
 * @property string|null $value
 */
class Setting extends Model
{
    protected $connection = Tenancy::CONNECTION;

    protected $table = 'platform_settings';

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        try {
            return static::query()->whereKey($key)->value('value') ?? $default;
        } catch (Throwable) {
            return $default;
        }
    }

    public static function put(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
