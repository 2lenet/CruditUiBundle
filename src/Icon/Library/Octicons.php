<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Icon\Library;

/**
 * Octicons (`octicons`): names of the catalogue that differ in this library, null for an icon it
 * does not have (ui_icon() then renders nothing and logs a warning).
 */
final class Octicons
{
    public const array EXCEPTIONS = [
        'edit' => 'pencil-24',
        'remove' => 'dash-24',
        'save' => null,
        'undo' => 'undo-24',
        'refresh' => 'sync-24',
        'close' => 'x-24',
        'search' => 'search-24',
        'filter-clear' => 'filter-remove-24',
        'bookmark-active' => 'bookmark-fill-24',
        'more' => 'kebab-horizontal-24',
        'drag' => null,
        'print' => null,
        'upload' => 'upload-24',
        'send' => 'paper-airplane-24',
        'external-link' => 'link-external-24',
        'menu' => 'three-bars-24',
        'sidebar-toggle' => null,
        'first' => null,
        'last' => null,
        'sort' => null,
        'sort-asc' => 'sort-asc-24',
        'sort-desc' => 'sort-desc-24',
        'success' => 'check-circle-24',
        'info' => 'info-24',
        'warning' => 'alert-24',
        'error' => null,
        'loading' => null,
        'user' => 'person-24',
        'logout' => 'sign-out-24',
        'impersonate' => null,
        'theme-system' => 'device-desktop-24',
        'email' => 'mail-24',
        'history-create' => null,
        'history-transition' => null,
        'calendar' => 'calendar-24',
        'repeat' => null,
        'file' => 'file-24',
        'file-csv' => null,
        'file-excel' => 'table-24',
        'file-pdf' => 'file-pdf',
        'bulleted-list' => 'list-unordered-24',
        'numbered-list' => 'list-ordered-24',
        'quote' => 'quote-24',
        'operator-equals' => null,
        'operator-not-equals' => null,
        'operator-less-than' => null,
        'operator-less-or-equal' => null,
        'operator-greater-than' => null,
        'operator-greater-or-equal' => null,
        'operator-between' => null,
        'operator-contains' => 'text-size',
        'operator-starts-with' => null,
        'operator-ends-with' => null,
        'operator-not-empty' => 'square-fill-24',
        'trend-up' => null,
        'trend-down' => null,
    ];
}
