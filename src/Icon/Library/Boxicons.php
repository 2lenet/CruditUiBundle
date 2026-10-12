<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Icon\Library;

/**
 * Boxicons (`boxicons`): names of the catalogue that differ in this library, null for an icon it
 * does not have (ui_icon() then renders nothing and logs a warning).
 */
final class Boxicons
{
    public const array EXCEPTIONS = [
        'save' => 'save',
        'refresh' => 'refresh',
        'close' => 'x',
        'search' => 'search',
        'filter-clear' => null,
        'bookmark-active' => 'bxs-bookmark',
        'settings' => 'slider-alt',
        'more' => 'dots-vertical',
        'drag' => 'grid-vertical',
        'print' => 'printer',
        'upload' => 'upload',
        'external-link' => 'link-external',
        'menu' => 'menu',
        'sidebar-toggle' => 'chevrons-left',
        'first' => 'skip-previous',
        'back' => 'arrow-back',
        'next' => 'right-arrow',
        'last' => 'skip-next',
        'arrow-up' => 'up-arrow-alt',
        'arrow-down' => 'down-arrow-alt',
        'success' => 'check-circle',
        'info' => 'info-circle',
        'warning' => 'error-alt',
        'error' => 'error',
        'loading' => 'loader',
        'empty' => 'archive',
        'logout' => 'log-out',
        'impersonate' => 'user-check',
        'history-transition' => 'transfer',
        'time' => 'time',
        'calendar' => 'calendar',
        'file' => 'file',
        'file-csv' => null,
        'file-excel' => 'table',
        'file-pdf' => 'bxs-file-pdf',
        'quote' => 'bxs-quote-alt-right',
        'operator-equals' => null,
        'operator-not-equals' => null,
        'operator-less-than' => null,
        'operator-less-or-equal' => null,
        'operator-greater-than' => null,
        'operator-greater-or-equal' => null,
        'operator-between' => 'expand-horizontal',
        'operator-contains' => 'text',
        'operator-starts-with' => 'align-left',
        'operator-ends-with' => 'align-right',
        'operator-not-empty' => 'bxs-square',
        'trend-up' => 'trending-up',
        'trend-down' => 'trending-down',
    ];
}
