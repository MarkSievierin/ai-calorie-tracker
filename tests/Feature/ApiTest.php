<?php

namespace Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    private array $h = ['Authorization' => 'Bearer test-token'];

    protected function setUp(): void
    {
        parent::setUp();
        CarbonImmutable::setTestNow('2026-11-05 10:00:00');
    }

    private function meal(array $over = []): array
    {
        return $over + [
            'title' => 'Soup', 'items' => [['name' => 'soup', 'grams' => 300, 'kcal' => 250]],
            'kcal_est' => 250, 'kcal_max' => 300, 'protein_g' => 10, 'fat_g' => 8, 'carbs_g' => 30,
            'confidence' => 'medium', 'source' => 'text',
        ];
    }

    public function test_meal_crud_with_soft_delete(): void
    {
        $id = $this->postJson('/api/meals', $this->meal(), $this->h)->assertCreated()->json('meal.id');
        $this->patchJson("/api/meals/$id", ['kcal_max' => 350], $this->h)->assertOk()->assertJsonPath('meal.kcal_max', 350);
        $this->patchJson("/api/meals/$id", ['kcal_max' => 100], $this->h)->assertStatus(422);
        $this->getJson('/api/day', $this->h)->assertJsonPath('totals.kcal', 350);

        $this->deleteJson("/api/meals/$id", [], $this->h)->assertOk();
        $this->getJson('/api/day', $this->h)->assertJsonPath('totals.kcal', 0)->assertJsonCount(0, 'meals');
        $this->postJson("/api/meals/$id/restore", [], $this->h)->assertOk();
        $this->getJson('/api/day', $this->h)->assertJsonCount(1, 'meals');
    }

    public function test_settings_reject_unknown_and_out_of_range(): void
    {
        $this->putJson('/api/settings?key=test-token', ['foo' => 1])->assertStatus(422);
        $this->putJson('/api/settings', ['deficit_pct' => 30], $this->h)->assertStatus(422);
        $this->putJson('/api/settings', ['deficit_pct' => 20, 'target_weight_kg' => null], $this->h)
            ->assertOk()->assertJsonPath('settings.deficit_pct', 20);
    }

    public function test_norm_follows_settings(): void
    {
        $this->postJson('/api/weights', ['weight_kg' => 90], $this->h)->assertCreated()->assertJsonPath('trend_kg', 90);
        $this->getJson('/api/day', $this->h)->assertJsonPath('norm_kcal', null);
        $this->putJson('/api/settings', ['height_cm' => 180, 'age' => 30], $this->h)->assertOk();
        $this->getJson('/api/day', $this->h)->assertJsonPath('norm_kcal', 1918);
        $this->putJson('/api/settings', ['deficit_pct' => 10], $this->h)->assertOk();
        // 2256 * 0.9 = 2030.4
        $this->getJson('/api/day', $this->h)->assertJsonPath('norm_kcal', 2030);
    }

    public function test_series_endpoints(): void
    {
        $this->postJson('/api/meals', $this->meal(), $this->h)->assertCreated();
        $this->postJson('/api/weights', ['weight_kg' => 100], $this->h)->assertCreated();
        $this->postJson('/api/metrics', ['type' => 'steps', 'value' => 8000], $this->h)->assertCreated();
        $this->postJson('/api/metrics', ['type' => 'Bad Type', 'value' => 1], $this->h)->assertStatus(422);

        $this->getJson('/api/calories?from=2026-11-01', $this->h)->assertOk()->assertJsonCount(5, 'days')
            ->assertJsonPath('days.4.kcal', 300);
        $this->getJson('/api/weights', $this->h)->assertOk()->assertJsonCount(1, 'points');
        $this->getJson('/api/metrics', $this->h)->assertOk()->assertJsonPath('items.0.value', 8000);
        $this->getJson('/api/progress', $this->h)->assertOk()->assertJsonPath('flags.low_logging', true);
    }

    public function test_notes(): void
    {
        $this->postJson('/api/notes', ['kind' => 'observation', 'text' => 'hi', 'related' => ['2026-11-01']], $this->h)->assertCreated();
        $this->getJson('/api/notes?kind=observation', $this->h)->assertJsonPath('notes.0.author', 'user');
    }
}
