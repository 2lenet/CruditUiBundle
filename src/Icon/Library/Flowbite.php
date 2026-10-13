<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Icon\Library;

/**
 * Flowbite (`flowbite`): names of the catalogue that differ in this library, null for an icon it
 * does not have (ui_icon() then renders nothing and logs a warning).
 */
final class Flowbite
{
    public const array EXCEPTIONS = [
        'delete' => 'trash-bin-outline',
        'undo' => 'undo-outline',
        'refresh' => 'refresh-outline',
        'close' => 'close-outline',
        'search' => 'search-outline',
        'filter-clear' => null,
        'bookmark-active' => 'bookmark-solid',
        'settings' => 'adjustments-horizontal-outline',
        'more' => 'dots-vertical-outline',
        'drag' => null,
        'print' => 'printer-outline',
        'copy' => 'clipboard-outline',
        'sidebar-toggle' => 'chevron-double-left-outline',
        'sort-asc' => 'caret-up-outline',
        'sort-desc' => 'caret-down-outline',
        'success' => 'check-circle-solid',
        'info' => 'info-circle-outline',
        'warning' => null,
        'error' => 'exclamation-circle-outline',
        'loading' => 'refresh-outline',
        'logout' => null,
        'impersonate' => null,
        'theme-system' => 'desktop-pc-outline',
        'history-transition' => 'arrow-right-arrow-left-solid',
        'calendar' => 'calendar-month-outline',
        'repeat' => null,
        'file-excel' => null,
        'bold' => null,
        'italic' => null,
        'bulleted-list' => 'list-outline',
        'numbered-list' => null,
        'quote' => 'quote-outline',
        'operator-equals' => null,
        'operator-not-equals' => null,
        'operator-less-than' => null,
        'operator-less-or-equal' => null,
        'operator-greater-than' => null,
        'operator-greater-or-equal' => null,
        'operator-between' => null,
        'operator-contains' => 'text-size-outline',
        'operator-starts-with' => 'align-left-outline',
        'operator-ends-with' => 'align-right-outline',
        'operator-empty' => null,
        'operator-not-empty' => 'stop-solid',
        'trend-up' => 'chart-line-up-outline',
        'trend-down' => 'chart-line-down-outline',
        'table' => 'grid-outline',
    ];
}
