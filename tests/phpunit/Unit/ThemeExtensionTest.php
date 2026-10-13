<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Tests\Unit;

use Lle\CruditUiBundle\Twig\ThemeExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ThemeExtensionTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string|null}>
     */
    public static function cookies(): iterable
    {
        yield 'light' => ['light', 'light'];
        yield 'dark' => ['dark', 'dark'];
        yield 'system: no attribute, the CSS follows the system' => ['system', null];
        yield 'anything else is ignored' => ['dark" onload="alert(1)', null];
    }

    #[DataProvider('cookies')]
    public function testThemeComesFromTheCookie(string $cookie, ?string $theme): void
    {
        self::assertSame($theme, $this->extension(new Request(cookies: [ThemeExtension::COOKIE => $cookie]))->theme());
    }

    public function testNoCookieFollowsTheSystem(): void
    {
        self::assertNull($this->extension(new Request())->theme());
    }

    public function testNoRequestFollowsTheSystem(): void
    {
        self::assertNull($this->extension(null)->theme());
    }

    private function extension(?Request $request): ThemeExtension
    {
        $requestStack = new RequestStack();
        if (null !== $request) {
            $requestStack->push($request);
        }

        return new ThemeExtension($requestStack);
    }
}
