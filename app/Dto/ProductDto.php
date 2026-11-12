<?php

namespace App\Dto;

/**
 * Nutrition per 100 g from a label.
 * Optional fields (portion, piece weight, notes) are written only when sent, so an agent that re-saves a label
 * without them does not wipe what was stored before. Sending null clears a field.
 */
final readonly class ProductDto
{
    private const OPTIONAL = ['default_portion_g', 'piece_g', 'notes'];

    /** @param list<string> $sent optional keys present in the input */
    public function __construct(
        public string $name,
        public float $kcal100g,
        public float $protein100g,
        public float $fat100g,
        public float $carbs100g,
        public ?float $defaultPortionG = null,
        public ?float $pieceG = null,
        public ?string $notes = null,
        public array $sent = self::OPTIONAL,
    ) {}

    public static function fromArray(array $data): self
    {
        $f = fn (string $k) => isset($data[$k]) ? (float) $data[$k] : null;
        $notes = isset($data['notes']) ? trim((string) $data['notes']) : null;

        return new self(
            name: (string) $data['name'],
            kcal100g: (float) $data['kcal_100g'],
            protein100g: (float) $data['protein_100g'],
            fat100g: (float) $data['fat_100g'],
            carbs100g: (float) $data['carbs_100g'],
            defaultPortionG: $f('default_portion_g'),
            pieceG: $f('piece_g'),
            notes: $notes === '' ? null : $notes,
            sent: array_values(array_filter(self::OPTIONAL, fn (string $k) => array_key_exists($k, $data))),
        );
    }

    public function toAttributes(): array
    {
        $optional = ['default_portion_g' => $this->defaultPortionG, 'piece_g' => $this->pieceG, 'notes' => $this->notes];

        return [
            'kcal_100g' => $this->kcal100g,
            'protein_100g' => $this->protein100g,
            'fat_100g' => $this->fat100g,
            'carbs_100g' => $this->carbs100g,
        ] + array_intersect_key($optional, array_flip($this->sent));
    }
}
