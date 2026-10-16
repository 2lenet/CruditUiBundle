<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Tests\Unit;

use Lle\CruditUiBundle\Icon\IconCatalogue;
use Lle\CruditUiBundle\Icon\IconFinder;
use Lle\CruditUiBundle\Icon\IconLibrary;
use PHPUnit\Framework\TestCase;

class IconFinderTest extends TestCase
{
    public function testEveryIconOfTheLibrary(): void
    {
        $icons = (new IconFinder(IconLibrary::FontAwesome, []))->icons();

        self::assertContains('fa7-solid:trash', $icons);
        self::assertContains('fa7-regular:bookmark', $icons);
        // one drawing can have several logical names
        self::assertLessThanOrEqual(\count(IconCatalogue::ICONS), \count($icons));
        self::assertSame(array_values(array_unique($icons)), $icons);
    }

    public function testIconsTheLibraryDoesNotHaveAreLeftOut(): void
    {
        $library = IconLibrary::Lucide;
        $missing = array_keys(array_filter($library->icons(), static fn (?string $icon): bool => null === $icon));
        self::assertNotSame([], $missing, 'Lucide has icons missing from the catalogue');

        $icons = (new IconFinder($library, []))->icons();

        self::assertNotContains(null, $icons);
        self::assertContains('lucide:trash', $icons);
    }

    public function testReplacementsOfTheMap(): void
    {
        $icons = (new IconFinder(IconLibrary::FontAwesome, [
            'delete' => 'tabler:trash-x',
            'lucide:truck' => 'mdi:truck',
            'edit' => 'fa7-solid:trash',
        ]))->icons();

        self::assertContains('tabler:trash-x', $icons);
        self::assertContains('mdi:truck', $icons);
        // delete is replaced, edit too, by an icon of the library
        self::assertNotContains('fa7-solid:pen', $icons);
        self::assertContains('fa7-solid:trash', $icons);
        self::assertSame(array_values(array_unique($icons)), $icons);
    }
}
