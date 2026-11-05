<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property-read int $id
 * @property Carbon $date
 * @property string $type
 * @property float $value
 * @property string|null $notes
 */
class Metric extends Model
{
    use SoftDeletes;

    protected $fillable = ['date', 'type', 'value', 'notes'];

    protected function casts(): array
    {
        return ['date' => 'date:Y-m-d', 'value' => 'float'];
    }

    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date->format('Y-m-d'),
            'type' => $this->type,
            'value' => $this->value,
            'notes' => $this->notes,
        ];
    }
}
