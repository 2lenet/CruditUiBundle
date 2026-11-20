<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Filter;

/**
 * The ready-made periods of the filter `Filter:Period`: the one source of their dates, for the component and for a
 * project that stores a period by its name ("this_month") and works out its dates again later, so that a saved set of
 * filters stays true from one month to the next.
 */
enum PeriodPreset: string
{
    case Today = 'today';
    case Last7Days = 'last_7_days';
    case Last30Days = 'last_30_days';
    case ThisMonth = 'this_month';
    case LastMonth = 'last_month';
    case NextMonth = 'next_month';
    case ThisYear = 'this_year';

    /**
     * The first and the last day (Y-m-d) of the period around $now.
     *
     * @return array{from: string, to: string}
     */
    public function range(\DateTimeInterface $now): array
    {
        $today = \DateTimeImmutable::createFromInterface($now)->setTime(0, 0);

        [$from, $to] = match ($this) {
            self::Today => [$today, $today],
            self::Last7Days => [$today->modify('-6 days'), $today],
            self::Last30Days => [$today->modify('-29 days'), $today],
            self::ThisMonth => [$today->modify('first day of this month'), $today->modify('last day of this month')],
            self::LastMonth => [$today->modify('first day of last month'), $today->modify('last day of last month')],
            self::NextMonth => [$today->modify('first day of next month'), $today->modify('last day of next month')],
            self::ThisYear => [$today->setDate((int) $today->format('Y'), 1, 1), $today->setDate((int) $today->format('Y'), 12, 31)],
        };

        return ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')];
    }

    /**
     * The period of a name, null for a name that is not one (an unknown or an empty value is ignored, never an error).
     */
    public static function fromName(mixed $name): ?self
    {
        return is_string($name) ? self::tryFrom($name) : null;
    }
}
