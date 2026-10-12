<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Tests\Unit;

use Lle\CruditUiBundle\Twig\IconRuntime;
use PHPUnit\Framework\TestCase;

class IconRuntimeTest extends TestCase
{
    public function testLogicalName(): void
    {
        self::assertSame('<i class="fa-solid fa-trash" aria-hidden="true"></i>', (new IconRuntime([]))->icon('delete'));
    }

    public function testClassPassedByTheProject(): void
    {
        self::assertSame('<i class="fa-solid fa-truck" aria-hidden="true"></i>', (new IconRuntime([]))->icon('fa-solid fa-truck'));
    }

    public function testMapReplacesALogicalName(): void
    {
        $runtime = new IconRuntime(['fa-solid fa-trash' => 'xx xx-trash']);

        self::assertSame('<i class="xx xx-trash" aria-hidden="true"></i>', $runtime->icon('delete'));
    }

    public function testMapReplacesAClassPassedByTheProject(): void
    {
        $runtime = new IconRuntime(['fa fa-user' => 'fa-solid fa-user']);

        self::assertSame('<i class="fa-solid fa-user" aria-hidden="true"></i>', $runtime->icon('fa fa-user'));
    }

    public function testAdditionalClass(): void
    {
        self::assertSame('<i class="fa-solid fa-pen me-1" aria-hidden="true"></i>', (new IconRuntime([]))->icon('edit', 'me-1'));
    }

    public function testClassesAreEscaped(): void
    {
        self::assertSame('<i class="fa-solid fa-pen &quot;&gt;" aria-hidden="true"></i>', (new IconRuntime([]))->icon('edit', '">'));
    }

    public function testUnknownName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown icon "trash"');

        (new IconRuntime([]))->icon('trash');
    }
}
