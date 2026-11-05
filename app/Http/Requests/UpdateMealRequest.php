<?php

namespace App\Http\Requests;

use App\Dto\MealUpdateDto;

class UpdateMealRequest extends ApiRequest
{
    public static function fieldRules(): array
    {
        return StoreMealRequest::fieldRules(partial: true);
    }

    public function toDto(): MealUpdateDto
    {
        return MealUpdateDto::fromArray($this->validated(), $this->settings()->timezone());
    }
}
