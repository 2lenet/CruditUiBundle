<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Icon\Library;

/**
 * Bootstrap Icons (`bootstrap-icons`): names of the catalogue that differ in this library, null for an icon it
 * does not have (ui_icon() then renders nothing and logs a warning).
 */
final class BootstrapIcons
{
    public const array EXCEPTIONS = [
        'remove' => 'dash',
        'save' => 'floppy',
        'undo' => 'arrow-counterclockwise',
        'refresh' => 'arrow-clockwise',
        'close' => 'x',
        'search' => 'search',
        'filter-clear' => null,
        'bookmark-active' => 'bookmark-fill',
        'more' => 'three-dots-vertical',
        'print' => 'printer',
        'send' => 'send',
        'external-link' => 'box-arrow-up-right',
        'menu' => 'list',
        'sidebar-toggle' => 'chevron-double-left',
        'first' => 'skip-start',
        'last' => 'skip-forward',
        'sort' => 'arrow-up-down',
        'success' => 'check-circle',
        'info' => 'info-circle',
        'warning' => 'exclamation-triangle',
        'error' => 'exclamation-circle',
        'loading' => 'arrow-repeat',
        'user' => 'person',
        'logout' => 'box-arrow-right',
        'impersonate' => 'incognito',
        'theme-system' => 'display',
        'history-transition' => 'arrow-left-right',
        'calendar' => 'calendar',
        'file' => 'file-text',
        'file-csv' => 'filetype-csv',
        'bold' => 'type-bold',
        'italic' => 'type-italic',
        'quote' => 'quote',
        'operator-equals' => null,
        'operator-not-equals' => null,
        'operator-less-than' => null,
        'operator-less-or-equal' => null,
        'operator-greater-than' => null,
        'operator-greater-or-equal' => null,
        'operator-between' => 'arrow-left-right',
        'operator-contains' => 'type',
        'operator-starts-with' => null,
        'operator-ends-with' => null,
        'operator-not-empty' => 'square-fill',
        'trend-up' => 'graph-up-arrow',
        'trend-down' => 'graph-down-arrow',
    ];
}
