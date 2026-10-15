<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Tests\Unit;

use Lle\CruditUiBundle\Format\Formatter;
use Lle\CruditUiBundle\Locale\RequestLocale;
use Lle\CruditUiBundle\Twig\FormatExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

/**
 * The filters give the result of the Formatter service (tested in FormatterTest).
 */
class FormatExtensionTest extends TestCase
{
    public function testFiltersDelegateToTheFormatter(): void
    {
        $formatter = new Formatter(new RequestLocale(new RequestStack(), 'en'), new Environment(new ArrayLoader()));
        $extension = new FormatExtension($formatter);
        $date = new \DateTimeImmutable('2026-10-13 14:05', new \DateTimeZone('UTC'));

        self::assertSame($formatter->number(1234.5), $extension->number(1234.5));
        self::assertSame($formatter->currency(1234.5, 'EUR'), $extension->currency(1234.5, 'EUR'));
        self::assertSame($formatter->date($date, 'short', timezone: 'UTC'), $extension->date($date, 'short', timezone: 'UTC'));
        self::assertSame($formatter->datetime($date, 'short', 'short', timezone: 'UTC'), $extension->datetime($date, 'short', 'short', timezone: 'UTC'));
        self::assertSame($formatter->time($date, 'short', timezone: 'UTC'), $extension->time($date, 'short', timezone: 'UTC'));
    }
}
