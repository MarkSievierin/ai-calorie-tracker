<?php

namespace Tests\Feature;

use App\Mcp\Servers\KkalServer;
use App\Mcp\Tools\DeleteMeal;
use App\Mcp\Tools\GetDaySummary;
use App\Mcp\Tools\LogMeal;
use App\Mcp\Tools\LogWeight;
use App\Mcp\Tools\RestoreMeal;
use App\Models\Meal;
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
            'get_progress', 'get_metrics', 'add_note', 'get_notes'] as $tool) {
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
}
