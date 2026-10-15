<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Tests\Unit;

use Lle\CruditUiBundle\Locale\RequestLocale;
use Lle\CruditUiBundle\Twig\FormatExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Intl\Countries;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class FormatExtensionTest extends TestCase
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
     * The numbers are stable from one version of ICU to the other, the dates are not (`13‏/10‏/2026 م` in `ar_SA`
     * since ICU 74, era included): only their digits and their order are checked.
     *
     * @return iterable<string, array{string, string, list<string>}>
     */
    public static function locales(): iterable
    {
        // thin no-break space between the thousands
        yield 'French' => ['fr', "1\u{202F}234,5", ['13', '10', '2026']];
        yield 'English' => ['en', '1,234.5', ['10', '13', '26']];
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
        $extension = $this->extension($locale);

        self::assertSame($number, $extension->number(1234.5));
        self::assertLatinDigits($date, $extension->date($this->twig(), new \DateTimeImmutable('2026-10-13 12:00'), 'short', timezone: 'UTC'));
    }

    public function testCurrency(): void
    {
        self::assertStringContainsString('1,234.50', $this->extension('ar_SA')->currency(1234.5, 'SAR'));
    }

    public function testDatetimeAndTime(): void
    {
        $date = new \DateTimeImmutable('2026-10-13 09:05', new \DateTimeZone('UTC'));
        $extension = $this->extension('ar_SA');

        self::assertLatinDigits(['13', '10', '2026', '9', '05'], $extension->datetime($this->twig(), $date, 'short', 'short', timezone: 'UTC'));
        self::assertLatinDigits(['9', '05'], $extension->time($this->twig(), $date, 'short', timezone: 'UTC'));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function keywords(): iterable
    {
        yield 'no keyword' => ['fr', 'fr@numbers=latn'];
        yield 'numbers keyword replaced' => ['ar_SA@numbers=arab', 'ar_SA@numbers=latn'];
        yield 'other keyword kept' => ['fa@calendar=persian', 'fa@calendar=persian;numbers=latn'];
        yield 'numbers keyword among others' => ['ar@NUMBERS=arab;collation=standard', 'ar@collation=standard;numbers=latn'];
    }

    #[DataProvider('keywords')]
    public function testTheNumbersKeywordIsAlwaysLatin(string $locale, string $expected): void
    {
        self::assertSame($expected, $this->extension($locale)->locale());
    }

    public function testNoRequestUsesTheDefaultLocale(): void
    {
        self::assertSame('ar_MA@numbers=latn', (new FormatExtension(new RequestLocale(new RequestStack(), 'ar_MA')))->locale());
    }

    public function testTheDefaultLocaleOfPhpIsLeftAlone(): void
    {
        $extension = $this->extension('fr');
        $extension->number(1);

        self::assertSame('fr', \Locale::getDefault());
        self::assertSame('France', Countries::getName('FR'));
    }

    private function extension(string $locale): FormatExtension
    {
        $request = new Request();
        $request->setLocale($locale);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        return new FormatExtension(new RequestLocale($requestStack, 'en'));
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
