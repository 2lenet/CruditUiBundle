<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Twig;

use Lle\CruditUiBundle\Format\Formatter;
use Twig\Attribute\AsTwigFilter;

/**
 * Filters `ui_number`, `ui_currency`, `ui_date`, `ui_datetime` and `ui_time`: numbers and dates in the format of the
 * locale of the request, always in Latin digits and in the Gregorian calendar. The formatting is done by the
 * {@see Formatter} service, which PHP code uses outside a template.
 */
class FormatExtension
{
    public function __construct(
        protected Formatter $formatter,
    ) {
    }

    /**
     * @param array<string, int|float|string> $attrs
     */
    #[AsTwigFilter('ui_number')]
    public function number(mixed $number, array $attrs = [], string $style = 'decimal', string $type = 'default'): string
    {
        return $this->formatter->number($number, $attrs, $style, $type);
    }

    /**
     * @param array<string, int|float|string> $attrs
     */
    #[AsTwigFilter('ui_currency')]
    public function currency(mixed $amount, string $currency, array $attrs = []): string
    {
        return $this->formatter->currency($amount, $currency, $attrs);
    }

    #[AsTwigFilter('ui_datetime')]
    public function datetime(
        mixed $date,
        ?string $dateFormat = null,
        ?string $timeFormat = null,
        string $pattern = '',
        mixed $timezone = null,
    ): string {
        return $this->formatter->datetime($date, $dateFormat, $timeFormat, $pattern, $timezone);
    }

    #[AsTwigFilter('ui_date')]
    public function date(mixed $date, ?string $dateFormat = null, string $pattern = '', mixed $timezone = null): string
    {
        return $this->formatter->date($date, $dateFormat, $pattern, $timezone);
    }

    #[AsTwigFilter('ui_time')]
    public function time(mixed $date, ?string $timeFormat = null, string $pattern = '', mixed $timezone = null): string
    {
        return $this->formatter->time($date, $timeFormat, $pattern, $timezone);
    }
}
