<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Component;

/**
 * The variants of Button (and of the trigger of Dropdown): the one list, for the bundles that check a variant before
 * giving it to the component (the components do not validate their props). The colours are in the tokens, not here.
 * A new variant: a case, its rule `&--<variant>` in components/_button.scss and its name in the comment of Button.
 */
enum ButtonVariant: string
{
    case Primary = 'primary';
    case Secondary = 'secondary';
    case Ghost = 'ghost';
    case Warning = 'warning';
    case Danger = 'danger';
    case DangerOutline = 'danger-outline';
    case Link = 'link';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $variant): string => $variant->value, self::cases());
    }
}
