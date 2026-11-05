<?php

namespace App\Writers;

use App\Dto\WeightDto;
use App\Models\Weight;

class WeightWriter
{
    public function create(WeightDto $dto): Weight
    {
        return Weight::query()->create($dto->toAttributes());
    }

    public function delete(Weight $weight): void
    {
        $weight->delete();
    }
}
