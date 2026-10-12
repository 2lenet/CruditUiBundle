<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Tests\Unit;

use Lle\CruditUiBundle\Icon\IconLibrary;
use Lle\CruditUiBundle\Twig\IconRuntime;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Icons\IconRendererInterface;

class IconRuntimeTest extends TestCase
{
    public function testLogicalNameInTheDefaultLibrary(): void
    {
        self::assertSame('fa7-solid:trash class="crudit-ui-icon"', $this->runtime()->icon('delete'));
    }

    public function testLogicalNameInTheLibraryOfTheProject(): void
    {
        self::assertSame('lucide:trash class="crudit-ui-icon"', $this->runtime(IconLibrary::Lucide)->icon('delete'));
    }

    public function testIconifyNamePassedByTheProject(): void
    {
        self::assertSame('lucide:truck class="crudit-ui-icon"', $this->runtime()->icon('lucide:truck'));
    }

    public function testMapReplacesALogicalName(): void
    {
        $runtime = $this->runtime(IconLibrary::FontAwesome, ['delete' => 'tabler:trash-x']);

        self::assertSame('tabler:trash-x class="crudit-ui-icon"', $runtime->icon('delete'));
    }

    public function testMapReplacesAnIconifyNamePassedByTheProject(): void
    {
        $runtime = $this->runtime(IconLibrary::FontAwesome, ['lucide:truck' => 'tabler:truck']);

        self::assertSame('tabler:truck class="crudit-ui-icon"', $runtime->icon('lucide:truck'));
    }

    public function testDirectionalIconIsMirrored(): void
    {
        self::assertSame('fa7-solid:arrow-right class="crudit-ui-icon crudit-ui-icon--mirror"', $this->runtime()->icon('next'));
    }

    public function testLoadingIconSpins(): void
    {
        self::assertSame('fa7-solid:spinner class="crudit-ui-icon crudit-ui-icon--spin"', $this->runtime()->icon('loading'));
    }

    public function testAdditionalClass(): void
    {
        self::assertSame('fa7-solid:pen class="crudit-ui-icon me-1"', $this->runtime()->icon('edit', 'me-1'));
    }

    public function testUnknownName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown icon "trash"');

        $this->runtime()->icon('trash');
    }

    /**
     * @param array<string, string> $map
     */
    private function runtime(IconLibrary $library = IconLibrary::FontAwesome, array $map = []): IconRuntime
    {
        $renderer = new class implements IconRendererInterface {
            public function renderIcon(string $name, array $attributes = []): string
            {
                \assert('true' === $attributes['aria-hidden']);

                return sprintf('%s class="%s"', $name, (string) $attributes['class']);
            }
        };

        return new IconRuntime($renderer, $library, $map);
    }
}
