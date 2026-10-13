<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Icon;

/**
 * The Iconify name of a logical name is built from the prefix of the library and the base name of the catalogue
 * (`fa7-solid:trash`, `lucide:trash`), plus the suffix of the library (`octicon:trash-24`). The exceptions of each
 * library (Library\*) list the names that differ, including the Font Awesome regular icons
 * (`fa7-regular:bookmark`), and the icons the library does not have (null).
 */
enum IconLibrary: string
{
    case FontAwesome = 'fontawesome';
    case BootstrapIcons = 'bootstrap-icons';
    case Boxicons = 'boxicons';
    case Flowbite = 'flowbite';
    case Heroicons = 'heroicons';
    case Iconoir = 'iconoir';
    case Ionicons = 'ionicons';
    case Lucide = 'lucide';
    case MaterialDesignIcons = 'mdi';
    case Octicons = 'octicons';
    case Phosphor = 'phosphor';
    case Tabler = 'tabler';

    public function prefix(): string
    {
        return match ($this) {
            self::FontAwesome => 'fa7-solid',
            self::BootstrapIcons => 'bi',
            self::Boxicons => 'bx',
            self::Flowbite => 'flowbite',
            self::Heroicons => 'heroicons',
            self::Iconoir => 'iconoir',
            self::Ionicons => 'ion',
            self::Lucide => 'lucide',
            self::MaterialDesignIcons => 'mdi',
            self::Octicons => 'octicon',
            self::Phosphor => 'ph',
            self::Tabler => 'tabler',
        };
    }

    public function suffix(): string
    {
        return match ($this) {
            self::Flowbite, self::Ionicons => '-outline',
            self::Octicons => '-24',
            default => '',
        };
    }

    /**
     * @return array<string, string|null> names of the catalogue that differ in this library, null when it does not
     *                                    have the icon
     */
    public function exceptions(): array
    {
        return match ($this) {
            self::FontAwesome => Library\FontAwesome::EXCEPTIONS,
            self::BootstrapIcons => Library\BootstrapIcons::EXCEPTIONS,
            self::Boxicons => Library\Boxicons::EXCEPTIONS,
            self::Flowbite => Library\Flowbite::EXCEPTIONS,
            self::Heroicons => Library\Heroicons::EXCEPTIONS,
            self::Iconoir => Library\Iconoir::EXCEPTIONS,
            self::Ionicons => Library\Ionicons::EXCEPTIONS,
            self::Lucide => Library\Lucide::EXCEPTIONS,
            self::MaterialDesignIcons => Library\MaterialDesignIcons::EXCEPTIONS,
            self::Octicons => Library\Octicons::EXCEPTIONS,
            self::Phosphor => Library\Phosphor::EXCEPTIONS,
            self::Tabler => Library\Tabler::EXCEPTIONS,
        };
    }

    /**
     * Iconify name of a logical name of the catalogue, null for a name that is not in it or an icon the library
     * does not have.
     */
    public function icon(string $name): ?string
    {
        if (!isset(IconCatalogue::ICONS[$name])) {
            return null;
        }

        $exceptions = $this->exceptions();
        $icon = \array_key_exists($name, $exceptions) ? $exceptions[$name] : IconCatalogue::ICONS[$name] . $this->suffix();
        if (null === $icon) {
            return null;
        }

        return str_contains($icon, ':') ? $icon : $this->prefix() . ':' . $icon;
    }

    /**
     * @return array<string, string|null> Iconify name of each logical name of the catalogue, null for an icon the
     *                                    library does not have
     */
    public function icons(): array
    {
        $icons = [];
        foreach (array_keys(IconCatalogue::ICONS) as $name) {
            $icons[$name] = $this->icon($name);
        }

        return $icons;
    }
}
