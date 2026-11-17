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

    public function test_deficit_change_keeps_the_norm_of_past_days(): void
    {
        $this->postJson('/api/weights', ['weight_kg' => 90, 'measured_at' => '2026-11-01T08:00:00'], $this->h)->assertCreated();
        $this->putJson('/api/settings', ['height_cm' => 180, 'age' => 30], $this->h)->assertOk();
        // The web app saves every field: an unchanged deficit must not start a new period.
        $this->putJson('/api/settings', ['deficit_pct' => 15.0, 'min_kcal' => 1800], $this->h)->assertOk();
        $this->assertDatabaseCount('setting_changes', 0);

        $this->putJson('/api/settings', ['deficit_pct' => 20], $this->h)->assertOk();
        // Changed twice on one day: the last value wins, the "before" value stays 15.
        $this->putJson('/api/settings', ['deficit_pct' => 10], $this->h)->assertOk();

        // TDEE 2256: 15% before today, 10% from today on.
        $this->getJson('/api/calories?from=2026-11-03&to=2026-11-05', $this->h)->assertOk()
            ->assertJsonPath('days.0.norm_kcal', 1918)
            ->assertJsonPath('days.1.norm_kcal', 1918)
            ->assertJsonPath('days.2.norm_kcal', 2030);
        $this->getJson('/api/day?date=2026-11-04', $this->h)->assertJsonPath('norm_kcal', 1918);

        CarbonImmutable::setTestNow('2026-11-07 10:00:00');
        $this->putJson('/api/settings', ['deficit_pct' => 20], $this->h)->assertOk();
        $this->getJson('/api/calories?from=2026-11-04&to=2026-11-07', $this->h)->assertOk()
            ->assertJsonPath('days.0.norm_kcal', 1918)
            ->assertJsonPath('days.1.norm_kcal', 2030)
            ->assertJsonPath('days.3.norm_kcal', 1805);
    }

    public function test_progress_budget_sums_norms_and_intake_of_complete_logged_days(): void
    {
        $this->postJson('/api/weights', ['weight_kg' => 90, 'measured_at' => '2026-11-01T08:00:00'], $this->h)->assertCreated();
        $this->putJson('/api/settings', ['height_cm' => 180, 'age' => 30], $this->h)->assertOk();
        // Norm 1918 (TDEE 2256, 15%). Two logged complete days, one empty day, and today (not counted).
        $this->postJson('/api/meals', $this->meal(['kcal_est' => 2000, 'kcal_max' => 2200, 'eaten_at' => '2026-11-02T12:00:00']), $this->h)->assertCreated();
        $this->postJson('/api/meals', $this->meal(['kcal_est' => 1500, 'kcal_max' => 1700, 'eaten_at' => '2026-11-04T12:00:00']), $this->h)->assertCreated();
        $this->postJson('/api/meals', $this->meal(['kcal_est' => 900, 'kcal_max' => 900]), $this->h)->assertCreated();

        $this->getJson('/api/progress?days=7', $this->h)->assertOk()
            ->assertJsonPath('intake.budget.days', 2)
            ->assertJsonPath('intake.budget.norm_kcal', 3836)
            ->assertJsonPath('intake.budget.eaten_kcal', 3900)
            ->assertJsonPath('intake.budget.over_kcal', 64);
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

    public function test_product_patch_and_server_today(): void
    {
        $id = $this->postJson('/api/products', ['name' => 'Tuna', 'kcal_100g' => 110, 'protein_100g' => 25, 'fat_100g' => 1, 'carbs_100g' => 0], $this->h)
            ->assertCreated()->json('product.id');
        $this->patchJson("/api/products/$id", ['piece_g' => 185, 'notes' => ' one can '], $this->h)
            ->assertOk()->assertJsonPath('product.piece_g', 185)->assertJsonPath('product.notes', 'one can')->assertJsonPath('product.kcal_100g', 110);

        // 02:00 with the day starting at 04:00 is still the previous day for the web app.
        $this->putJson('/api/settings', ['day_start_hour' => 4], $this->h)->assertOk();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-10 02:00', 'Europe/Kyiv'));
        $this->getJson('/api/day', $this->h)->assertJsonPath('today', '2026-11-09')->assertJsonPath('date', '2026-11-09');
    }

    public function test_put_metric_sets_the_day_value_without_duplicates(): void
    {
        // A partial count typed in chat, then the phone exports the total twice.
        $this->postJson('/api/metrics', ['type' => 'steps', 'value' => 559, 'date' => '2026-11-09', 'notes' => 'by 16:00'], $this->h)->assertCreated();
        $this->putJson('/api/metrics', ['type' => 'steps', 'value' => 4200, 'date' => '2026-11-09'], $this->h)->assertOk();
        $this->putJson('/api/metrics?key=test-token', ['type' => 'steps', 'value' => 7310, 'date' => '2026-11-09'])->assertOk()
            ->assertJsonPath('metric.value', 7310);
        $this->putJson('/api/metrics', ['type' => 'steps', 'value' => 100, 'date' => '2026-11-10'], $this->h)->assertOk();
        $this->putJson('/api/metrics', ['type' => 'steps', 'value' => 'many'], $this->h)->assertStatus(422);

        $this->getJson('/api/metrics?from=2026-11-01&to=2026-11-30&types=steps', $this->h)->assertOk()
            ->assertJsonCount(2, 'items')->assertJsonPath('items.0.value', 7310)->assertJsonPath('items.1.value', 100);
    }
}
