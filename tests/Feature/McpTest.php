<?php

namespace Tests\Feature;

use App\Mcp\Servers\KkalServer;
use App\Mcp\Tools\AddNote;
use App\Mcp\Tools\DeleteMeal;
use App\Mcp\Tools\DeleteNote;
use App\Mcp\Tools\FindProducts;
use App\Mcp\Tools\GetDaySummary;
use App\Mcp\Tools\GetSettings;
use App\Mcp\Tools\LogMeal;
use App\Mcp\Tools\LogWeight;
use App\Mcp\Tools\RestoreMeal;
use App\Mcp\Tools\SaveProduct;
use App\Mcp\Tools\UpdateMeal;
use App\Mcp\Tools\UpdateProduct;
use App\Mcp\Tools\UpdateSettings;
use App\Models\Meal;
use App\Models\Note;
use App\Models\Product;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class McpTest extends TestCase
{
    use RefreshDatabase;

    private function meal(array $over = []): array
    {
        return $over + [
            'title' => 'Omelette with bread',
            'items' => [['name' => 'eggs', 'grams' => 120, 'kcal' => 180], ['name' => 'bread', 'grams' => 50, 'kcal' => 130]],
            'kcal_est' => 310, 'kcal_max' => 400, 'protein_g' => 20, 'fat_g' => 18, 'carbs_g' => 25,
            'confidence' => 'medium', 'source' => 'photo',
        ];
    }

    public function test_http_initialize_lists_tools_and_instructions(): void
    {
        $headers = ['Accept' => 'application/json, text/event-stream'];
        $init = $this->postJson('/mcp?key=test-token', [
            'jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize',
            'params' => ['protocolVersion' => '2025-06-18', 'capabilities' => (object) [], 'clientInfo' => ['name' => 't', 'version' => '1']],
        ], $headers)->assertOk();
        $this->assertStringContainsString('kcal_max', $init->json('result.instructions'));

        $list = $this->postJson('/mcp?key=test-token', ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/list'],
            $headers + ['MCP-Protocol-Version' => '2025-06-18'])->assertOk();
        $names = collect($list->json('result.tools'))->pluck('name')->all();
        foreach (['log_meal', 'update_meal', 'delete_meal', 'log_weight', 'log_metric', 'get_day_summary',
            'get_progress', 'get_metrics', 'add_note', 'get_notes', 'delete_note', 'update_product', 'get_settings', 'update_settings'] as $tool) {
            $this->assertContains($tool, $names);
        }
    }

    public function test_http_tool_call_creates_meal(): void
    {
        $res = $this->postJson('/mcp?key=test-token', [
            'jsonrpc' => '2.0', 'id' => 3, 'method' => 'tools/call',
            'params' => ['name' => 'log_meal', 'arguments' => $this->meal()],
        ], ['Accept' => 'application/json, text/event-stream', 'MCP-Protocol-Version' => '2025-06-18'])->assertOk();

        $this->assertFalse($res->json('result.isError'));
        $this->assertSame(400, (int) $res->json('result.structuredContent.day.eaten_kcal'));
        $this->assertSame(1, Meal::query()->count());
    }

    public function test_log_meal_rejects_max_below_estimate(): void
    {
        KkalServer::tool(LogMeal::class, $this->meal(['kcal_max' => 100]))->assertHasErrors();
        $this->assertSame(0, Meal::query()->count());
    }

    public function test_optional_nutrients_stay_null(): void
    {
        KkalServer::tool(LogMeal::class, $this->meal())->assertOk();
        $this->assertNull(Meal::query()->first()->sugar_g);
        KkalServer::tool(GetDaySummary::class, [])->assertStructuredContent(fn ($json) => $json
            ->where('totals.sugar_g', null)->where('totals.kcal', 400)->etc());
    }

    public function test_day_uses_user_timezone(): void
    {
        // 23:30 Kyiv on Nov 5 is 21:30 UTC; 00:30 Kyiv on Nov 6 is 22:30 UTC on Nov 5.
        KkalServer::tool(LogMeal::class, $this->meal(['eaten_at' => '2026-11-05T23:30:00+02:00']))->assertOk();
        KkalServer::tool(LogMeal::class, $this->meal(['eaten_at' => '2026-11-06T00:30:00', 'kcal_max' => 500]))->assertOk();

        KkalServer::tool(GetDaySummary::class, ['date' => '2026-11-05'])
            ->assertStructuredContent(fn ($j) => $j->where('totals.kcal', 400)->etc());
        KkalServer::tool(GetDaySummary::class, ['date' => '2026-11-06'])
            ->assertStructuredContent(fn ($j) => $j->where('totals.kcal', 500)->etc());
    }

    public function test_norm_and_remaining_after_weight(): void
    {
        CarbonImmutable::setTestNow('2026-11-05 10:00:00');
        config(['kkal.defaults.height_cm' => 180, 'kkal.defaults.age' => 30]);
        KkalServer::tool(LogWeight::class, ['weight_kg' => 90])->assertOk();
        // 90 kg, 180 cm, 30 y, k 1.2 -> TDEE 2256, norm 1918.
        KkalServer::tool(LogMeal::class, $this->meal())->assertOk()
            ->assertStructuredContent(fn ($j) => $j->where('day.norm_kcal', 1918)->where('day.remaining_kcal', 1518)->etc());
    }

    public function test_no_norm_without_height_and_age(): void
    {
        KkalServer::tool(LogWeight::class, ['weight_kg' => 90])->assertOk();
        KkalServer::tool(GetDaySummary::class, [])->assertStructuredContent(fn ($j) => $j->where('norm_kcal', null)->etc());
    }

    public function test_deleted_meal_does_not_count_and_can_be_restored(): void
    {
        KkalServer::tool(LogMeal::class, $this->meal())->assertOk();
        $id = Meal::query()->first()->id;
        KkalServer::tool(DeleteMeal::class, ['id' => $id])
            ->assertStructuredContent(fn ($j) => $j->where('day.eaten_kcal', 0)->etc());
        KkalServer::tool(RestoreMeal::class, ['id' => $id])->assertOk();
        $this->assertSame(1, Meal::query()->count());
    }

    private function sticks(array $over = []): Product
    {
        KkalServer::tool(SaveProduct::class, $over + [
            'name' => 'Salted sticks Vladka', 'kcal_100g' => 376, 'protein_100g' => 10, 'fat_100g' => 4, 'carbs_100g' => 74,
            'piece_g' => 1.25, 'notes' => 'weighed: 20 pcs = 25 g',
        ])->assertOk();

        return Product::query()->firstOrFail();
    }

    private function productMeal(array $items): array
    {
        return ['title' => 'Snack', 'items' => $items, 'confidence' => 'high', 'source' => 'label'];
    }

    public function test_product_items_are_counted_by_the_server(): void
    {
        $p = $this->sticks();
        KkalServer::tool(LogMeal::class, $this->productMeal([
            ['product_id' => $p->id, 'pieces' => 10],
            ['name' => 'tea with sugar', 'grams' => 250, 'kcal' => 40, 'kcal_max' => 50, 'protein_g' => 0, 'fat_g' => 0, 'carbs_g' => 10],
        ]))->assertOk()->assertStructuredContent(fn ($j) => $j
            ->where('items.0.name', 'Salted sticks Vladka')->where('items.0.grams', 12.5)->where('items.0.kcal', 47)
            ->where('kcal_max', 97)->where('day.eaten_kcal', 97)->etc());

        $meal = Meal::query()->firstOrFail();
        $this->assertSame([87.0, 97.0, 1.3, 0.5, 19.3], [$meal->kcal_est, $meal->kcal_max, $meal->protein_g, $meal->fat_g, $meal->carbs_g]);
        $this->assertSame(10, (int) $meal->items[0]['pieces']);
    }

    public function test_product_items_reject_meal_totals_and_incomplete_items(): void
    {
        $p = $this->sticks();
        KkalServer::tool(LogMeal::class, $this->productMeal([['product_id' => $p->id, 'grams' => 25]]) + ['kcal_est' => 94, 'kcal_max' => 94])
            ->assertHasErrors(['Omit kcal_est']);
        KkalServer::tool(LogMeal::class, $this->productMeal([['product_id' => $p->id, 'grams' => 25], ['name' => 'tea', 'kcal' => 40]]))
            ->assertHasErrors(['protein_g']);
        KkalServer::tool(LogMeal::class, $this->productMeal([['product_id' => $p->id, 'grams' => 25, 'pieces' => 20]]))
            ->assertHasErrors(['not both']);
        KkalServer::tool(LogMeal::class, $this->productMeal([['product_id' => 999, 'grams' => 25]]))->assertHasErrors(['not found']);
        $this->assertSame(0, Meal::query()->count());
    }

    public function test_pieces_need_a_piece_weight(): void
    {
        $p = $this->sticks(['piece_g' => null]);
        KkalServer::tool(LogMeal::class, $this->productMeal([['product_id' => $p->id, 'pieces' => 10]]))->assertHasErrors(['do not guess']);
    }

    public function test_estimated_meal_still_needs_totals(): void
    {
        $meal = $this->meal();
        unset($meal['kcal_est'], $meal['protein_g']);
        KkalServer::tool(LogMeal::class, $meal)->assertHasErrors(['kcal_est field is required']);
    }

    public function test_update_with_product_items_sums_again(): void
    {
        $p = $this->sticks();
        KkalServer::tool(LogMeal::class, $this->meal())->assertOk();
        $id = Meal::query()->firstOrFail()->id;
        KkalServer::tool(UpdateMeal::class, ['id' => $id, 'items' => [['product_id' => $p->id, 'pieces' => 20]]])
            ->assertStructuredContent(fn ($j) => $j->where('meal.kcal_est', 94)->where('meal.kcal_max', 94)->where('meal.carbs_g', 18.5)->etc());
    }

    public function test_resaving_a_label_keeps_piece_weight_and_notes(): void
    {
        $this->sticks();
        KkalServer::tool(SaveProduct::class, [
            'name' => 'Salted sticks Vladka', 'kcal_100g' => 380, 'protein_100g' => 10, 'fat_100g' => 4, 'carbs_100g' => 74,
        ])->assertOk();
        KkalServer::tool(FindProducts::class, ['query' => 'vladka'])->assertStructuredContent(fn ($j) => $j
            ->where('products.0.kcal_100g', 380)->where('products.0.piece_g', 1.25)->where('products.0.notes', 'weighed: 20 pcs = 25 g')->etc());
    }

    public function test_instructions_include_latest_notes(): void
    {
        KkalServer::tool(AddNote::class, ['kind' => 'observation', 'text' => 'One stick weighs 1.25 g'])->assertOk();
        $init = $this->postJson('/mcp?key=test-token', [
            'jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize',
            'params' => ['protocolVersion' => '2025-06-18', 'capabilities' => (object) [], 'clientInfo' => ['name' => 't', 'version' => '1']],
        ], ['Accept' => 'application/json, text/event-stream'])->assertOk();
        $this->assertMatchesRegularExpression('/#\d+, \d{4}-\d\d-\d\d, observation: One stick weighs 1.25 g/', $init->json('result.instructions'));
    }

    private function instructions(): string
    {
        return (string) $this->postJson('/mcp?key=test-token', [
            'jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize',
            'params' => ['protocolVersion' => '2025-06-18', 'capabilities' => (object) [], 'clientInfo' => ['name' => 't', 'version' => '1']],
        ], ['Accept' => 'application/json, text/event-stream'])->assertOk()->json('result.instructions');
    }

    public function test_day_start_hour_keeps_a_night_snack_in_the_evening_day(): void
    {
        KkalServer::tool(UpdateSettings::class, ['day_start_hour' => 4])
            ->assertStructuredContent(fn ($j) => $j->where('settings.day_start_hour', 4)->etc());

        // 01:05 on Nov 10 belongs to Nov 9; 05:00 on Nov 10 already is Nov 10.
        KkalServer::tool(LogMeal::class, $this->meal(['eaten_at' => '2026-11-10T01:05:00']))
            ->assertStructuredContent(fn ($j) => $j->where('day.date', '2026-11-09')->where('eaten_at', '2026-11-10T01:05:00+02:00')->etc());
        KkalServer::tool(LogMeal::class, $this->meal(['eaten_at' => '2026-11-10T05:00:00', 'kcal_max' => 500]))
            ->assertStructuredContent(fn ($j) => $j->where('day.date', '2026-11-10')->etc());

        KkalServer::tool(GetDaySummary::class, ['date' => '2026-11-09'])
            ->assertStructuredContent(fn ($j) => $j->where('totals.kcal', 400)->etc());
        KkalServer::tool(GetDaySummary::class, ['date' => '2026-11-10'])
            ->assertStructuredContent(fn ($j) => $j->where('totals.kcal', 500)->etc());

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-10 02:00', 'Europe/Kyiv'));
        KkalServer::tool(GetSettings::class, [])->assertStructuredContent(fn ($j) => $j->where('today', '2026-11-09')->etc());
    }

    public function test_settings_tool_sets_the_profile_and_rejects_bad_values(): void
    {
        CarbonImmutable::setTestNow('2026-11-05 10:00:00');
        KkalServer::tool(LogWeight::class, ['weight_kg' => 90])->assertOk();
        KkalServer::tool(UpdateSettings::class, ['height_cm' => 180, 'age' => 30, 'target_weight_kg' => 80])
            ->assertStructuredContent(fn ($j) => $j->where('settings.target_weight_kg', 80)->where('norm_kcal', 1918)->etc());
        KkalServer::tool(UpdateSettings::class, ['deficit_pct' => 50])->assertHasErrors();
        $this->assertStringContainsString('height 180 cm, age 30', $this->instructions());
    }

    public function test_rules_stay_in_instructions_and_notes_can_be_deleted(): void
    {
        KkalServer::tool(AddNote::class, ['kind' => 'rule', 'text' => 'Coffee is always with 10 ml of milk'])->assertOk();
        KkalServer::tool(AddNote::class, ['kind' => 'observation', 'text' => 'Stale finding'])->assertOk();
        // Rules are not pushed out by newer findings.
        foreach (range(1, 12) as $i) {
            KkalServer::tool(AddNote::class, ['kind' => 'observation', 'text' => "Finding $i"])->assertOk();
        }
        $rule = Note::query()->where('kind', 'rule')->firstOrFail();

        $text = $this->instructions();
        $this->assertStringContainsString("- #{$rule->id}: Coffee is always with 10 ml of milk", $text);
        $this->assertStringContainsString('Finding 12', $text);
        $this->assertStringNotContainsString('Stale finding', $text);

        KkalServer::tool(DeleteNote::class, ['id' => $rule->id])->assertOk();
        $this->assertStringNotContainsString('10 ml of milk', $this->instructions());
    }

    public function test_instructions_list_saved_products(): void
    {
        $p = $this->sticks();
        $this->assertStringContainsString(
            "- id {$p->id}: Salted sticks Vladka; 376 kcal/100 g; piece 1.25 g; note: weighed: 20 pcs = 25 g",
            $this->instructions(),
        );
    }

    public function test_product_is_changed_by_id_without_the_label(): void
    {
        $p = $this->sticks(['piece_g' => null]);
        KkalServer::tool(UpdateProduct::class, ['id' => $p->id, 'piece_g' => 1.3, 'name' => 'Sweet sticks Vladka'])
            ->assertStructuredContent(fn ($j) => $j->where('product.name', 'Sweet sticks Vladka')->where('product.piece_g', 1.3)
                ->where('product.kcal_100g', 376)->where('product.notes', 'weighed: 20 pcs = 25 g')->etc());
        $this->assertSame(1, Product::query()->count());

        KkalServer::tool(SaveProduct::class, ['name' => 'Banana', 'kcal_100g' => 89, 'protein_100g' => 1, 'fat_100g' => 0, 'carbs_100g' => 23])->assertOk();
        KkalServer::tool(UpdateProduct::class, ['id' => $p->id, 'name' => 'Banana'])->assertHasErrors(['already named']);
        KkalServer::tool(UpdateProduct::class, ['id' => $p->id, 'kcal_100g' => 5000])->assertHasErrors();
    }

    public function test_products_are_found_by_words_in_any_order_and_form(): void
    {
        KkalServer::tool(SaveProduct::class, ['name' => 'Соломка сладкая Vladka 200 г', 'kcal_100g' => 376, 'protein_100g' => 10, 'fat_100g' => 4, 'carbs_100g' => 74])->assertOk();
        KkalServer::tool(SaveProduct::class, ['name' => 'Кефір 1% Люстдорф', 'kcal_100g' => 35, 'protein_100g' => 3, 'fat_100g' => 1, 'carbs_100g' => 4])->assertOk();

        foreach (['vladka соломка', 'соломки', 'СОЛОМКУ сладкую'] as $query) {
            KkalServer::tool(FindProducts::class, ['query' => $query])->assertStructuredContent(fn ($j) => $j
                ->has('products', 1)->where('products.0.name', 'Соломка сладкая Vladka 200 г')->etc());
        }
        KkalServer::tool(FindProducts::class, ['query' => 'соломка люстдорф'])->assertStructuredContent(fn ($j) => $j->has('products', 0)->etc());
        KkalServer::tool(FindProducts::class, ['query' => ''])->assertStructuredContent(fn ($j) => $j->has('products', 2)->etc());
    }
}
