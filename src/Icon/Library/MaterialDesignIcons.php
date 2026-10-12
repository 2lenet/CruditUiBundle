<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Icon\Library;

/**
 * Material Design Icons (`mdi`): names of the catalogue that differ in this library, null for an icon it
 * does not have (ui_icon() then renders nothing and logs a warning).
 */
final class MaterialDesignIcons
{
    public const array EXCEPTIONS = [
        'refresh' => 'refresh',
        'close' => 'close',
        'search' => 'search',
        'filter-clear' => 'filter-remove',
        'bookmark' => 'bookmark-outline',
        'settings' => 'tune',
        'drag' => 'drag',
        'print' => 'printer',
        'copy' => 'content-copy',
        'external-link' => 'external-link',
        'menu' => 'menu',
        'sidebar-toggle' => 'chevron-double-left',
        'first' => 'skip-previous',
        'last' => 'skip-forward',
        'sort-asc' => 'sort-ascending',
        'sort-desc' => 'sort-descending',
        'success' => 'check-circle',
        'info' => 'info-circle',
        'warning' => 'warning',
        'error' => 'alert-circle',
        'loading' => 'loading',
        'logout' => 'logout',
        'impersonate' => 'incognito',
        'theme-light' => 'white-balance-sunny',
        'theme-dark' => 'weather-night',
        'theme-system' => 'monitor',
        'history-transition' => 'arrow-left-right',
        'calendar' => 'calendar',
        'file' => 'file-text',
        'bold' => 'format-bold',
        'italic' => 'format-italic',
        'bulleted-list' => 'format-list-bulleted',
        'numbered-list' => 'format-list-numbered',
        'quote' => 'format-quote-close',
        'operator-equals' => 'equal',
        'operator-less-or-equal' => 'less-than-or-equal',
        'operator-greater-or-equal' => 'greater-than-or-equal',
        'operator-between' => 'arrow-left-right',
        'operator-contains' => 'text-search',
        'operator-starts-with' => 'format-align-left',
        'operator-ends-with' => 'format-align-right',
        'operator-empty' => 'square-outline',
        'trend-up' => 'trending-up',
        'trend-down' => 'trending-down',
    ];
}
