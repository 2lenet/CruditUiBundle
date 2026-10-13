<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Icon\Library;

/**
 * Phosphor (`phosphor`): names of the catalogue that differ in this library, null for an icon it
 * does not have (ui_icon() then renders nothing and logs a warning).
 */
final class Phosphor
{
    public const array EXCEPTIONS = [
        'undo' => 'arrow-u-up-left',
        'refresh' => 'arrow-clockwise',
        'close' => 'x',
        'filter' => 'funnel',
        'filter-clear' => 'funnel-x',
        'bookmark-active' => 'bookmark-fill',
        'more' => 'dots-three-vertical',
        'drag' => 'dots-six-vertical',
        'print' => 'printer',
        'external-link' => 'arrow-square-out',
        'menu' => 'list',
        'dropdown' => 'caret-down',
        'forward' => 'caret-right',
        'sidebar-toggle' => 'caret-double-left',
        'first' => 'skip-back',
        'last' => 'skip-forward',
        'sort' => 'arrows-down-up',
        'sort-asc' => 'sort-ascending',
        'sort-desc' => 'sort-descending',
        'success' => 'check-circle',
        'info' => 'info',
        'warning' => 'warning',
        'error' => 'warning-circle',
        'empty' => 'tray',
        'logout' => 'sign-out',
        'impersonate' => 'detective',
        'history-transition' => 'arrows-left-right',
        'calendar' => 'calendar',
        'file' => 'file-text',
        'file-excel' => 'file-xls',
        'bold' => 'text-t-bold',
        'italic' => 'text-italic',
        'bulleted-list' => 'list',
        'numbered-list' => 'list-numbers',
        'quote' => 'quotes',
        'operator-not-equals' => 'not-equals',
        'operator-less-or-equal' => 'less-than-or-equal',
        'operator-greater-or-equal' => 'greater-than-or-equal',
        'operator-contains' => 'text-t',
        'operator-starts-with' => 'text-align-left',
        'operator-ends-with' => 'text-align-right',
        'operator-not-empty' => 'square-fill',
        'trend-up' => 'trend-up',
        'trend-down' => 'trend-down',
    ];
}
