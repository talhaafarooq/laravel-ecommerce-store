<?php

declare(strict_types = 1);

namespace App\ValueObjects;

use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use InvalidArgumentException;

/**
 * An inclusive date range, normalised to whole days.
 */
final readonly class DateRange
{
    public CarbonImmutable $from;

    public CarbonImmutable $to;

    public function __construct(CarbonImmutable $from, CarbonImmutable $to)
    {
        if ($from->greaterThan($to)) {
            throw new InvalidArgumentException('The start of a date range must be before its end.');
        }

        $this->from = $from->startOfDay();
        $this->to   = $to->endOfDay();
    }

    public static function lastDays(int $days): self
    {
        $today = CarbonImmutable::now();

        return new self($today->subDays(max($days - 1, 0)), $today);
    }

    public static function fromStrings(?string $from, ?string $to, int $defaultDays = 30): self
    {
        if ($from === null || $to === null) {
            return self::lastDays($defaultDays);
        }

        return new self(CarbonImmutable::parse($from), CarbonImmutable::parse($to));
    }

    /**
     * Get the number of days covered by the range.
     */
    public function days(): int
    {
        return (int) $this->from->diffInDays($this->to->startOfDay()) + 1;
    }

    /**
     * Get the range immediately preceding this one with the same length.
     */
    public function previous(): self
    {
        $days = $this->days();

        return new self($this->from->subDays($days), $this->from->subDay());
    }

    /**
     * Iterate over each day of the range as Y-m-d strings.
     *
     * @return list<string>
     */
    public function eachDay(): array
    {
        $days = [];

        foreach (CarbonPeriod::create($this->from, $this->to->startOfDay()) as $day) {
            $days[] = $day->format('Y-m-d');
        }

        return $days;
    }

    /**
     * @return array{from: string, to: string}
     */
    public function toArray(): array
    {
        return [
            'from' => $this->from->toDateString(),
            'to'   => $this->to->toDateString(),
        ];
    }
}
