<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Tests\Unit;

use Lle\CruditUiBundle\Icon\IconCatalogue;
use Lle\CruditUiBundle\Icon\IconLibrary;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class IconLibraryTest extends TestCase
{
    /**
     * @return iterable<string, array{IconLibrary}>
     */
    public static function libraries(): iterable
    {
        foreach (IconLibrary::cases() as $library) {
            yield $library->value => [$library];
        }
    }

    #[DataProvider('libraries')]
    public function testLibraryCoversTheCatalogue(IconLibrary $library): void
    {
        self::assertSame(array_keys(IconCatalogue::ICONS), array_keys($library->icons()));
    }

    public function testIconOfANameOutsideTheCatalogue(): void
    {
        self::assertSame('lucide:trash', IconLibrary::Lucide->icon('delete'));
        self::assertNull(IconLibrary::Lucide->icon('trash'));
    }

    public function testIconTheLibraryDoesNotHave(): void
    {
        self::assertNull(IconLibrary::Octicons->icon('operator-equals'));
        self::assertSame('octicon:trash-24', IconLibrary::Octicons->icon('delete'));
    }

    #[DataProvider('libraries')]
    public function testExceptionsAreNamesOfTheCatalogue(IconLibrary $library): void
    {
        self::assertSame([], array_diff_key($library->exceptions(), IconCatalogue::ICONS));
    }

    #[DataProvider('libraries')]
    public function testIconsAreIconifyNames(IconLibrary $library): void
    {
        foreach (array_filter($library->icons(), static fn (?string $icon): bool => null !== $icon) as $name => $icon) {
            self::assertMatchesRegularExpression('/^[a-z0-9-]+:[a-z0-9-]+$/', $icon, $name);
        }
    }

    /**
     * A typo would only show up as a missing icon in a project, and a deprecated alias of Iconify may disappear
     * from a later version of the icon set. The icon sets come from the @iconify-json packages, devDependencies of
     * assets/ (`npm ci` in assets/).
     */
    #[DataProvider('libraries')]
    public function testIconsExistInIconify(IconLibrary $library): void
    {
        foreach (array_filter($library->icons(), static fn (?string $icon): bool => null !== $icon) as $name => $icon) {
            [$prefix, $iconName] = explode(':', $icon);
            self::assertContains($iconName, $this->iconSet($prefix), sprintf('%s: %s does not exist or is a deprecated alias', $name, $icon));
        }
    }

    /**
     * @return list<string> names of the icons, without the aliases
     */
    private function iconSet(string $prefix): array
    {
        $file = \dirname(__DIR__, 3) . '/assets/node_modules/@iconify-json/' . $prefix . '/icons.json';
        if (!is_file($file)) {
            self::markTestSkipped(sprintf('Icon set "%s" not installed: run `npm ci` in assets/.', $prefix));
        }

        /** @var array{icons: array<string, mixed>} $iconSet */
        $iconSet = json_decode((string) file_get_contents($file), true, flags: \JSON_THROW_ON_ERROR);

        return array_keys($iconSet['icons']);
    }
}
