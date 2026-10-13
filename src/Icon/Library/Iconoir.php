<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Icon\Library;

/**
 * Iconoir (`iconoir`): names of the catalogue that differ in this library, null for an icon it
 * does not have (ui_icon() then renders nothing and logs a warning).
 */
final class Iconoir
{
    public const array EXCEPTIONS = [
        'edit' => 'edit',
        'undo' => 'undo',
        'refresh' => 'refresh',
        'search' => 'search',
        'filter-clear' => null,
        'bookmark-active' => 'bookmark-solid',
        'settings' => 'settings',
        'more' => 'more-vert',
        'drag' => 'drag',
        'print' => 'printer',
        'upload' => 'upload',
        'send' => 'send',
        'external-link' => 'open-new-window',
        'menu' => 'menu',
        'dropdown' => 'nav-arrow-down',
        'forward' => 'nav-arrow-right',
        'sidebar-toggle' => 'fast-arrow-left',
        'first' => 'skip-prev',
        'last' => 'skip-next',
        'success' => 'check-circle',
        'info' => 'info-circle',
        'warning' => 'warning-triangle',
        'error' => 'warning-circle',
        'loading' => 'refresh',
        'empty' => 'archive',
        'logout' => 'log-out',
        'impersonate' => 'user-star',
        'theme-light' => 'sun-light',
        'theme-dark' => 'half-moon',
        'theme-system' => 'laptop',
        'email' => 'mail',
        'history-transition' => 'data-transfer-both',
        'calendar' => 'calendar',
        'file' => 'page',
        'file-csv' => null,
        'file-excel' => 'table',
        'file-pdf' => null,
        'bulleted-list' => 'list',
        'numbered-list' => 'numbered-list-left',
        'quote' => 'quote',
        'operator-equals' => null,
        'operator-not-equals' => null,
        'operator-less-than' => null,
        'operator-less-or-equal' => null,
        'operator-greater-than' => null,
        'operator-greater-or-equal' => null,
        'operator-between' => null,
        'operator-contains' => 'text',
        'operator-starts-with' => 'align-left',
        'operator-ends-with' => 'align-right',
        'operator-not-empty' => null,
        'trend-up' => 'graph-up',
        'trend-down' => 'graph-down',
    ];
}
