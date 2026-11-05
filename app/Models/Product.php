<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property-read int $id
 * @property string $name
 * @property float $kcal_100g
 * @property float $protein_100g
 * @property float $fat_100g
 * @property float $carbs_100g
 * @property float|null $default_portion_g
 */
class Product extends Model
{
    protected $fillable = ['name', 'kcal_100g', 'protein_100g', 'fat_100g', 'carbs_100g', 'default_portion_g'];

    protected function casts(): array
    {
        return [
            'kcal_100g' => 'float',
            'protein_100g' => 'float',
            'fat_100g' => 'float',
            'carbs_100g' => 'float',
            'default_portion_g' => 'float',
        ];
    }

    public function toApi(): array
    {
        return $this->only(['id', 'name', 'kcal_100g', 'protein_100g', 'fat_100g', 'carbs_100g', 'default_portion_g']);
    }
}
