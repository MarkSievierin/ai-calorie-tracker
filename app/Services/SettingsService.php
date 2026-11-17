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

    /** @var array<string, array<string, mixed>>|null */
    private ?array $history = null;

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

    /**
     * Dated values of the settings that shape the norm (config kkal.dated_settings).
     * A key without history has had its current value all along.
     *
     * @return array<string, array<string, mixed>> key => [effective_on => value], oldest first
     */
    public function history(): array
    {
        return $this->history ??= $this->reader->history(config('kkal.dated_settings'));
    }

    public function refresh(): void
    {
        $this->cache = null;
        $this->history = null;
    }

    public function timezone(): string
    {
        return (string) $this->get('timezone');
    }

    /**
     * Hour at which the user's day starts. People who stay up late eat after midnight: with 4, a snack at 01:00
     * counts towards the evening it belongs to, and nobody has to fake the meal time to keep the day together.
     */
    public function dayStartHour(): int
    {
        return (int) $this->get('day_start_hour');
    }

    public function today(): string
    {
        return $this->localDate(CarbonImmutable::now());
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} UTC bounds [from, to) of a local date */
    public function dayBounds(string $date): array
    {
        $day = CarbonImmutable::parse($date, $this->timezone())->startOfDay();
        $hour = $this->dayStartHour();

        return [$day->setTime($hour, 0)->utc(), $day->addDay()->setTime($hour, 0)->utc()];
    }

    /** The user's day a moment belongs to (shifted by the day start hour). */
    public function localDate(\DateTimeInterface $at): string
    {
        return CarbonImmutable::instance($at)->setTimezone($this->timezone())->subHours($this->dayStartHour())->toDateString();
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
