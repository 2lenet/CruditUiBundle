<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Format;

use Lle\CruditUiBundle\Locale\RequestLocale;
use Twig\Environment;
use Twig\Extra\Intl\IntlExtension;

/**
 * Numbers and dates in the format of the locale of the request, always in Latin digits: `ar_MA` gives `1.234,5`,
 * `ar_SA` gives `1,234.5` instead of `١٬٢٣٤٫٥`. Used by the `ui_*` filters, and by PHP code that formats outside
 * a template (a brick, a service): both give the same result.
 *
 * The methods take the arguments of the `format_*` filters of twig/intl-extra, without the locale and the calendar:
 * dates are always in the Gregorian calendar, the default of `format_*` too. The `short` date always has the year in
 * 4 digits (`10/13/2026` in en, `13.10.2026` in de, instead of `10/13/26` and `13.10.26`). Dates are converted to the
 * default timezone of Twig, as the `format_*` filters do; `timezone: false` keeps the timezone of the date (a date or a
 * time alone, which must not change day or hour).
 *
 * The keyword is only given to the formatters: the default locale of PHP is left alone, Symfony Intl (country
 * names, forms) does not read a locale with keywords. The extension of twig/intl-extra used here is a separate
 * instance: the prototypes of formatters configured in `twig_extra.intl` by the project do not apply.
 */
class Formatter
{
    public const string KEYWORDS = 'numbers=latn';

    /**
     * Skeletons of the time added to the date with a 4-digit year, by time format.
     */
    private const array TIME_SKELETONS = ['none' => '', 'short' => 'jmm', 'medium' => 'jmmss'];

    private ?IntlExtension $intl = null;

    /**
     * @var array<string, \IntlDatePatternGenerator>
     */
    private array $patternGenerators = [];

    public function __construct(
        protected RequestLocale $requestLocale,
        protected Environment $twig,
    ) {
    }

    /**
     * @param array<string, int|float|string> $attrs
     */
    public function number(mixed $number, array $attrs = [], string $style = 'decimal', string $type = 'default'): string
    {
        return $this->intl()->formatNumber($number, $attrs, $style, $type, $this->locale());
    }

    /**
     * @param array<string, int|float|string> $attrs
     */
    public function currency(mixed $amount, string $currency, array $attrs = []): string
    {
        return $this->intl()->formatCurrency($amount, $currency, $attrs, $this->locale());
    }

    public function datetime(
        mixed $date,
        ?string $dateFormat = null,
        ?string $timeFormat = null,
        string $pattern = '',
        mixed $timezone = null,
    ): string {
        $locale = $this->locale();
        // null is the medium time of format_datetime
        $pattern = $this->pattern($locale, $dateFormat, $timeFormat ?? 'medium', $pattern);

        return $this->intl()->formatDateTime($this->twig, $date, $dateFormat, $timeFormat, $pattern, $timezone, 'gregorian', $locale);
    }

    public function date(mixed $date, ?string $dateFormat = null, string $pattern = '', mixed $timezone = null): string
    {
        $locale = $this->locale();
        $pattern = $this->pattern($locale, $dateFormat, 'none', $pattern);

        return $this->intl()->formatDate($this->twig, $date, $dateFormat, $pattern, $timezone, 'gregorian', $locale);
    }

    public function time(mixed $date, ?string $timeFormat = null, string $pattern = '', mixed $timezone = null): string
    {
        return $this->intl()->formatTime($this->twig, $date, $timeFormat, $pattern, $timezone, 'gregorian', $this->locale());
    }

    /**
     * Locale of the request with the Latin digits: `ar_SA@numbers=latn`. A `numbers` keyword already in the locale
     * is replaced, ICU would keep the first one (`ar_SA@numbers=arab` gives `ar_SA@numbers=latn`).
     */
    public function locale(): string
    {
        [$locale, $keywords] = explode('@', $this->requestLocale->get(), 2) + [1 => ''];
        $keywords = array_filter(
            explode(';', $keywords),
            static fn (string $keyword): bool => '' !== $keyword && 'numbers' !== strtolower(trim(explode('=', $keyword, 2)[0])),
        );
        $keywords[] = self::KEYWORDS;

        return $locale . '@' . implode(';', $keywords);
    }

    /**
     * Pattern of the `short` date with a 4-digit year, in the order and with the separators of the locale, asked to
     * ICU from a skeleton (`yyyyMMdd` gives `MM/dd/yyyy` in en). The pattern given by the template, the other date
     * formats and the long times are left to ICU.
     */
    private function pattern(string $locale, ?string $dateFormat, string $timeFormat, string $pattern): string
    {
        if ('' !== $pattern || 'short' !== $dateFormat || !isset(self::TIME_SKELETONS[$timeFormat])) {
            return $pattern;
        }

        $generator = $this->patternGenerators[$locale] ??= new \IntlDatePatternGenerator($locale);

        $bestPattern = $generator->getBestPattern('yyyyMMdd' . self::TIME_SKELETONS[$timeFormat]);

        return false === $bestPattern ? '' : $bestPattern;
    }

    private function intl(): IntlExtension
    {
        return $this->intl ??= new IntlExtension();
    }
}
