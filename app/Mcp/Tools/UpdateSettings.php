<?php

namespace App\Mcp\Tools;

use App\Actions\Reports\GetProfileAction;
use App\Actions\Settings\UpdateSettingsAction;
use App\Dto\SettingsUpdateDto;
use App\Http\Requests\UpdateSettingsRequest;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Name('update_settings')]
#[IsIdempotent]
#[Description('Change the user profile or goal: pass only the settings that change. Height, age, target weight, deficit and day start hour live here, not in log_metric. Only on the user\'s explicit request: these values change the daily norm. A new deficit or minimum norm applies from today on, past days keep their norm. Returns the profile with the new norm.')]
class UpdateSettings extends KkalTool
{
    public function handle(Request $request): ResponseFactory
    {
        $values = $request->validate(UpdateSettingsRequest::fieldRules());
        app(UpdateSettingsAction::class)->handle(new SettingsUpdateDto($values));

        return $this->ok(app(GetProfileAction::class)->handle());
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'height_cm' => $schema->number()->description('Height, cm'),
            'age' => $schema->integer()->description('Age, years'),
            'activity_factor' => $schema->number()->description('1.2 sedentary, 1.375 light (walks), 1.55 moderate (training 3-5 times a week)'),
            'target_weight_kg' => $schema->number()->nullable()->description('Goal weight, kg; null = no target'),
            'deficit_pct' => $schema->number()->description('Deficit from TDEE, 10-20 %'),
            'min_kcal' => $schema->number()->description('The norm never goes below this, kcal'),
            'start_tdee' => $schema->number()->nullable()->description('Manual starting TDEE, kcal; null = formula'),
            'day_start_hour' => $schema->integer()->description('Hour (0-8) when a new day starts. 4 = food eaten at 01:00 counts towards the previous day'),
            'timezone' => $schema->string()->description('IANA timezone, e.g. Europe/Kyiv'),
        ];
    }
}
