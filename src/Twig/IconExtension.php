<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Twig;

use Lle\CruditUiBundle\Icon\IconCatalogue;
use Lle\CruditUiBundle\Icon\IconLibrary;
use Psr\Log\LoggerInterface;
use Symfony\UX\Icons\Exception\IconNotFoundException;
use Symfony\UX\Icons\IconRendererInterface;
use Twig\Attribute\AsTwigFunction;

/**
 * Renders an icon through Symfony UX Icons, from a logical name of the catalogue (`delete`), translated into the
 * library of the project, or from an Iconify name passed by a project (`lucide:truck`), after the replacements
 * of `lle_crudit_ui.icons.map`. An icon that cannot be rendered is logged and left out, the page is still
 * displayed.
 */
class IconExtension
{
    /**
     * @param array<string, string> $map replacements, keyed by logical name or by Iconify name
     */
    public function __construct(
        protected IconRendererInterface $iconRenderer,
        protected IconLibrary $library,
        protected array $map,
        protected LoggerInterface $logger,
    ) {
    }

    #[AsTwigFunction('ui_icon', isSafe: ['html'])]
    public function icon(string $name, string $class = ''): string
    {
        $icon = $this->resolve($name);
        if (null === $icon) {
            return '';
        }

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

        try {
            return $this->iconRenderer->renderIcon($icon, [
                'class' => implode(' ', $classes),
                'aria-hidden' => 'true',
            ]);
        } catch (IconNotFoundException $exception) {
            $this->logger->warning('Icon "{icon}" not found.', ['icon' => $icon, 'exception' => $exception]);

            return '';
        }
    }

    /**
     * Iconify name of an icon, null (and a warning in the log) when it cannot be resolved.
     */
    public function resolve(string $name): ?string
    {
        if (isset($this->map[$name])) {
            return $this->map[$name];
        }

        if (isset(IconCatalogue::ICONS[$name])) {
            $icon = $this->library->icon($name);
            if (null === $icon) {
                $this->logger->warning('Icon "{name}" does not exist in the "{library}" library: replace it in lle_crudit_ui.icons.map.', [
                    'name' => $name,
                    'library' => $this->library->value,
                ]);
            }

            return $icon;
        }

        if (str_contains($name, ':')) {
            return $name;
        }

        $this->logger->warning('Unknown icon "{name}": use a logical name of the catalogue (docs/icons.md) or an Iconify name ("lucide:truck").', [
            'name' => $name,
        ]);

        return null;
    }
}
