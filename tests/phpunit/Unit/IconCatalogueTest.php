<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Tests\Unit;

use Lle\CruditUiBundle\Icon\IconCatalogue;
use Lle\CruditUiBundle\Icon\IconLibrary;
use PHPUnit\Framework\TestCase;

class IconCatalogueTest extends TestCase
{
    /**
     * The catalogue and its documentation (Font Awesome icons) must not drift apart.
     */
    public function testCatalogueMatchesTheDocumentation(): void
    {
        $rows = $this->documentationRows();

        self::assertSame(IconLibrary::FontAwesome->icons(), array_combine($rows[1], $rows[2]));
    }

    public function testMirroredIconsMatchTheRtlColumn(): void
    {
        $rows = $this->documentationRows();
        $mirrored = array_keys(array_filter(array_combine($rows[1], $rows[3]), static fn (string $rtl): bool => 'yes' === trim($rtl)));

        self::assertSame(IconCatalogue::MIRRORED, $mirrored);
    }

    public function testOneIconHasOneName(): void
    {
        $icons = IconLibrary::FontAwesome->icons();

        self::assertSame(array_values($icons), array_values(array_unique($icons)));
    }

    /**
     * @return array{list<string>, list<string>, list<string>, list<string>} matches, logical names, Iconify names and RTL column of docs/icons.md
     */
    private function documentationRows(): array
    {
        $doc = (string) file_get_contents(\dirname(__DIR__, 3) . '/docs/icons.md');
        preg_match_all('/^\| <img[^|]*\| `([a-z0-9-]+)` \| `([^`]+)` \|([^|]*)\|/m', $doc, $matches);

        return $matches;
    }
}
