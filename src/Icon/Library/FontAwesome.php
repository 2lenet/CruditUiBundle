<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Icon\Library;

/**
 * Font Awesome 7 (`fontawesome`): names of the catalogue that differ in this library, null for an icon it
 * does not have (ui_icon() then renders nothing and logs a warning).
 */
final class FontAwesome
{
    public const array EXCEPTIONS = [
        'bookmark' => 'fa7-regular:bookmark',
        'time' => 'fa7-regular:clock',
        'operator-starts-with' => 'fa7-regular:square-caret-right',
        'operator-ends-with' => 'fa7-regular:square-caret-left',
        'operator-empty' => 'fa7-regular:square',
    ];
}
