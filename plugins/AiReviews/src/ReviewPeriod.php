<?php

namespace LifeWheel\Plugins\AiReviews;

use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

final class ReviewPeriod
{
    public const TYPES = ['daily', 'weekly', 'monthly', 'quarterly', 'yearly', 'custom'];

    public static function range(string $type, ?string $startDate = null, ?string $endDate = null): array
    {
        if (! in_array($type, self::TYPES, true)) {
            throw new InvalidArgumentException('Unsupported review period.');
        }

        $now = CarbonImmutable::parse(now()->toDateString());

        return match ($type) {
            'daily' => [$now->startOfDay(), $now->endOfDay()],
            'weekly' => [$now->startOfWeek(), $now->endOfWeek()],
            'monthly' => [$now->startOfMonth(), $now->endOfMonth()],
            'quarterly' => [$now->startOfQuarter(), $now->endOfQuarter()],
            'yearly' => [$now->startOfYear(), $now->endOfYear()],
            'custom' => self::customRange($startDate, $endDate),
        };
    }

    private static function customRange(?string $startDate, ?string $endDate): array
    {
        if (! $startDate || ! $endDate) {
            throw new InvalidArgumentException('Custom reviews require start and end dates.');
        }

        $start = Carbon::parse($startDate)->startOfDay()->toImmutable();
        $end = Carbon::parse($endDate)->endOfDay()->toImmutable();

        if ($end->lt($start)) {
            throw new InvalidArgumentException('Review end date must be after start date.');
        }

        return [$start, $end];
    }
}
