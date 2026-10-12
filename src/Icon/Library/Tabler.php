<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Icon\Library;

/**
 * Tabler Icons (`tabler`): names of the catalogue that differ in this library, null for an icon it
 * does not have (ui_icon() then renders nothing and logs a warning).
 */
final class Tabler
{
    public const array EXCEPTIONS = [
        'edit' => 'pencil',
        'save' => 'device-floppy',
        'undo' => 'arrow-back-up',
        'close' => 'x',
        'search' => 'search',
        'filter-clear' => 'filter-x',
        'bookmark-active' => 'bookmark-filled',
        'settings' => 'adjustments',
        'more' => 'dots-vertical',
        'print' => 'printer',
        'upload' => 'upload',
        'send' => 'send',
        'external-link' => 'external-link',
        'menu' => 'menu',
        'sidebar-toggle' => 'chevrons-left',
        'first' => 'skip-back',
        'last' => 'skip-forward',
        'sort' => 'arrows-up-down',
        'sort-asc' => 'sort-ascending',
        'sort-desc' => 'sort-descending',
        'info' => 'info-circle',
        'warning' => 'alert-triangle',
        'error' => 'alert-circle',
        'loading' => 'loader',
        'logout' => 'logout',
        'impersonate' => 'spy',
        'theme-system' => 'device-desktop',
        'email' => 'mail',
        'history-transition' => 'arrows-left-right',
        'calendar' => 'calendar',
        'file' => 'file-text',
        'file-csv' => 'file-type-csv',
        'file-pdf' => 'file-type-pdf',
        'bulleted-list' => 'list',
        'numbered-list' => 'list-numbers',
        'quote' => 'quote',
        'operator-equals' => 'equal',
        'operator-not-equals' => 'equal-not',
        'operator-less-than' => 'math-lower',
        'operator-less-or-equal' => 'math-equal-lower',
        'operator-greater-than' => 'math-greater',
        'operator-greater-or-equal' => 'math-equal-greater',
        'operator-contains' => 'text-size',
        'operator-starts-with' => 'align-left',
        'operator-ends-with' => 'align-right',
        'operator-not-empty' => 'square-filled',
        'trend-up' => 'trending-up',
        'trend-down' => 'trending-down',
    ];
}
