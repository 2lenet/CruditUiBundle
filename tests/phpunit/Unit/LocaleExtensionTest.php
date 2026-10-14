<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Tests\Unit;

use Lle\CruditUiBundle\Twig\LocaleExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class LocaleExtensionTest extends TestCase
{
    private const array RTL_LANGUAGES = ['ar', 'fa', 'he', 'ur'];

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function locales(): iterable
    {
        yield 'French' => ['fr', 'fr', 'ltr'];
        yield 'French of Belgium' => ['fr_BE', 'fr-BE', 'ltr'];
        yield 'Arabic' => ['ar', 'ar', 'rtl'];
        yield 'Arabic of Morocco' => ['ar_MA', 'ar-MA', 'rtl'];
        yield 'Arabic in BCP 47' => ['ar-SA', 'ar-SA', 'rtl'];
        yield 'Hebrew' => ['he', 'he', 'rtl'];
        yield 'Persian' => ['fa_IR', 'fa-IR', 'rtl'];
        yield 'Urdu' => ['ur', 'ur', 'rtl'];
    }

    #[DataProvider('locales')]
    public function testLangAndDirComeFromTheRequest(string $locale, string $lang, string $dir): void
    {
        $request = new Request();
        $request->setLocale($locale);
        $extension = $this->extension($request);

        self::assertSame($lang, $extension->lang());
        self::assertSame($dir, $extension->dir());
    }

    public function testNoRequestUsesTheDefaultLocale(): void
    {
        $extension = $this->extension(null, 'ar_MA');

        self::assertSame('ar-MA', $extension->lang());
        self::assertSame('rtl', $extension->dir());
    }

    public function testRtlLanguagesOfTheProject(): void
    {
        $request = new Request();
        $request->setLocale('ar');
        $extension = new LocaleExtension($this->requestStack($request), 'fr', ['he']);

        self::assertSame('ltr', $extension->dir());
    }

    private function extension(?Request $request, string $defaultLocale = 'fr'): LocaleExtension
    {
        return new LocaleExtension($this->requestStack($request), $defaultLocale, self::RTL_LANGUAGES);
    }

    private function requestStack(?Request $request): RequestStack
    {
        $requestStack = new RequestStack();
        if (null !== $request) {
            $requestStack->push($request);
        }

        return $requestStack;
    }
}
