<?php

declare(strict_types = 1);

use App\ValueObjects\DateRange;
use Carbon\CarbonImmutable;

it('spans whole days inclusively', function (): void {
    $range = new DateRange(CarbonImmutable::parse('2026-03-01 15:30'), CarbonImmutable::parse('2026-03-10 02:00'));

    expect($range->days())->toBe(10)
        ->and($range->from->toDateTimeString())->toBe('2026-03-01 00:00:00')
        ->and($range->to->toDateTimeString())->toBe('2026-03-10 23:59:59');
});

it('lists every day in the range', function (): void {
    $range = DateRange::fromStrings('2026-03-01', '2026-03-04');

    expect($range->eachDay())->toBe(['2026-03-01', '2026-03-02', '2026-03-03', '2026-03-04']);
});

it('returns the previous period of equal length', function (): void {
    $previous = DateRange::fromStrings('2026-03-08', '2026-03-14')->previous();

    expect($previous->toArray())->toBe(['from' => '2026-03-01', 'to' => '2026-03-07']);
});

it('defaults to the last 30 days', function (): void {
    expect(DateRange::fromStrings(null, null)->days())->toBe(30);
});
