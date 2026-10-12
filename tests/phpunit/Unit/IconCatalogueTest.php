<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Tests\Unit;

use Lle\CruditUiBundle\Icon\IconCatalogue;
use PHPUnit\Framework\TestCase;

class IconCatalogueTest extends TestCase
{
    /**
     * The catalogue and its documentation must not drift apart.
     */
    public function testCatalogueMatchesTheDocumentation(): void
    {
        $doc = (string) file_get_contents(\dirname(__DIR__, 3) . '/docs/icons.md');
        preg_match_all('/^\| <img[^|]*\| `([a-z0-9-]+)` \| `([^`]+)` \|/m', $doc, $matches);

        self::assertSame(IconCatalogue::ICONS, array_combine($matches[1], $matches[2]));
    }

    public function testOneClassHasOneName(): void
    {
        self::assertSame(array_values(IconCatalogue::ICONS), array_values(array_unique(IconCatalogue::ICONS)));
    }
}
