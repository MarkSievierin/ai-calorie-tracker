<?php

namespace App\Services;

use App\Readers\SettingReader;
use Carbon\CarbonImmutable;

/**
 * Settings with defaults from config/kkal.php, plus time helpers in the user's timezone.
 * Scoped: settings are read once per request (call refresh() after a write).
 */
class SettingsService
{
    /** @var array<string, mixed>|null */
    private ?array $cache = null;

    public function __construct(private readonly SettingReader $reader) {}

    /** @return array<string, mixed> */
    public function all(): array
    {
        $defaults = config('kkal.defaults');

        return $this->cache ??= array_merge($defaults, array_intersect_key($this->reader->stored(), $defaults));
    }

    public function get(string $key): mixed
    {
        return $this->all()[$key] ?? null;
    }

    public function refresh(): void
    {
        $this->cache = null;
    }

    public function timezone(): string
    {
        return (string) $this->get('timezone');
    }

    public function today(): string
    {
        return CarbonImmutable::now($this->timezone())->toDateString();
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} UTC bounds [from, to) of a local date */
    public function dayBounds(string $date): array
    {
        $start = CarbonImmutable::parse($date, $this->timezone())->startOfDay();

        return [$start->utc(), $start->addDay()->utc()];
    }

    public function localDate(\DateTimeInterface $at): string
    {
        return CarbonImmutable::instance($at)->setTimezone($this->timezone())->toDateString();
    }

    public static function shift(string $date, int $days): string
    {
        return CarbonImmutable::parse($date)->addDays($days)->toDateString();
    }

    /** @return list<string> every date from..to inclusive */
    public static function dates(string $from, string $to): array
    {
        $out = [];
        for ($d = CarbonImmutable::parse($from); $d->toDateString() <= $to; $d = $d->addDay()) {
            $out[] = $d->toDateString();
        }

        return $out;
    }
}
