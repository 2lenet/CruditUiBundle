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
        'delete' => 'delete',
        'save' => 'content-save',
        'refresh' => 'refresh',
        'close' => 'close',
        'search' => 'magnify',
        'filter-clear' => 'filter-remove',
        'bookmark' => 'bookmark-outline',
        'settings' => 'tune',
        'more' => 'dots-vertical',
        'drag' => 'drag',
        'print' => 'printer',
        'copy' => 'content-copy',
        'send' => 'send',
        'external-link' => 'open-in-new',
        'menu' => 'menu',
        'sidebar-toggle' => 'chevron-double-left',
        'first' => 'skip-previous',
        'last' => 'skip-forward',
        'sort-asc' => 'sort-ascending',
        'sort-desc' => 'sort-descending',
        'success' => 'check-circle',
        'info' => 'information',
        'warning' => 'alert',
        'error' => 'alert-circle',
        'loading' => 'loading',
        'user' => 'account',
        'logout' => 'logout',
        'impersonate' => 'incognito',
        'language' => 'earth',
        'theme-light' => 'white-balance-sunny',
        'theme-dark' => 'weather-night',
        'theme-system' => 'monitor',
        'email' => 'email',
        'history-transition' => 'arrow-left-right',
        'calendar' => 'calendar',
        'file' => 'file-document',
        'file-csv' => 'file-delimited',
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
