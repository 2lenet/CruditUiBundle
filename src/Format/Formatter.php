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
 * dates are always in the Gregorian calendar. The calendar is also forced in the locale, as ICU takes the patterns in
 * the default calendar of the locale (`13/10/2026 م` in `ar_SA` with ICU 74, `AD2026/10/13` in
 * `ja@calendar=japanese`). The `short` date, alone or with a `short` or `medium` time, has the year in 4 digits and
 * the rest usually as the `short` format of the locale, leading zeros included (`1/5/2026` in en, `05.01.2026` in de,
 * instead of `1/5/26` and `05.01.26`); a few locales differ slightly (`dd. MM. yyyy` in cs). Dates are converted to the default timezone of Twig, as the `format_*` filters do;
 * `timezone: false` keeps the timezone of the date (a date or a time alone, which must not change day or hour).
 *
 * The keywords are only given to the formatters: the default locale of PHP is left alone, Symfony Intl (country
 * names, forms) does not read a locale with keywords. The extension of twig/intl-extra used here is a separate
 * instance: the prototypes of formatters configured in `twig_extra.intl` by the project do not apply.
 */
class Formatter
{
    public const string KEYWORDS = 'numbers=latn;calendar=gregorian';

    /**
     * Skeletons of the time added to the date with a 4-digit year, by time format.
     */
    private const array TIME_SKELETONS = ['none' => '', 'short' => 'jmm', 'medium' => 'jmmss'];

    private ?IntlExtension $intl = null;

    /**
     * @var array<string, \IntlDatePatternGenerator>
     */
    private array $patternGenerators = [];

    /**
     * @var array<string, string>
     */
    private array $dateSkeletons = [];

    /**
     * Twig gives the default timezone (`twig.date.timezone`), which the formatters of twig/intl-extra read from it:
     * the same as the `format_*` filters. No circular reference, because FormatExtension is an extension by
     * attributes, whose runtime is loaded lazily: a classic AbstractExtension receiving the Formatter would make one
     * (Environment, FormatExtension, Formatter, Environment).
     */
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
     * Locale of the request with the Latin digits and the Gregorian calendar: `ar_SA@numbers=latn;calendar=gregorian`.
     * The `numbers` and `calendar` keywords already in the locale are replaced, ICU would keep the first ones
     * (`fa@calendar=persian` gives `fa@numbers=latn;calendar=gregorian`).
     */
    public function locale(): string
    {
        [$locale, $keywords] = explode('@', $this->requestLocale->get(), 2) + [1 => ''];
        $keywords = array_filter(
            explode(';', $keywords),
            static fn (string $keyword): bool => '' !== $keyword
                && !\in_array(strtolower(trim(explode('=', $keyword, 2)[0])), ['numbers', 'calendar'], true),
        );
        $keywords[] = self::KEYWORDS;

        return $locale . '@' . implode(';', $keywords);
    }

    /**
     * Pattern of the `short` date with a 4-digit year, asked to ICU from a skeleton made from the `short` pattern of
     * the locale (`M/d/yy` gives `yyyyMd`, then `M/d/yyyy` in en): the order, the separators and the leading zeros of
     * the locale are usually kept, ICU taking the pattern from the other formats of the locale. The pattern given by the template, the other date formats and the long times are left to
     * ICU.
     */
    private function pattern(string $locale, ?string $dateFormat, string $timeFormat, string $pattern): string
    {
        if ('' !== $pattern || 'short' !== $dateFormat || !isset(self::TIME_SKELETONS[$timeFormat])) {
            return $pattern;
        }

        $generator = $this->patternGenerators[$locale] ??= new \IntlDatePatternGenerator($locale);

        $bestPattern = $generator->getBestPattern($this->dateSkeleton($locale) . self::TIME_SKELETONS[$timeFormat]);

        return false === $bestPattern ? '' : $bestPattern;
    }

    /**
     * Skeleton of the `short` date of the locale with a 4-digit year: `dd.MM.yy` gives `yyyyMMdd`, `d/M/yy` gives
     * `yyyyMd`. The quoted texts of the pattern are ignored; an escaped quote (`''`) splits a quoted text in two,
     * which does no harm: only the `M` and `d` outside the quoted texts matter.
     */
    private function dateSkeleton(string $locale): string
    {
        if (isset($this->dateSkeletons[$locale])) {
            return $this->dateSkeletons[$locale];
        }

        $formatter = new \IntlDateFormatter($locale, \IntlDateFormatter::SHORT, \IntlDateFormatter::NONE, 'UTC', \IntlDateFormatter::GREGORIAN);
        $shortPattern = (string) preg_replace("/'[^']*'/", '', (string) $formatter->getPattern());
        $skeleton = 'yyyy';
        foreach (['M', 'd'] as $field) {
            $skeleton .= 1 === preg_match('/' . $field . '+/', $shortPattern, $match) ? $match[0] : $field;
        }

        return $this->dateSkeletons[$locale] = $skeleton;
    }

    private function intl(): IntlExtension
    {
        return $this->intl ??= new IntlExtension();
    }
}
