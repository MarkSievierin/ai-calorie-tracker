<?php

namespace App\Http\Requests;

use App\Dto\SettingsUpdateDto;
use Illuminate\Validation\Validator;

class UpdateSettingsRequest extends ApiRequest
{
    public static function fieldRules(): array
    {
        return [
            'timezone' => ['sometimes', 'string', 'timezone:all'],
            'height_cm' => ['sometimes', 'nullable', 'numeric', 'between:100,250'],
            'age' => ['sometimes', 'nullable', 'integer', 'between:10,120'],
            'target_weight_kg' => ['sometimes', 'nullable', 'numeric', 'between:30,300'],
            'start_tdee' => ['sometimes', 'nullable', 'numeric', 'between:1000,6000'],
            'activity_factor' => ['sometimes', 'numeric', 'between:1.1,2'],
            'deficit_pct' => ['sometimes', 'numeric', 'between:10,20'],
            'min_kcal' => ['sometimes', 'numeric', 'between:1000,4000'],
        ];
    }

    /** Unknown keys are an error, not silently dropped. Only the body counts: the query may carry ?key=. */
    public function after(): array
    {
        return [function (Validator $validator) {
            $unknown = array_diff(array_keys($this->json()->all()), array_keys(static::fieldRules()));
            if ($unknown) {
                $validator->errors()->add('settings', 'Unknown settings: '.implode(', ', $unknown));
            }
        }];
    }

    public function toDto(): SettingsUpdateDto
    {
        return new SettingsUpdateDto($this->validated());
    }
}
