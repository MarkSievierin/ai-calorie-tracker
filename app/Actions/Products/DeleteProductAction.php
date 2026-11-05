<?php

namespace App\Actions\Products;

use App\Readers\ProductReader;
use App\Writers\ProductWriter;

class DeleteProductAction
{
    public function __construct(private readonly ProductReader $reader, private readonly ProductWriter $writer) {}

    public function handle(int $id): void
    {
        $this->writer->delete($this->reader->findOrFail($id));
    }
}
