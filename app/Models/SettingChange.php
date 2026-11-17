<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A dated value of a setting that shapes the norm (see config kkal.dated_settings).
 *
 * @property int $id
 * @property string $key
 * @property string|null $value JSON
 * @property string $effective_on local date (Y-m-d) from which the value applies
 */
class SettingChange extends Model
{
    /** effective_on of the value that was in effect before the first recorded change. */
    public const SINCE_ALWAYS = '1970-01-01';

    const UPDATED_AT = null;

    protected $fillable = ['key', 'value', 'effective_on'];
}
