<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Icon\Library;

/**
 * Heroicons (`heroicons`): names of the catalogue that differ in this library, null for an icon it
 * does not have (ui_icon() then renders nothing and logs a warning).
 */
final class Heroicons
{
    public const array EXCEPTIONS = [
        'edit' => 'pencil',
        'save' => null,
        'undo' => 'arrow-uturn-left',
        'refresh' => 'arrow-path',
        'close' => 'x-mark',
        'filter' => 'funnel',
        'filter-clear' => null,
        'bookmark-active' => 'bookmark-solid',
        'settings' => 'adjustments-horizontal',
        'drag' => null,
        'print' => 'printer',
        'copy' => 'clipboard',
        'download' => 'arrow-down-tray',
        'send' => 'paper-airplane',
        'external-link' => 'arrow-top-right-on-square',
        'menu' => 'bars-3',
        'sidebar-toggle' => 'chevron-double-left',
        'first' => 'backward',
        'last' => 'forward',
        'sort' => 'arrows-up-down',
        'sort-asc' => 'bars-arrow-up',
        'sort-desc' => 'bars-arrow-down',
        'success' => 'check-circle',
        'info' => 'information-circle',
        'warning' => 'exclamation-triangle',
        'error' => 'exclamation-circle',
        'loading' => 'arrow-path',
        'locked' => 'lock-closed',
        'logout' => 'arrow-right-on-rectangle',
        'impersonate' => null,
        'language' => 'language',
        'theme-system' => 'computer-desktop',
        'history-transition' => 'arrows-right-left',
        'repeat' => 'arrow-path-rounded-square',
        'file' => 'document-text',
        'file-csv' => null,
        'file-excel' => null,
        'file-pdf' => null,
        'bulleted-list' => 'list-bullet',
        'numbered-list' => 'numbered-list',
        'quote' => null,
        'operator-not-equals' => null,
        'operator-less-than' => null,
        'operator-less-or-equal' => null,
        'operator-greater-than' => null,
        'operator-greater-or-equal' => null,
        'operator-between' => null,
        'operator-contains' => null,
        'operator-starts-with' => null,
        'operator-ends-with' => null,
        'operator-empty' => null,
        'operator-not-empty' => 'stop-solid',
        'trend-up' => 'arrow-trending-up',
        'trend-down' => 'arrow-trending-down',
        'table' => 'table-cells',
    ];
}
