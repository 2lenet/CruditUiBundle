<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Tests\Unit;

use Lle\CruditUiBundle\Icon\IconLibrary;
use Lle\CruditUiBundle\Twig\IconExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ErrorHandler\BufferingLogger;
use Symfony\UX\Icons\Exception\IconNotFoundException;
use Symfony\UX\Icons\IconRendererInterface;

class IconExtensionTest extends TestCase
{
    private BufferingLogger $logger;

    protected function setUp(): void
    {
        $this->logger = new BufferingLogger();
    }

    public function testLogicalNameInTheDefaultLibrary(): void
    {
        self::assertSame('fa7-solid:trash class="crudit-ui-icon"', $this->extension()->icon('delete'));
    }

    public function testLogicalNameInTheLibraryOfTheProject(): void
    {
        self::assertSame('lucide:trash class="crudit-ui-icon"', $this->extension(IconLibrary::Lucide)->icon('delete'));
    }

    public function testIconifyNamePassedByTheProject(): void
    {
        self::assertSame('lucide:truck class="crudit-ui-icon"', $this->extension()->icon('lucide:truck'));
    }

    public function testMapReplacesALogicalName(): void
    {
        $extension = $this->extension(IconLibrary::FontAwesome, ['delete' => 'tabler:trash-x']);

        self::assertSame('tabler:trash-x class="crudit-ui-icon"', $extension->icon('delete'));
    }

    public function testMapReplacesAnIconifyNamePassedByTheProject(): void
    {
        $extension = $this->extension(IconLibrary::FontAwesome, ['lucide:truck' => 'tabler:truck']);

        self::assertSame('tabler:truck class="crudit-ui-icon"', $extension->icon('lucide:truck'));
    }

    public function testMapReplacesAnIconTheLibraryDoesNotHave(): void
    {
        $extension = $this->extension(IconLibrary::Octicons, ['operator-equals' => 'tabler:equal']);

        self::assertSame('tabler:equal class="crudit-ui-icon"', $extension->icon('operator-equals'));
        self::assertSame([], $this->warnings());
    }

    public function testDirectionalIconIsMirrored(): void
    {
        self::assertSame('fa7-solid:arrow-right class="crudit-ui-icon crudit-ui-icon--mirror"', $this->extension()->icon('next'));
    }

    public function testLoadingIconSpins(): void
    {
        self::assertSame('fa7-solid:spinner class="crudit-ui-icon crudit-ui-icon--spin"', $this->extension()->icon('loading'));
    }

    public function testAdditionalClass(): void
    {
        self::assertSame('fa7-solid:pen class="crudit-ui-icon me-1"', $this->extension()->icon('edit', 'me-1'));
    }

    public function testUnknownNameIsLoggedAndLeftOut(): void
    {
        self::assertSame('', $this->extension()->icon('trash'));
        self::assertSame(['Unknown icon "{name}": use a logical name of the catalogue (docs/icons.md) or an Iconify name ("lucide:truck").'], $this->warnings());
    }

    public function testIconTheLibraryDoesNotHaveIsLoggedAndLeftOut(): void
    {
        self::assertSame('', $this->extension(IconLibrary::Octicons)->icon('operator-equals'));
        self::assertSame(['Icon "{name}" does not exist in the "{library}" library: replace it in lle_crudit_ui.icons.map.'], $this->warnings());
    }

    public function testIconNotFoundByUxIconsIsLoggedAndLeftOut(): void
    {
        self::assertSame('', $this->extension()->icon('lucide:does-not-exist'));
        self::assertSame(['Icon "{icon}" not found.'], $this->warnings());
    }

    /**
     * @param array<string, string> $map
     */
    private function extension(IconLibrary $library = IconLibrary::FontAwesome, array $map = []): IconExtension
    {
        $renderer = new class implements IconRendererInterface {
            public function renderIcon(string $name, array $attributes = []): string
            {
                if (str_ends_with($name, 'does-not-exist')) {
                    throw new IconNotFoundException(sprintf('Icon "%s" not found.', $name));
                }
                \assert('true' === $attributes['aria-hidden']);

                return sprintf('%s class="%s"', $name, (string) $attributes['class']);
            }
        };
        return new IconExtension($renderer, $library, $map, $this->logger);
    }

    /**
     * @return list<string>
     */
    private function warnings(): array
    {
        return array_values(array_map(static fn (array $log): string => (string) $log[1], $this->logger->cleanLogs()));
    }
}
