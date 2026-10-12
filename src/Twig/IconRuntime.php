<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Twig;

use Lle\CruditUiBundle\Icon\IconCatalogue;
use Lle\CruditUiBundle\Icon\IconLibrary;
use Symfony\UX\Icons\IconRendererInterface;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * Renders an icon through Symfony UX Icons, from a logical name of the catalogue (`delete`), translated into the
 * library of the project, or from an Iconify name passed by a project (`lucide:truck`), after the replacements
 * of `lle_crudit_ui.icons.map`.
 */
class IconRuntime implements RuntimeExtensionInterface
{
    /**
     * @param array<string, string> $map replacements, keyed by logical name or by Iconify name
     */
    public function __construct(
        protected IconRendererInterface $iconRenderer,
        protected IconLibrary $library,
        protected array $map,
    ) {
    }

    public function icon(string $name, string $class = ''): string
    {
        $classes = ['crudit-ui-icon'];
        if (\in_array($name, IconCatalogue::MIRRORED, true)) {
            $classes[] = 'crudit-ui-icon--mirror';
        }
        if (\in_array($name, IconCatalogue::SPINNING, true)) {
            $classes[] = 'crudit-ui-icon--spin';
        }
        if ('' !== $class) {
            $classes[] = $class;
        }

        return $this->iconRenderer->renderIcon($this->resolve($name), [
            'class' => implode(' ', $classes),
            'aria-hidden' => 'true',
        ]);
    }

    public function resolve(string $name): string
    {
        if (isset($this->map[$name])) {
            return $this->map[$name];
        }

        $icon = $this->library->icon($name);
        if (null !== $icon) {
            return $icon;
        }

        if (str_contains($name, ':')) {
            return $name;
        }

        throw new \InvalidArgumentException(sprintf(
            'Unknown icon "%s": use a logical name of the catalogue (docs/icons.md) or an Iconify name ("lucide:truck").',
            $name,
        ));
    }
}
