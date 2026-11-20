<?php

declare(strict_types=1);

namespace phpunit\Unit;

use Lle\CruditUiBundle\Filter\PeriodPreset;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PeriodPresetTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string, string, string}>
     */
    public static function ranges(): iterable
    {
        yield 'today' => ['today', '2026-11-19 15:30', '2026-11-19', '2026-11-19'];
        yield 'last 7 days' => ['last_7_days', '2026-11-19', '2026-11-13', '2026-11-19'];
        yield 'last 30 days over a year end' => ['last_30_days', '2026-01-10', '2025-12-12', '2026-01-10'];
        yield 'this month' => ['this_month', '2026-11-19', '2026-11-01', '2026-11-30'];
        yield 'this month, 29 February' => ['this_month', '2028-02-10', '2028-02-01', '2028-02-29'];
        yield 'last month from January' => ['last_month', '2026-01-31', '2025-12-01', '2025-12-31'];
        yield 'last month from 31 March' => ['last_month', '2026-03-31', '2026-02-01', '2026-02-28'];
        yield 'next month from 31 January' => ['next_month', '2026-01-31', '2026-02-01', '2026-02-28'];
        yield 'next month from December' => ['next_month', '2026-12-15', '2027-01-01', '2027-01-31'];
        yield 'this year' => ['this_year', '2026-07-04', '2026-01-01', '2026-12-31'];
    }

    #[DataProvider('ranges')]
    public function testTheRangeOfAPeriod(string $name, string $now, string $from, string $to): void
    {
        $preset = PeriodPreset::fromName($name);

        self::assertNotNull($preset);
        self::assertSame(['from' => $from, 'to' => $to], $preset->range(new \DateTimeImmutable($now)));
    }

    public function testAMutableDateIsNotChanged(): void
    {
        $now = new \DateTime('2026-11-19');
        PeriodPreset::ThisMonth->range($now);

        self::assertSame('2026-11-19', $now->format('Y-m-d'));
    }

    public function testAnUnknownNameIsNull(): void
    {
        self::assertNull(PeriodPreset::fromName('next_century'));
        self::assertNull(PeriodPreset::fromName(''));
        self::assertNull(PeriodPreset::fromName(['this_month']));
        self::assertNull(PeriodPreset::fromName(null));
    }
}
