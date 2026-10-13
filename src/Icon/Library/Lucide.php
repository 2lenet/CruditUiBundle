<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Icon\Library;

/**
 * Lucide (`lucide`): names of the catalogue that differ in this library, null for an icon it
 * does not have (ui_icon() then renders nothing and logs a warning).
 */
final class Lucide
{
    public const array EXCEPTIONS = [
        'save' => 'save',
        'undo' => 'undo',
        'refresh' => 'refresh-cw',
        'close' => 'x',
        'search' => 'search',
        'filter-clear' => 'filter-x',
        'bookmark-active' => 'bookmark-check',
        'settings' => 'sliders-vertical',
        'print' => 'printer',
        'upload' => 'upload',
        'send' => 'send',
        'external-link' => 'square-arrow-out-up-right',
        'menu' => 'menu',
        'sidebar-toggle' => 'chevrons-left',
        'first' => 'skip-back',
        'last' => 'skip-forward',
        'sort' => 'arrow-up-down',
        'sort-asc' => 'arrow-up-narrow-wide',
        'sort-desc' => 'arrow-down-wide-narrow',
        'info' => 'info',
        'warning' => 'triangle-alert',
        'error' => 'circle-alert',
        'loading' => 'loader',
        'logout' => 'log-out',
        'impersonate' => 'venetian-mask',
        'theme-system' => 'monitor',
        'email' => 'mail',
        'history-transition' => 'arrow-left-right',
        'file' => 'file-text',
        'file-csv' => 'file-spreadsheet',
        'file-excel' => 'file-spreadsheet',
        'file-pdf' => null,
        'bulleted-list' => 'list',
        'numbered-list' => 'list-ordered',
        'quote' => 'quote',
        'operator-equals' => 'equal',
        'operator-not-equals' => 'equal-not',
        'operator-less-than' => null,
        'operator-less-or-equal' => null,
        'operator-greater-than' => null,
        'operator-greater-or-equal' => null,
        'operator-between' => 'arrow-left-right',
        'operator-contains' => 'text-search',
        'operator-starts-with' => 'align-left',
        'operator-ends-with' => 'align-right',
        'operator-not-empty' => 'square-dot',
        'trend-up' => 'trending-up',
        'trend-down' => 'trending-down',
    ];
}
