<?php

namespace App\Actions\Weights;

use App\Dto\WeightDto;
use App\Models\Weight;
use App\Services\EnergyService;
use App\Writers\WeightWriter;

class LogWeightAction
{
    public function __construct(private readonly WeightWriter $writer, private readonly EnergyService $energy) {}

    public function handle(WeightDto $dto): Weight
    {
        $weight = $this->writer->create($dto);
        $this->energy->refresh();

        return $weight;
    }
}
