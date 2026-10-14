<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Tests\Unit;

use Lle\CruditUiBundle\Twig\FormatExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Intl\Countries;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

#[RequiresPhpExtension('intl')]
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
     * @return iterable<string, array{string, string, string}>
     */
    public static function locales(): iterable
    {
        // thin no-break space between the thousands
        yield 'French' => ['fr', "1\u{202F}234,5", '13/10/2026'];
        yield 'English' => ['en', '1,234.5', '10/13/26'];
        yield 'Arabic of Saudi Arabia: Latin digits' => ['ar_SA', '1,234.5', '13‏/10‏/2026'];
        yield 'Arabic of Morocco: separators of the country' => ['ar_MA', '1.234,5', '13‏/10‏/2026'];
        yield 'Persian: Gregorian calendar, Latin digits' => ['fa', '1,234.5', '2026/10/13'];
    }

    #[DataProvider('locales')]
    public function testFormatsFollowTheLocaleInLatinDigits(string $locale, string $number, string $date): void
    {
        $extension = $this->extension($locale);

        self::assertSame($number, $extension->number(1234.5));
        self::assertSame($date, $extension->date($this->twig(), new \DateTimeImmutable('2026-10-13 12:00'), 'short', timezone: 'UTC'));
    }

    public function testCurrency(): void
    {
        self::assertStringContainsString('1,234.50', $this->extension('ar_SA')->currency(1234.5, 'SAR'));
    }

    public function testDatetimeAndTime(): void
    {
        $date = new \DateTimeImmutable('2026-10-13 09:05', new \DateTimeZone('UTC'));
        $extension = $this->extension('ar_SA');

        self::assertSame('13‏/10‏/2026، 9:05 ص', $extension->datetime($this->twig(), $date, 'short', 'short', timezone: 'UTC'));
        self::assertSame('9:05 ص', $extension->time($this->twig(), $date, 'short', timezone: 'UTC'));
    }

    public function testNoRequestUsesTheDefaultLocale(): void
    {
        self::assertSame('ar_MA@numbers=latn', (new FormatExtension(new RequestStack(), 'ar_MA'))->locale());
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

        return new FormatExtension($requestStack, 'en');
    }

    private function twig(): Environment
    {
        return new Environment(new ArrayLoader());
    }
}
