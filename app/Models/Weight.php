<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read int $id
 * @property CarbonImmutable $measured_at
 * @property float $weight_kg
 * @property string|null $notes
 */
class Weight extends Model
{
    use SoftDeletes;

    protected $fillable = ['measured_at', 'weight_kg', 'notes'];

    protected function casts(): array
    {
        return ['measured_at' => 'immutable_datetime', 'weight_kg' => 'float'];
    }

    public function toApi(string $timezone): array
    {
        return [
            'id' => $this->id,
            'measured_at' => $this->measured_at->setTimezone($timezone)->toIso8601String(),
            'weight_kg' => $this->weight_kg,
            'notes' => $this->notes,
        ];
    }
}
