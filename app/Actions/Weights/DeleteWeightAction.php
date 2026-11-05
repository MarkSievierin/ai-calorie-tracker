<?php

namespace App\Actions\Weights;

use App\Readers\WeightReader;
use App\Services\EnergyService;
use App\Writers\WeightWriter;

class DeleteWeightAction
{
    public function __construct(
        private readonly WeightReader $reader,
        private readonly WeightWriter $writer,
        private readonly EnergyService $energy,
    ) {}

    public function handle(int $id): void
    {
        $this->writer->delete($this->reader->findOrFail($id));
        $this->energy->refresh();
    }
}
