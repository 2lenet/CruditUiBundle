<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Tests\Unit;

use Lle\CruditUiBundle\Format\Formatter;
use Lle\CruditUiBundle\Locale\RequestLocale;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Intl\Countries;
use Twig\Environment;
use Twig\Extension\CoreExtension;
use Twig\Loader\ArrayLoader;

class FormatterTest extends TestCase
{
    private string $defaultLocale;

    protected function setUp(): void
    {
        $this->defaultLocale = \Locale::getDefault();
    }

    protected function tearDown(): void
    {
        // Request::setLocale() changes the default locale of PHP
        \Locale::setDefault($this->defaultLocale);
    }

    /**
     * The numbers are stable from one version of ICU to the other, the dates are not (spaces, separators): only their
     * digits and their order are checked.
     *
     * @return iterable<string, array{string, string, list<string>}>
     */
    public static function locales(): iterable
    {
        // thin no-break space between the thousands
        yield 'French' => ['fr', "1\u{202F}234,5", ['13', '10', '2026']];
        yield 'English: year in 4 digits' => ['en', '1,234.5', ['10', '13', '2026']];
        yield 'German: year in 4 digits' => ['de', '1.234,5', ['13', '10', '2026']];
        yield 'Arabic of Saudi Arabia: Latin digits' => ['ar_SA', '1,234.5', ['13', '10', '2026']];
        yield 'Arabic of Morocco: separators of the country' => ['ar_MA', '1.234,5', ['13', '10', '2026']];
        yield 'Persian: Latin digits' => ['fa', '1,234.5', ['2026', '10', '13']];
        yield 'Arabic digits asked by the locale' => ['ar_SA@numbers=arab', '1,234.5', ['13', '10', '2026']];
    }

    /**
     * @param list<string> $date numbers of the date, in their order
     */
    #[DataProvider('locales')]
    public function testFormatsFollowTheLocaleInLatinDigits(string $locale, string $number, array $date): void
    {
        $formatter = $this->formatter($locale);

        self::assertSame($number, $formatter->number(1234.5));
        self::assertLatinDigits($date, $formatter->date(new \DateTimeImmutable('2026-10-13 12:00'), 'short', timezone: 'UTC'));
    }

    /**
     * The leading zeros are those of the `short` format of the locale, only the year is in 4 digits.
     *
     * @return iterable<string, array{string, list<string>, list<string>}>
     */
    public static function leadingZeros(): iterable
    {
        yield 'English: no leading zero' => ['en', ['1', '5', '2026'], ['9', '05']];
        yield 'Spanish: no leading zero' => ['es', ['5', '1', '2026'], ['9', '05']];
        yield 'German: leading zeros' => ['de', ['05', '01', '2026'], ['09', '05']];
        yield 'French: leading zeros' => ['fr', ['05', '01', '2026'], ['09', '05']];
    }

    /**
     * @param list<string> $date numbers of the date, in their order
     * @param list<string> $time numbers of the time, in their order
     */
    #[DataProvider('leadingZeros')]
    public function testTheShortDateKeepsTheLeadingZerosOfTheLocale(string $locale, array $date, array $time): void
    {
        $formatter = $this->formatter($locale);
        $value = new \DateTimeImmutable('2026-01-05 09:05', new \DateTimeZone('UTC'));

        self::assertLatinDigits($date, $formatter->date($value, 'short', timezone: 'UTC'));
        self::assertLatinDigits([...$date, ...$time], $formatter->datetime($value, 'short', 'short', timezone: 'UTC'));
    }

    /**
     * Locales whose default calendar, or the keyword, is not Gregorian: ICU would take the patterns of that calendar,
     * with an era (`13‏/10‏/2026 م` in `ar_SA` with ICU 74, `AD2026/10/13` in Japanese) or a short year.
     *
     * @return iterable<string, array{string}>
     */
    public static function otherCalendars(): iterable
    {
        yield 'Arabic of Saudi Arabia' => ['ar_SA'];
        yield 'Islamic calendar asked by the locale' => ['ar_SA@calendar=islamic-umalqura'];
        yield 'Japanese calendar asked by the locale' => ['ja@calendar=japanese'];
        yield 'Persian calendar asked by the locale' => ['fa@calendar=persian'];
        yield 'Thai, Buddhist calendar by default' => ['th'];
    }

    #[DataProvider('otherCalendars')]
    public function testNoEraFromTheDefaultCalendarOfTheLocale(string $locale): void
    {
        $actual = $this->formatter($locale)->date(new \DateTimeImmutable('2026-10-13 12:00'), 'short', timezone: 'UTC');

        self::assertMatchesRegularExpression('/(^|\D)2026(\D|$)/', $actual, 'year in 4 digits');
        self::assertDoesNotMatchRegularExpression('/\p{L}/u', $actual, 'era');
    }

