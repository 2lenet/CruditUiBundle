<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Twig;

use Lle\CruditUiBundle\Locale\RequestLocale;
use Twig\Attribute\AsTwigFilter;
use Twig\Environment;
use Twig\Extra\Intl\IntlExtension;

/**
 * Numbers and dates in the format of the locale of the request, always in Latin digits: `ar_MA` gives `1.234,5`,
 * `ar_SA` gives `1,234.5` instead of `١٬٢٣٤٫٥`. The filters take the arguments of the `format_*` filters of
 * twig/intl-extra, without the locale and the calendar: dates are always in the Gregorian calendar, the default
 * of `format_*` too.
 *
 * The keyword is only given to the formatters: the default locale of PHP is left alone, Symfony Intl (country
 * names, forms) does not read a locale with keywords. The filters use their own `IntlExtension`: the prototypes of
 * formatters configured in `twig_extra.intl` by the project do not apply to them.
 */
class FormatExtension
{
    public const string KEYWORDS = 'numbers=latn';

    private ?IntlExtension $intl = null;

    public function __construct(
        protected RequestLocale $requestLocale,
    ) {
    }

    /**
     * @param array<string, int|float|string> $attrs
     */
    #[AsTwigFilter('ui_number')]
    public function number(mixed $number, array $attrs = [], string $style = 'decimal', string $type = 'default'): string
    {
        return $this->intl()->formatNumber($number, $attrs, $style, $type, $this->locale());
    }

    /**
     * @param array<string, int|float|string> $attrs
     */
    #[AsTwigFilter('ui_currency')]
    public function currency(mixed $amount, string $currency, array $attrs = []): string
    {
        return $this->intl()->formatCurrency($amount, $currency, $attrs, $this->locale());
    }

    #[AsTwigFilter('ui_datetime')]
    public function datetime(
        Environment $env,
        mixed $date,
        ?string $dateFormat = null,
        ?string $timeFormat = null,
        string $pattern = '',
        mixed $timezone = null,
    ): string {
        return $this->intl()->formatDateTime($env, $date, $dateFormat, $timeFormat, $pattern, $timezone, 'gregorian', $this->locale());
    }

    #[AsTwigFilter('ui_date')]
    public function date(Environment $env, mixed $date, ?string $dateFormat = null, string $pattern = '', mixed $timezone = null): string
    {
        return $this->intl()->formatDate($env, $date, $dateFormat, $pattern, $timezone, 'gregorian', $this->locale());
    }

    #[AsTwigFilter('ui_time')]
    public function time(Environment $env, mixed $date, ?string $timeFormat = null, string $pattern = '', mixed $timezone = null): string
    {
        return $this->intl()->formatTime($env, $date, $timeFormat, $pattern, $timezone, 'gregorian', $this->locale());
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

    private function intl(): IntlExtension
    {
        return $this->intl ??= new IntlExtension();
    }
}
