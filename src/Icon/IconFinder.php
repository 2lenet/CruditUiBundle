<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Icon;

use Symfony\UX\Icons\IconFinderInterface;

/**
 * Icons of the bundles for `ux:icons:lock` and the cache warmer of Symfony UX Icons: every icon of the catalogue in
 * the library of the project, except the ones replaced in `lle_crudit_ui.icons.map`, and the replacements of the
 * map. They are rendered through `ui_icon('delete')`, which the finder of the Twig templates of UX Icons does not see
 * (no `prefix:name` string).
 */
class IconFinder implements IconFinderInterface
{
    /**
     * @param array<string, string> $map replacements, keyed by logical name or by Iconify name
     */
    public function __construct(
        protected IconLibrary $library,
        protected array $map,
    ) {
    }

    /**
     * @return list<string>
     */
    public function icons(): array
    {
        // a logical name replaced in the map is no longer rendered with the icon of the library
        $libraryIcons = array_diff_key($this->library->icons(), $this->map);
        $icons = [
            ...array_filter($libraryIcons, static fn (?string $icon): bool => null !== $icon),
            ...array_values($this->map),
        ];

        return array_values(array_unique($icons));
    }
}