    public function testNoEraInTheLongFormatsEither(): void
    {
        $actual = $this->formatter('ja@calendar=japanese')->date(new \DateTimeImmutable('2026-10-13 12:00'), 'medium', timezone: 'UTC');

        // without the keyword, the Gregorian era of the Japanese patterns (西暦2026年10月13日); with the Japanese
        // calendar, its own era (令和8年10月13日)
        self::assertStringNotContainsString('西暦', $actual);
        self::assertStringNotContainsString('令和', $actual);
        self::assertStringContainsString('2026', $actual);
    }

    public function testCurrency(): void
    {
        self::assertStringContainsString('1,234.50', $this->formatter('ar_SA')->currency(1234.5, 'SAR'));
    }

    public function testDatetimeAndTime(): void
    {
        $date = new \DateTimeImmutable('2026-10-13 09:05', new \DateTimeZone('UTC'));
        $formatter = $this->formatter('ar_SA');

        self::assertLatinDigits(['13', '10', '2026', '9', '05'], $formatter->datetime($date, 'short', 'short', timezone: 'UTC'));
        self::assertLatinDigits(['13', '10', '2026', '9', '05', '00'], $formatter->datetime($date, 'short', timezone: 'UTC'));
        self::assertLatinDigits(['9', '05'], $formatter->time($date, 'short', timezone: 'UTC'));
    }

    public function testTwelveHourClockInEnglish(): void
    {
        $date = new \DateTimeImmutable('2026-10-13 14:05', new \DateTimeZone('UTC'));
        $formatter = $this->formatter('en');

        $datetime = $formatter->datetime($date, 'short', 'short', timezone: 'UTC');
        self::assertLatinDigits(['10', '13', '2026', '2', '05'], $datetime);
        self::assertStringContainsString('PM', $datetime);
        $time = $formatter->time($date, 'medium', timezone: 'UTC');
        self::assertLatinDigits(['2', '05', '00'], $time);
        self::assertStringContainsString('PM', $time);
    }

    public function testPatternAndOtherFormatsAreLeftToIcu(): void
    {
        $date = new \DateTimeImmutable('2026-10-13 14:05', new \DateTimeZone('UTC'));
        $formatter = $this->formatter('en');

        self::assertSame('2026-10-13', $formatter->date($date, 'short', 'yyyy-MM-dd', 'UTC'));
        self::assertSame('Oct 13, 2026', $formatter->date($date, 'medium', timezone: 'UTC'));
    }

    public function testDatesAreConvertedToTheTimezoneOfTwig(): void
    {
        $twig = $this->twig();
        $twig->getExtension(CoreExtension::class)->setTimezone('Europe/Paris');
        $formatter = $this->formatter('fr', $twig);
        $date = new \DateTimeImmutable('2026-10-13 23:30', new \DateTimeZone('UTC'));

        self::assertLatinDigits(['14', '10', '2026', '01', '30'], $formatter->datetime($date, 'short', 'short'));
        // a date alone keeps its day
        self::assertLatinDigits(['13', '10', '2026'], $formatter->date($date, 'short', timezone: false));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function keywords(): iterable
    {
        yield 'no keyword' => ['fr', 'fr@numbers=latn;calendar=gregorian'];
        yield 'numbers keyword replaced' => ['ar_SA@numbers=arab', 'ar_SA@numbers=latn;calendar=gregorian'];
        yield 'calendar keyword replaced' => ['fa@calendar=persian', 'fa@numbers=latn;calendar=gregorian'];
        yield 'keywords in capitals replaced' => ['ja@CALENDAR=japanese;NUMBERS=jpan', 'ja@numbers=latn;calendar=gregorian'];
        yield 'other keyword kept' => ['ar@NUMBERS=arab;collation=standard', 'ar@collation=standard;numbers=latn;calendar=gregorian'];
    }

    #[DataProvider('keywords')]
    public function testTheNumbersAreLatinAndTheCalendarGregorian(string $locale, string $expected): void
    {
        self::assertSame($expected, $this->formatter($locale)->locale());
    }

    public function testNoRequestUsesTheDefaultLocale(): void
    {
        self::assertSame('ar_MA@numbers=latn;calendar=gregorian', (new Formatter(new RequestLocale(new RequestStack(), 'ar_MA'), $this->twig()))->locale());
    }

    public function testTheDefaultLocaleOfPhpIsLeftAlone(): void
    {
        $formatter = $this->formatter('fr');
        $formatter->number(1);

        self::assertSame('fr', \Locale::getDefault());
        self::assertSame('France', Countries::getName('FR'));
    }

    private function formatter(string $locale, ?Environment $twig = null): Formatter
    {
        $request = new Request();
        $request->setLocale($locale);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        return new Formatter(new RequestLocale($requestStack, 'en'), $twig ?? $this->twig());
    }

    /**
     * @param list<string> $numbers
     */
    private static function assertLatinDigits(array $numbers, string $actual): void
    {
        self::assertDoesNotMatchRegularExpression('/[\x{0660}-\x{0669}\x{06F0}-\x{06F9}]/u', $actual, 'Arabic-Indic or Persian digits');
        preg_match_all('/\d+/', $actual, $matches);
        self::assertSame($numbers, $matches[0], $actual);
    }

    private function twig(): Environment
    {
        return new Environment(new ArrayLoader());
    }
}
