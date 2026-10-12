<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Twig;

use Lle\CruditUiBundle\Icon\IconCatalogue;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * Renders an icon from a logical name of the catalogue (`delete`) or from a Font Awesome class passed by a
 * project (`fa-solid fa-truck`), after the replacements of `lle_crudit_ui.icons.map`.
 */
class IconRuntime implements RuntimeExtensionInterface
{
    /**
     * @param array<string, string> $map replacements, keyed by the Font Awesome class
     */
    public function __construct(private readonly array $map)
    {
    }

    public function icon(string $name, string $class = ''): string
    {
        $iconClass = $this->resolve($name);
        $iconClass = $this->map[$iconClass] ?? $iconClass;

        return sprintf(
            '<i class="%s" aria-hidden="true"></i>',
            htmlspecialchars(trim($iconClass . ' ' . $class), \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8'),
        );
    }

    private function resolve(string $name): string
    {
        if (isset(IconCatalogue::ICONS[$name])) {
            return IconCatalogue::ICONS[$name];
        }

        if (str_starts_with($name, 'fa')) {
            return $name;
        }

        throw new \InvalidArgumentException(sprintf(
            'Unknown icon "%s": use a logical name of the catalogue (docs/icons.md) or a Font Awesome class.',
            $name,
        ));
    }
}
