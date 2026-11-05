<?php

namespace App\Http\Requests;

use App\Services\SettingsService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Base request. Access is checked by the token middleware, so authorize() is always true.
 * Each request exposes static fieldRules() so MCP tools validate with exactly the same rules.
 */
abstract class ApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    abstract public static function fieldRules(): array;

    public function rules(): array
    {
        return static::fieldRules();
    }

    protected function settings(): SettingsService
    {
        return app(SettingsService::class);
    }
}
