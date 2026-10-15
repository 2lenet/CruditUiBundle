<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Tests\Unit;

use Lle\CruditUiBundle\Locale\RequestLocale;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class RequestLocaleTest extends TestCase
{
    public function testLocaleOfTheRequest(): void
    {
        $request = new Request();
        $request->setLocale('ar_MA');
        $requestStack = new RequestStack();
        $requestStack->push($request);

        self::assertSame('ar_MA', (new RequestLocale($requestStack, 'fr'))->get());
    }

    public function testNoRequestUsesTheDefaultLocale(): void
    {
        self::assertSame('fr', (new RequestLocale(new RequestStack(), 'fr'))->get());
    }
}
