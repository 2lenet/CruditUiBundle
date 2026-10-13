# Icon libraries

The project chooses its icon library in `lle_crudit_ui.icons.library` (see [Configuration](configuration.md#icons)).
`ui_icon()` builds the Iconify name of each logical name of the [catalogue](icons.md) from the prefix of the library
and the base name of the catalogue, which is the Font Awesome name (`fa7-solid:trash`, `lucide:trash`,
`octicon:trash-24`), except for the names that differ, listed in one file per library (`src/Icon/Library/`); with
Font Awesome, the regular icons such as `fa7-regular:bookmark`.
[Symfony UX Icons](https://symfony.com/bundles/ux-icons/current/index.html) renders it as an inline SVG. The
libraries are the icon sets listed by the Symfony UX Icons documentation, Font Awesome in version 7.

A library does not always have an icon for every logical name (mathematical operators, file types…), marked `—`
below: `ui_icon()` then renders nothing and logs a warning. Give it an icon with `lle_crudit_ui.icons.map`, from
any icon set:

```yaml
lle_crudit_ui:
    icons:
        library: octicons
        map:
            operator-equals: 'tabler:equal'
```

`IconLibraryTest` checks that every name exists in its Iconify set, and is not a deprecated alias (`sort-asc` in Lucide
is now `arrow-up-narrow-wide`): an alias may disappear from a later version of the set.

| `library` | Library | Iconify prefix | Licence | Missing icons |
|---|---|---|---|---|
| `fontawesome` | [Font Awesome 7 (default)](https://fontawesome.com) | `fa7-solid`, `fa7-regular` | CC BY 4.0 | 0 |
| `bootstrap-icons` | [Bootstrap Icons](https://icons.getbootstrap.com) | `bi` | MIT | 9 |
| `boxicons` | [Boxicons](https://boxicons.com) | `bx` | MIT | 8 |
| `flowbite` | [Flowbite](https://flowbite.com/icons/) | `flowbite` | MIT | 18 |
| `heroicons` | [Heroicons](https://heroicons.com) | `heroicons` | MIT | 18 |
| `iconoir` | [Iconoir](https://iconoir.com) | `iconoir` | MIT | 11 |
| `ionicons` | [Ionicons](https://ionic.io/ionicons) | `ion` | MIT | 18 |
| `lucide` | [Lucide](https://lucide.dev) | `lucide` | ISC | 5 |
| `mdi` | [Material Design Icons](https://pictogrammers.com/library/mdi/) | `mdi` | Apache 2.0 | 0 |
| `octicons` | [Octicons](https://primer.style/octicons) | `octicon` | MIT | 23 |
| `phosphor` | [Phosphor](https://phosphoricons.com) | `ph` | MIT | 0 |
| `tabler` | [Tabler Icons](https://tabler.io/icons) | `tabler` | MIT | 0 |

## Tables

| Name | `bootstrap-icons` | `boxicons` | `flowbite` | `heroicons` |
|---|---|---|---|---|
| `add` | `plus` | `plus` | `plus-outline` | `plus` |
| `edit` | `pen` | `pen` | `pen-outline` | `pencil` |
| `delete` | `trash` | `trash` | `trash-bin-outline` | `trash` |
| `remove` | `dash` | `minus` | `minus-outline` | `minus` |
| `save` | `floppy` | `save` | `floppy-disk-outline` | — |
| `undo` | `arrow-counterclockwise` | `rotate-left` | `undo-outline` | `arrow-uturn-left` |
| `refresh` | `arrow-clockwise` | `refresh` | `refresh-outline` | `arrow-path` |
| `resume` | `play` | `play` | `play-outline` | `play` |
| `close` | `x` | `x` | `close-outline` | `x-mark` |
| `search` | `search` | `search` | `search-outline` | `magnifying-glass` |
| `filter` | `filter` | `filter` | `filter-outline` | `funnel` |
| `filter-clear` | — | — | — | — |
| `bookmark` | `bookmark` | `bookmark` | `bookmark-outline` | `bookmark` |
| `bookmark-active` | `bookmark-fill` | `bxs-bookmark` | `bookmark-solid` | `bookmark-solid` |
| `settings` | `sliders` | `slider-alt` | `adjustments-horizontal-outline` | `adjustments-horizontal` |
| `more` | `three-dots-vertical` | `dots-vertical` | `dots-vertical-outline` | `ellipsis-vertical` |
| `drag` | `grip-vertical` | `grid-vertical` | — | — |
| `print` | `printer` | `printer` | `printer-outline` | `printer` |
| `copy` | `copy` | `copy` | `clipboard-outline` | `clipboard` |
| `download` | `download` | `download` | `download-outline` | `arrow-down-tray` |
| `upload` | `cloud-arrow-up` | `upload` | `cloud-arrow-up-outline` | `cloud-arrow-up` |
| `send` | `send` | `paper-plane` | `paper-plane-outline` | `paper-airplane` |
| `link` | `link` | `link` | `link-outline` | `link` |
| `external-link` | `box-arrow-up-right` | `link-external` | `arrow-up-right-from-square-outline` | `arrow-top-right-on-square` |
| `menu` | `list` | `menu` | `bars-outline` | `bars-3` |
| `dropdown` | `chevron-down` | `chevron-down` | `chevron-down-outline` | `chevron-down` |
| `forward` | `chevron-right` | `chevron-right` | `chevron-right-outline` | `chevron-right` |
| `sidebar-toggle` | `chevron-double-left` | `chevrons-left` | `chevron-double-left-outline` | `chevron-double-left` |
| `first` | `skip-start` | `skip-previous` | `backward-step-outline` | `backward` |
| `back` | `arrow-left` | `arrow-back` | `arrow-left-outline` | `arrow-left` |
| `next` | `arrow-right` | `right-arrow` | `arrow-right-outline` | `arrow-right` |
| `last` | `skip-forward` | `skip-next` | `forward-step-outline` | `forward` |
| `arrow-up` | `arrow-up` | `up-arrow-alt` | `arrow-up-outline` | `arrow-up` |
| `arrow-down` | `arrow-down` | `down-arrow-alt` | `arrow-down-outline` | `arrow-down` |
| `sort` | `arrow-down-up` | `sort` | `sort-outline` | `arrows-up-down` |
| `sort-asc` | `sort-up` | `sort-up` | `caret-up-outline` | `bars-arrow-up` |
| `sort-desc` | `sort-down` | `sort-down` | `caret-down-outline` | `bars-arrow-down` |
| `success` | `check-circle` | `check-circle` | `check-circle-solid` | `check-circle` |
| `info` | `info-circle` | `info-circle` | `info-circle-outline` | `information-circle` |
| `warning` | `exclamation-triangle` | `error-alt` | — | `exclamation-triangle` |
| `error` | `exclamation-circle` | `error` | `exclamation-circle-outline` | `exclamation-circle` |
| `check` | `check` | `check` | `check-outline` | `check` |
| `loading` | `arrow-repeat` | `loader` | `refresh-outline` | `arrow-path` |
| `empty` | `inbox` | `archive` | `inbox-outline` | `inbox` |
| `locked` | `lock` | `lock` | `lock-outline` | `lock-closed` |
| `notifications` | `bell` | `bell` | `bell-outline` | `bell` |
| `user` | `person` | `user` | `user-outline` | `user` |
| `logout` | `box-arrow-right` | `log-out` | — | `arrow-right-on-rectangle` |
| `impersonate` | `incognito` | `user-check` | — | — |
| `language` | `globe` | `globe` | `globe-outline` | `language` |
| `theme-light` | `sun` | `sun` | `sun-outline` | `sun` |
| `theme-dark` | `moon` | `moon` | `moon-outline` | `moon` |
| `theme-system` | `display` | `desktop` | `desktop-pc-outline` | `computer-desktop` |
| `email` | `envelope` | `envelope` | `envelope-outline` | `envelope` |
| `history-create` | `folder-plus` | `folder-plus` | `folder-plus-outline` | `folder-plus` |
| `history-transition` | `arrow-left-right` | `transfer` | `arrow-right-arrow-left-solid` | `arrows-right-left` |
| `time` | `clock` | `time` | `clock-outline` | `clock` |
| `calendar` | `calendar` | `calendar` | `calendar-month-outline` | `calendar-days` |
| `repeat` | `repeat` | `repeat` | — | `arrow-path-rounded-square` |
| `file` | `file-text` | `file` | `file-lines-outline` | `document-text` |
| `file-csv` | `filetype-csv` | — | `file-csv-outline` | — |
| `file-excel` | `file-excel` | `table` | — | — |
| `file-pdf` | `file-pdf` | `bxs-file-pdf` | `file-pdf-outline` | — |
| `bold` | `type-bold` | `bold` | — | `bold` |
| `italic` | `type-italic` | `italic` | — | `italic` |
| `bulleted-list` | `list-ul` | `list-ul` | `list-outline` | `list-bullet` |
| `numbered-list` | `list-ol` | `list-ol` | — | `numbered-list` |
| `quote` | `quote` | `bxs-quote-alt-right` | `quote-outline` | — |
| `operator-equals` | — | — | — | `equals` |
| `operator-not-equals` | — | — | — | — |
| `operator-less-than` | — | — | — | — |
| `operator-less-or-equal` | — | — | — | — |
| `operator-greater-than` | — | — | — | — |
| `operator-greater-or-equal` | — | — | — | — |
| `operator-between` | `arrow-left-right` | `expand-horizontal` | — | — |
| `operator-contains` | `type` | `text` | `text-size-outline` | — |
| `operator-starts-with` | — | `align-left` | `align-left-outline` | — |
| `operator-ends-with` | — | `align-right` | `align-right-outline` | — |
| `operator-empty` | `square` | `square` | — | — |
| `operator-not-empty` | `square-fill` | `bxs-square` | `stop-solid` | `stop-solid` |
| `trend-up` | `graph-up-arrow` | `trending-up` | `chart-line-up-outline` | `arrow-trending-up` |
| `trend-down` | `graph-down-arrow` | `trending-down` | `chart-line-down-outline` | `arrow-trending-down` |
| `table` | `table` | `table` | `grid-outline` | `table-cells` |

| Name | `iconoir` | `ionicons` | `lucide` | `mdi` |
|---|---|---|---|---|
| `add` | `plus` | `add-outline` | `plus` | `plus` |
| `edit` | `edit` | `pencil-outline` | `pen` | `pen` |
| `delete` | `trash` | `trash-outline` | `trash` | `delete` |
| `remove` | `minus` | `remove-outline` | `minus` | `minus` |
| `save` | `floppy-disk` | `save-outline` | `save` | `content-save` |
| `undo` | `undo` | `arrow-undo-outline` | `undo` | `rotate-left` |
| `refresh` | `refresh` | `refresh-outline` | `refresh-cw` | `refresh` |
| `resume` | `play` | `play-outline` | `play` | `play` |
| `close` | `xmark` | `close-outline` | `x` | `close` |
| `search` | `search` | `search-outline` | `search` | `magnify` |
| `filter` | `filter` | `filter-outline` | `filter` | `filter` |
| `filter-clear` | — | — | `filter-x` | `filter-remove` |
| `bookmark` | `bookmark` | `bookmark-outline` | `bookmark` | `bookmark-outline` |
| `bookmark-active` | `bookmark-solid` | `bookmark` | `bookmark-check` | `bookmark` |
| `settings` | `settings` | `settings-outline` | `sliders-vertical` | `tune` |
| `more` | `more-vert` | `ellipsis-vertical-outline` | `ellipsis-vertical` | `dots-vertical` |
| `drag` | `drag` | `reorder-two-outline` | `grip-vertical` | `drag` |
| `print` | `printer` | `print-outline` | `printer` | `printer` |
| `copy` | `copy` | `copy-outline` | `copy` | `content-copy` |
| `download` | `download` | `download-outline` | `download` | `download` |
| `upload` | `upload` | `cloud-upload-outline` | `upload` | `cloud-arrow-up` |
| `send` | `send` | `paper-plane-outline` | `send` | `send` |
| `link` | `link` | `link-outline` | `link` | `link` |
| `external-link` | `open-new-window` | `open-outline` | `square-arrow-out-up-right` | `open-in-new` |
| `menu` | `menu` | `menu-outline` | `menu` | `menu` |
| `dropdown` | `nav-arrow-down` | `chevron-down-outline` | `chevron-down` | `chevron-down` |
| `forward` | `nav-arrow-right` | `chevron-forward-outline` | `chevron-right` | `chevron-right` |
| `sidebar-toggle` | `fast-arrow-left` | — | `chevrons-left` | `chevron-double-left` |
| `first` | `skip-prev` | `play-skip-back-outline` | `skip-back` | `skip-previous` |
| `back` | `arrow-left` | `arrow-back-outline` | `arrow-left` | `arrow-left` |
| `next` | `arrow-right` | `arrow-forward-outline` | `arrow-right` | `arrow-right` |
| `last` | `skip-next` | `play-skip-forward-outline` | `skip-forward` | `skip-forward` |
| `arrow-up` | `arrow-up` | `arrow-up-outline` | `arrow-up` | `arrow-up` |
| `arrow-down` | `arrow-down` | `arrow-down-outline` | `arrow-down` | `arrow-down` |
| `sort` | `sort` | `swap-vertical-outline` | `arrow-up-down` | `sort` |
| `sort-asc` | `sort-up` | `caret-up-outline` | `arrow-up-narrow-wide` | `sort-ascending` |
| `sort-desc` | `sort-down` | `caret-down-outline` | `arrow-down-wide-narrow` | `sort-descending` |
| `success` | `check-circle` | `checkmark-circle-outline` | `circle-check` | `check-circle` |
| `info` | `info-circle` | `information-outline` | `info` | `information` |
| `warning` | `warning-triangle` | `warning-outline` | `triangle-alert` | `alert` |
| `error` | `warning-circle` | `alert-circle-outline` | `circle-alert` | `alert-circle` |
| `check` | `check` | `checkmark-outline` | `check` | `check` |
| `loading` | `refresh` | `refresh-outline` | `loader` | `loading` |
| `empty` | `archive` | `archive-outline` | `inbox` | `inbox` |
| `locked` | `lock` | `lock-closed-outline` | `lock` | `lock` |
| `notifications` | `bell` | `notifications-outline` | `bell` | `bell` |
| `user` | `user` | `person-outline` | `user` | `account` |
| `logout` | `log-out` | `log-out-outline` | `log-out` | `logout` |
| `impersonate` | `user-star` | `glasses-outline` | `venetian-mask` | `incognito` |
| `language` | `globe` | `globe-outline` | `globe` | `earth` |
| `theme-light` | `sun-light` | `sunny-outline` | `sun` | `white-balance-sunny` |
| `theme-dark` | `half-moon` | `moon-outline` | `moon` | `weather-night` |
| `theme-system` | `laptop` | `desktop-outline` | `monitor` | `monitor` |
| `email` | `mail` | `mail-outline` | `mail` | `email` |
| `history-create` | `folder-plus` | — | `folder-plus` | `folder-plus` |
| `history-transition` | `data-transfer-both` | `swap-horizontal-outline` | `arrow-left-right` | `arrow-left-right` |
| `time` | `clock` | `time-outline` | `clock` | `clock` |
| `calendar` | `calendar` | `calendar-outline` | `calendar-days` | `calendar` |
| `repeat` | `repeat` | `repeat-outline` | `repeat` | `repeat` |
| `file` | `page` | `document-text-outline` | `file-text` | `file-document` |
| `file-csv` | — | — | `file-spreadsheet` | `file-delimited` |
| `file-excel` | `table` | — | `file-spreadsheet` | `file-excel` |
| `file-pdf` | — | — | — | `file-pdf` |
| `bold` | `bold` | — | `bold` | `format-bold` |
| `italic` | `italic` | — | `italic` | `format-italic` |
| `bulleted-list` | `list` | `list-outline` | `list` | `format-list-bulleted` |
| `numbered-list` | `numbered-list-left` | — | `list-ordered` | `format-list-numbered` |
| `quote` | `quote` | `quote` | `quote` | `format-quote-close` |
| `operator-equals` | — | — | `equal` | `equal` |
| `operator-not-equals` | — | — | `equal-not` | `not-equal` |
| `operator-less-than` | — | — | — | `less-than` |
| `operator-less-or-equal` | — | — | — | `less-than-or-equal` |
| `operator-greater-than` | — | — | — | `greater-than` |
| `operator-greater-or-equal` | — | — | — | `greater-than-or-equal` |
| `operator-between` | — | — | `arrow-left-right` | `arrow-left-right` |
| `operator-contains` | `text` | `text-outline` | `text-search` | `text-search` |
| `operator-starts-with` | `align-left` | — | `align-left` | `format-align-left` |
| `operator-ends-with` | `align-right` | — | `align-right` | `format-align-right` |
| `operator-empty` | `square` | `square-outline` | `square` | `square-outline` |
| `operator-not-empty` | — | `square` | `square-dot` | `square` |
| `trend-up` | `graph-up` | `trending-up-outline` | `trending-up` | `trending-up` |
| `trend-down` | `graph-down` | `trending-down-outline` | `trending-down` | `trending-down` |
| `table` | `table` | `grid-outline` | `table` | `table` |

| Name | `octicons` | `phosphor` | `tabler` |
|---|---|---|---|
| `add` | `plus-24` | `plus` | `plus` |
| `edit` | `pencil-24` | `pen` | `pencil` |
| `delete` | `trash-24` | `trash` | `trash` |
| `remove` | `dash-24` | `minus` | `minus` |
| `save` | — | `floppy-disk` | `device-floppy` |
| `undo` | `undo-24` | `arrow-u-up-left` | `arrow-back-up` |
| `refresh` | `sync-24` | `arrow-clockwise` | `rotate` |
| `resume` | `triangle-circle-24` | `play` | `play` |
| `close` | `x-24` | `x` | `x` |
| `search` | `search-24` | `magnifying-glass` | `search` |
| `filter` | `filter-24` | `funnel` | `filter` |
| `filter-clear` | `filter-remove-24` | `funnel-x` | `filter-x` |
| `bookmark` | `bookmark-24` | `bookmark` | `bookmark` |
| `bookmark-active` | `bookmark-fill-24` | `bookmark-fill` | `bookmark-filled` |
| `settings` | `sliders-24` | `sliders` | `adjustments` |
| `more` | `kebab-horizontal-24` | `dots-three-vertical` | `dots-vertical` |
| `drag` | `grabber-24` | `dots-six-vertical` | `grip-vertical` |
| `print` | — | `printer` | `printer` |
| `copy` | `copy-24` | `copy` | `copy` |
| `download` | `download-24` | `download` | `download` |
| `upload` | `upload-24` | `cloud-arrow-up` | `upload` |
| `send` | `paper-airplane-24` | `paper-plane` | `send` |
| `link` | `link-24` | `link` | `link` |
| `external-link` | `link-external-24` | `arrow-square-out` | `external-link` |
| `menu` | `three-bars-24` | `list` | `menu` |
| `dropdown` | `chevron-down-24` | `caret-down` | `chevron-down` |
| `forward` | `chevron-right-24` | `caret-right` | `chevron-right` |
| `sidebar-toggle` | — | `caret-double-left` | `chevrons-left` |
| `first` | — | `skip-back` | `skip-back` |
| `back` | `arrow-left-24` | `arrow-left` | `arrow-left` |
| `next` | `arrow-right-24` | `arrow-right` | `arrow-right` |
| `last` | — | `skip-forward` | `skip-forward` |
| `arrow-up` | `arrow-up-24` | `arrow-up` | `arrow-up` |
| `arrow-down` | `arrow-down-24` | `arrow-down` | `arrow-down` |
| `sort` | — | `arrows-down-up` | `arrows-up-down` |
| `sort-asc` | `sort-asc-24` | `sort-ascending` | `sort-ascending` |
| `sort-desc` | `sort-desc-24` | `sort-descending` | `sort-descending` |
| `success` | `check-circle-24` | `check-circle` | `circle-check` |
| `info` | `info-24` | `info` | `info-circle` |
| `warning` | `alert-24` | `warning` | `alert-triangle` |
| `error` | `stop-24` | `warning-circle` | `alert-circle` |
| `check` | `check-24` | `check` | `check` |
| `loading` | — | `spinner` | `loader` |
| `empty` | `inbox-24` | `tray` | `inbox` |
| `locked` | `lock-24` | `lock` | `lock` |
| `notifications` | `bell-24` | `bell` | `bell` |
| `user` | `person-24` | `user` | `user` |
| `logout` | `sign-out-24` | `sign-out` | `logout` |
| `impersonate` | — | `detective` | `spy` |
| `language` | `globe-24` | `globe` | `globe` |
| `theme-light` | `sun-24` | `sun` | `sun` |
| `theme-dark` | `moon-24` | `moon` | `moon` |
| `theme-system` | `device-desktop-24` | `desktop` | `device-desktop` |
| `email` | `mail-24` | `envelope` | `mail` |
| `history-create` | — | `folder-plus` | `folder-plus` |
| `history-transition` | — | `arrows-left-right` | `arrows-left-right` |
| `time` | `clock-24` | `clock` | `clock` |
| `calendar` | `calendar-24` | `calendar` | `calendar` |
| `repeat` | — | `repeat` | `repeat` |
| `file` | `file-24` | `file-text` | `file-text` |
| `file-csv` | — | `file-csv` | `file-type-csv` |
| `file-excel` | `table-24` | `file-xls` | `file-excel` |
| `file-pdf` | `file-pdf` | `file-pdf` | `file-type-pdf` |
| `bold` | `bold-24` | `text-t-bold` | `bold` |
| `italic` | `italic-24` | `text-italic` | `italic` |
| `bulleted-list` | `list-unordered-24` | `list` | `list` |
| `numbered-list` | `list-ordered-24` | `list-numbers` | `list-numbers` |
| `quote` | `quote-24` | `quotes` | `quote` |
| `operator-equals` | — | `equals` | `equal` |
| `operator-not-equals` | — | `not-equals` | `equal-not` |
| `operator-less-than` | — | `less-than` | `math-lower` |
| `operator-less-or-equal` | — | `less-than-or-equal` | `math-equal-lower` |
| `operator-greater-than` | — | `greater-than` | `math-greater` |
| `operator-greater-or-equal` | — | `greater-than-or-equal` | `math-equal-greater` |
| `operator-between` | — | `arrows-left-right` | `arrows-left-right` |
| `operator-contains` | `text-size` | `text-t` | `text-size` |
| `operator-starts-with` | — | `text-align-left` | `align-left` |
| `operator-ends-with` | — | `text-align-right` | `align-right` |
| `operator-empty` | `square-24` | `square` | `square` |
| `operator-not-empty` | `square-fill-24` | `square-fill` | `square-filled` |
| `trend-up` | — | `trend-up` | `trending-up` |
| `trend-down` | — | `trend-down` | `trending-down` |
| `table` | `table-24` | `table` | `table` |

The Font Awesome table is the [catalogue](icons.md#catalogue).
