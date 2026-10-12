# Icon libraries

The project chooses its icon library in `lle_crudit_ui.icons.library` (see [Configuration](configuration.md#icons)).
`ui_icon()` builds the Iconify name of each logical name of the [catalogue](icons.md) from the prefix of the library
and the base name of the catalogue, which is the Font Awesome name (`fa7-solid:trash`, `lucide:trash`,
`octicon:trash-24`), except for the names that differ (`IconLibrary`; with Font Awesome, the regular icons such as
`fa7-regular:bookmark`),
and [Symfony UX Icons](https://symfony.com/bundles/ux-icons/current/index.html) renders it as an inline SVG. The
libraries are the icon sets listed by the Symfony UX Icons documentation, Font Awesome in version 7.

A library does not always have an icon for every logical name (mathematical operators, file types…): the bundle
then takes the **Tabler Icons** one, a complete set with an outline style, marked *(Tabler)* below. Any icon can be
replaced with `lle_crudit_ui.icons.map`.

`IconLibraryTest` checks that every name exists in its Iconify set.

| `library` | Library | Iconify prefix | Licence | Icons taken from Tabler |
|---|---|---|---|---|
| `fontawesome` | [Font Awesome 7 (default)](https://fontawesome.com) | `fa7-solid`, `fa7-regular` | CC BY 4.0 | 0 |
| `bootstrap-icons` | [Bootstrap Icons](https://icons.getbootstrap.com) | `bi` | MIT | 9 |
| `boxicons` | [Boxicons](https://boxicons.com) | `bx` | MIT | 8 |
| `flowbite` | [Flowbite](https://flowbite.com/icons/) | `flowbite` | MIT | 19 |
| `heroicons` | [Heroicons](https://heroicons.com) | `heroicons` | MIT | 18 |
| `iconoir` | [Iconoir](https://iconoir.com) | `iconoir` | MIT | 11 |
| `ionicons` | [Ionicons](https://ionic.io/ionicons) | `ion` | MIT | 19 |
| `lucide` | [Lucide](https://lucide.dev) | `lucide` | ISC | 6 |
| `mdi` | [Material Design Icons](https://pictogrammers.com/library/mdi/) | `mdi` | Apache 2.0 | 0 |
| `octicons` | [Octicons](https://primer.style/octicons) | `octicon` | MIT | 25 |
| `phosphor` | [Phosphor](https://phosphoricons.com) | `ph` | MIT | 0 |
| `tabler` | [Tabler Icons](https://tabler.io/icons) | `tabler` | MIT | 0 |

## Tables

| Name | `bootstrap-icons` | `boxicons` | `flowbite` | `heroicons` |
|---|---|---|---|---|
| `add` | `plus` | `plus` | `plus-outline` | `plus` |
| `edit` | `pen` | `pen` | `pen-outline` | `pencil` |
| `delete` | `trash` | `trash` | `trash-bin-outline` | `trash` |
| `remove` | `dash` | `minus` | `minus-outline` | `minus` |
| `save` | `floppy` | `save` | `floppy-disk-outline` | `device-floppy` *(Tabler)* |
| `undo` | `arrow-counterclockwise` | `rotate-left` | `undo-outline` | `arrow-uturn-left` |
| `refresh` | `arrow-clockwise` | `refresh` | `rotate-solid` | `arrow-path` |
| `resume` | `play` | `play` | `play-outline` | `play` |
| `close` | `x` | `x` | `x-outline` | `x-mark` |
| `search` | `search` | `search` | `search-outline` | `magnifying-glass` |
| `filter` | `filter` | `filter` | `filter-outline` | `funnel` |
| `filter-clear` | `filter-x` *(Tabler)* | `filter-x` *(Tabler)* | `filter-x` *(Tabler)* | `filter-x` *(Tabler)* |
| `bookmark` | `bookmark` | `bookmark` | `bookmark-outline` | `bookmark` |
| `bookmark-active` | `bookmark-fill` | `bxs-bookmark` | `bookmark-solid` | `bookmark-solid` |
| `settings` | `sliders` | `slider-alt` | `adjustments-horizontal-outline` | `adjustments-horizontal` |
| `more` | `three-dots-vertical` | `dots-vertical` | `dots-vertical-outline` | `ellipsis-vertical` |
| `drag` | `grip-vertical` | `grid-vertical` | `grip-vertical` *(Tabler)* | `grip-vertical` *(Tabler)* |
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
| `sort` | `arrow-up-down` | `sort` | `sort-outline` | `arrows-up-down` |
| `sort-asc` | `sort-up` | `sort-up` | `caret-up-outline` | `bars-arrow-up` |
| `sort-desc` | `sort-down` | `sort-down` | `caret-down-outline` | `bars-arrow-down` |
| `success` | `check-circle` | `check-circle` | `circle-check-solid` | `check-circle` |
| `info` | `info-circle` | `info-circle` | `info-circle-outline` | `information-circle` |
| `warning` | `exclamation-triangle` | `error-alt` | `alert-triangle` *(Tabler)* | `exclamation-triangle` |
| `error` | `exclamation-circle` | `error` | `exclamation-circle-outline` | `exclamation-circle` |
| `check` | `check` | `check` | `check-outline` | `check` |
| `loading` | `arrow-repeat` | `loader` | `refresh-outline` | `arrow-path` |
| `empty` | `inbox` | `archive` | `inbox-outline` | `inbox` |
| `locked` | `lock` | `lock` | `lock-outline` | `lock-closed` |
| `notifications` | `bell` | `bell` | `bell-outline` | `bell` |
| `user` | `person` | `user` | `user-outline` | `user` |
| `logout` | `box-arrow-right` | `log-out` | `logout` *(Tabler)* | `arrow-right-on-rectangle` |
| `impersonate` | `incognito` | `user-check` | `spy` *(Tabler)* | `spy` *(Tabler)* |
| `language` | `globe` | `globe` | `globe-outline` | `language` |
| `theme-light` | `sun` | `sun` | `sun-outline` | `sun` |
| `theme-dark` | `moon` | `moon` | `moon-outline` | `moon` |
| `theme-system` | `display` | `desktop` | `device-desktop` *(Tabler)* | `computer-desktop` |
| `email` | `envelope` | `envelope` | `envelope-outline` | `envelope` |
| `history-create` | `folder-plus` | `folder-plus` | `folder-plus-outline` | `folder-plus` |
| `history-transition` | `arrow-left-right` | `transfer` | `arrow-right-arrow-left-solid` | `arrows-right-left` |
| `time` | `clock` | `time` | `clock-outline` | `clock` |
| `calendar` | `calendar` | `calendar` | `calendar-month-outline` | `calendar-days` |
| `repeat` | `repeat` | `repeat` | `repeat` *(Tabler)* | `arrow-path-rounded-square` |
| `file` | `file-text` | `file` | `file-lines-outline` | `document-text` |
| `file-csv` | `filetype-csv` | `file-type-csv` *(Tabler)* | `file-csv-outline` | `file-type-csv` *(Tabler)* |
| `file-excel` | `file-excel` | `table` | `file-excel` *(Tabler)* | `file-excel` *(Tabler)* |
| `file-pdf` | `file-pdf` | `bxs-file-pdf` | `file-pdf-outline` | `file-type-pdf` *(Tabler)* |
| `bold` | `type-bold` | `bold` | `bold` *(Tabler)* | `bold` |
| `italic` | `type-italic` | `italic` | `italic` *(Tabler)* | `italic` |
| `bulleted-list` | `list-ul` | `list-ul` | `list-outline` | `list-bullet` |
| `numbered-list` | `list-ol` | `list-ol` | `list-numbers` *(Tabler)* | `numbered-list` |
| `quote` | `quote` | `bxs-quote-alt-right` | `quote-outline` | `quote` *(Tabler)* |
| `operator-equals` | `equal` *(Tabler)* | `equal` *(Tabler)* | `equal` *(Tabler)* | `equals` |
| `operator-not-equals` | `equal-not` *(Tabler)* | `equal-not` *(Tabler)* | `equal-not` *(Tabler)* | `equal-not` *(Tabler)* |
| `operator-less-than` | `math-lower` *(Tabler)* | `math-lower` *(Tabler)* | `math-lower` *(Tabler)* | `math-lower` *(Tabler)* |
| `operator-less-or-equal` | `math-equal-lower` *(Tabler)* | `math-equal-lower` *(Tabler)* | `math-equal-lower` *(Tabler)* | `math-equal-lower` *(Tabler)* |
| `operator-greater-than` | `math-greater` *(Tabler)* | `math-greater` *(Tabler)* | `math-greater` *(Tabler)* | `math-greater` *(Tabler)* |
| `operator-greater-or-equal` | `math-equal-greater` *(Tabler)* | `math-equal-greater` *(Tabler)* | `math-equal-greater` *(Tabler)* | `math-equal-greater` *(Tabler)* |
| `operator-between` | `arrow-left-right` | `expand-horizontal` | `arrows-left-right` *(Tabler)* | `arrows-left-right` *(Tabler)* |
| `operator-contains` | `type` | `text` | `text-size-outline` | `text-size` *(Tabler)* |
| `operator-starts-with` | `align-left` *(Tabler)* | `align-left` | `align-left-outline` | `align-left` *(Tabler)* |
| `operator-ends-with` | `align-right` *(Tabler)* | `align-right` | `align-right-outline` | `align-right` *(Tabler)* |
| `operator-empty` | `square` | `square` | `square` *(Tabler)* | `square` *(Tabler)* |
| `operator-not-empty` | `square-fill` | `bxs-square` | `stop-solid` | `stop-solid` |
| `trend-up` | `graph-up-arrow` | `trending-up` | `chart-line-up-outline` | `arrow-trending-up` |
| `trend-down` | `graph-down-arrow` | `trending-down` | `chart-line-down-outline` | `arrow-trending-down` |
| `table` | `table` | `table` | `grid-outline` | `table-cells` |

| Name | `iconoir` | `ionicons` | `lucide` | `mdi` |
|---|---|---|---|---|
| `add` | `plus` | `plus` | `plus` | `plus` |
| `edit` | `edit` | `pencil-outline` | `pen` | `pen` |
| `delete` | `trash` | `trash-outline` | `trash` | `trash` |
| `remove` | `minus` | `minus` | `minus` | `minus` |
| `save` | `floppy-disk` | `save-outline` | `save` | `floppy-disk` |
| `undo` | `undo` | `arrow-undo-outline` | `undo` | `rotate-left` |
| `refresh` | `refresh` | `refresh-outline` | `refresh-cw` | `refresh` |
| `resume` | `play` | `play-outline` | `play` | `play` |
| `close` | `xmark` | `close-outline` | `x` | `close` |
| `search` | `search` | `search-outline` | `search` | `search` |
| `filter` | `filter` | `filter-outline` | `filter` | `filter` |
| `filter-clear` | `filter-x` *(Tabler)* | `filter-x` *(Tabler)* | `filter-x` | `filter-remove` |
| `bookmark` | `bookmark` | `bookmark-outline` | `bookmark` | `bookmark-outline` |
| `bookmark-active` | `bookmark-solid` | `bookmark` | `bookmark-check` | `bookmark` |
| `settings` | `settings` | `settings-outline` | `sliders` | `tune` |
| `more` | `more-vert` | `ellipsis-vertical-outline` | `ellipsis-vertical` | `ellipsis-vertical` |
| `drag` | `drag` | `drag` | `grip-vertical` | `drag` |
| `print` | `printer` | `print-outline` | `printer` | `printer` |
| `copy` | `copy` | `copy-outline` | `copy` | `content-copy` |
| `download` | `download` | `download-outline` | `download` | `download` |
| `upload` | `upload` | `upload` | `upload` | `cloud-arrow-up` |
| `send` | `send` | `paper-plane-outline` | `send` | `paper-plane` |
| `link` | `link` | `link-outline` | `link` | `link` |
| `external-link` | `open-new-window` | `open-outline` | `arrow-up-right-from-square` | `external-link` |
| `menu` | `menu` | `menu-outline` | `menu` | `menu` |
| `dropdown` | `nav-arrow-down` | `chevron-down-outline` | `chevron-down` | `chevron-down` |
| `forward` | `nav-arrow-right` | `chevron-right` | `chevron-right` | `chevron-right` |
| `sidebar-toggle` | `fast-arrow-left` | `chevrons-left` *(Tabler)* | `chevrons-left` | `chevron-double-left` |
| `first` | `skip-prev` | `skip-backward` | `skip-back` | `skip-previous` |
| `back` | `arrow-left` | `arrow-back-outline` | `arrow-left` | `arrow-left` |
| `next` | `arrow-right` | `arrow-forward-outline` | `arrow-right` | `arrow-right` |
| `last` | `skip-next` | `skip-forward` | `skip-forward` | `skip-forward` |
| `arrow-up` | `arrow-up` | `arrow-up-outline` | `arrow-up` | `arrow-up` |
| `arrow-down` | `arrow-down` | `arrow-down-outline` | `arrow-down` | `arrow-down` |
| `sort` | `sort` | `arrows-up-down` *(Tabler)* | `arrow-up-down` | `sort` |
| `sort-asc` | `sort-up` | `caret-up-outline` | `sort-asc` | `sort-ascending` |
| `sort-desc` | `sort-down` | `caret-down-outline` | `sort-desc` | `sort-descending` |
| `success` | `check-circle` | `checkmark-circle-outline` | `circle-check` | `check-circle` |
| `info` | `info-circle` | `information-outline` | `info` | `info-circle` |
| `warning` | `warning-triangle` | `warning-outline` | `alert-triangle` | `warning` |
| `error` | `warning-circle` | `alert-circle-outline` | `alert-circle` | `alert-circle` |
| `check` | `check` | `checkmark-outline` | `check` | `check` |
| `loading` | `refresh` | `refresh-outline` | `loader` | `loading` |
| `empty` | `archive` | `archive-outline` | `inbox` | `inbox` |
| `locked` | `lock` | `lock-closed-outline` | `lock` | `lock` |
| `notifications` | `bell` | `notifications-outline` | `bell` | `bell` |
| `user` | `user` | `person-outline` | `user` | `user` |
| `logout` | `log-out` | `log-out-outline` | `log-out` | `logout` |
| `impersonate` | `user-star` | `glasses-outline` | `venetian-mask` | `incognito` |
| `language` | `globe` | `globe-outline` | `globe` | `globe` |
| `theme-light` | `sun-light` | `sunny-outline` | `sun` | `white-balance-sunny` |
| `theme-dark` | `half-moon` | `moon-outline` | `moon` | `weather-night` |
| `theme-system` | `laptop` | `desktop-outline` | `monitor` | `monitor` |
| `email` | `mail` | `mail-outline` | `mail` | `envelope` |
| `history-create` | `folder-plus` | `folder-plus` *(Tabler)* | `folder-plus` | `folder-plus` |
| `history-transition` | `data-transfer-both` | `swap-horizontal-outline` | `arrow-left-right` | `arrow-left-right` |
| `time` | `clock` | `clock` | `clock` | `clock` |
| `calendar` | `calendar` | `calendar-outline` | `calendar-days` | `calendar` |
| `repeat` | `repeat` | `repeat-outline` | `repeat` | `repeat` |
| `file` | `page` | `document-text-outline` | `file-text` | `file-text` |
| `file-csv` | `file-type-csv` *(Tabler)* | `file-type-csv` *(Tabler)* | `file-spreadsheet` | `file-csv` |
| `file-excel` | `table` | `file-excel` *(Tabler)* | `file-spreadsheet` | `file-excel` |
| `file-pdf` | `file-type-pdf` *(Tabler)* | `file-type-pdf` *(Tabler)* | `file-type-pdf` *(Tabler)* | `file-pdf` |
| `bold` | `bold` | `bold` *(Tabler)* | `bold` | `format-bold` |
| `italic` | `italic` | `italic` *(Tabler)* | `italic` | `format-italic` |
| `bulleted-list` | `list` | `list-outline` | `list` | `format-list-bulleted` |
| `numbered-list` | `numbered-list-left` | `list-numbers` *(Tabler)* | `list-ordered` | `format-list-numbered` |
| `quote` | `quote` | `quote` | `quote` | `format-quote-close` |
| `operator-equals` | `equal` *(Tabler)* | `equal` *(Tabler)* | `equal` | `equal` |
| `operator-not-equals` | `equal-not` *(Tabler)* | `equal-not` *(Tabler)* | `equal-not` | `not-equal` |
| `operator-less-than` | `math-lower` *(Tabler)* | `math-lower` *(Tabler)* | `math-lower` *(Tabler)* | `less-than` |
| `operator-less-or-equal` | `math-equal-lower` *(Tabler)* | `math-equal-lower` *(Tabler)* | `math-equal-lower` *(Tabler)* | `less-than-or-equal` |
| `operator-greater-than` | `math-greater` *(Tabler)* | `math-greater` *(Tabler)* | `math-greater` *(Tabler)* | `greater-than` |
| `operator-greater-or-equal` | `math-equal-greater` *(Tabler)* | `math-equal-greater` *(Tabler)* | `math-equal-greater` *(Tabler)* | `greater-than-or-equal` |
| `operator-between` | `arrows-left-right` *(Tabler)* | `arrows-left-right` *(Tabler)* | `arrow-left-right` | `arrow-left-right` |
| `operator-contains` | `text` | `text-outline` | `text-search` | `text-search` |
| `operator-starts-with` | `align-left` | `align-left` *(Tabler)* | `align-left` | `format-align-left` |
| `operator-ends-with` | `align-right` | `align-right` *(Tabler)* | `align-right` | `format-align-right` |
| `operator-empty` | `square` | `square-outline` | `square` | `square-outline` |
| `operator-not-empty` | `square-filled` *(Tabler)* | `square` | `square-filled` *(Tabler)* | `square` |
| `trend-up` | `graph-up` | `trending-up-outline` | `trending-up` | `trending-up` |
| `trend-down` | `graph-down` | `trending-down-outline` | `trending-down` | `trending-down` |
| `table` | `table` | `grid-outline` | `table` | `table` |

| Name | `octicons` | `phosphor` | `tabler` |
|---|---|---|---|
| `add` | `plus-24` | `plus` | `plus` |
| `edit` | `pencil-24` | `pen` | `pencil` |
| `delete` | `trash-24` | `trash` | `trash` |
| `remove` | `dash-24` | `minus` | `minus` |
| `save` | `device-floppy` *(Tabler)* | `floppy-disk` | `device-floppy` |
| `undo` | `undo-24` | `arrow-u-up-left` | `arrow-back-up` |
| `refresh` | `sync-24` | `arrow-clockwise` | `rotate` |
| `resume` | `play-24` | `play` | `play` |
| `close` | `x-24` | `x` | `x` |
| `search` | `search-24` | `magnifying-glass` | `search` |
| `filter` | `filter-24` | `funnel` | `filter` |
| `filter-clear` | `filter-remove-24` | `funnel-x` | `filter-x` |
| `bookmark` | `bookmark-24` | `bookmark` | `bookmark` |
| `bookmark-active` | `bookmark-fill-24` | `bookmark-fill` | `bookmark-filled` |
| `settings` | `sliders-24` | `sliders` | `adjustments` |
| `more` | `kebab-horizontal-24` | `dots-three-vertical` | `dots-vertical` |
| `drag` | `grip-vertical` *(Tabler)* | `dots-six-vertical` | `grip-vertical` |
| `print` | `printer` *(Tabler)* | `printer` | `printer` |
| `copy` | `copy-24` | `copy` | `copy` |
| `download` | `download-24` | `download` | `download` |
| `upload` | `upload-24` | `cloud-arrow-up` | `upload` |
| `send` | `paper-airplane-24` | `paper-plane` | `send` |
| `link` | `link-24` | `link` | `link` |
| `external-link` | `link-external-24` | `arrow-square-out` | `external-link` |
| `menu` | `three-bars-24` | `list` | `menu` |
| `dropdown` | `chevron-down-24` | `caret-down` | `chevron-down` |
| `forward` | `chevron-right-24` | `caret-right` | `chevron-right` |
| `sidebar-toggle` | `chevrons-left` *(Tabler)* | `caret-double-left` | `chevrons-left` |
| `first` | `skip-back` *(Tabler)* | `skip-back` | `skip-back` |
| `back` | `arrow-left-24` | `arrow-left` | `arrow-left` |
| `next` | `arrow-right-24` | `arrow-right` | `arrow-right` |
| `last` | `skip-forward` *(Tabler)* | `skip-forward` | `skip-forward` |
| `arrow-up` | `arrow-up-24` | `arrow-up` | `arrow-up` |
| `arrow-down` | `arrow-down-24` | `arrow-down` | `arrow-down` |
| `sort` | `arrows-up-down` *(Tabler)* | `arrows-down-up` | `arrows-up-down` |
| `sort-asc` | `sort-asc-24` | `sort-ascending` | `sort-ascending` |
| `sort-desc` | `sort-desc-24` | `sort-descending` | `sort-descending` |
| `success` | `check-circle-24` | `check-circle` | `circle-check` |
| `info` | `info-24` | `info` | `info-circle` |
| `warning` | `alert-24` | `warning` | `alert-triangle` |
| `error` | `alert-circle` *(Tabler)* | `warning-circle` | `alert-circle` |
| `check` | `check-24` | `check` | `check` |
| `loading` | `loader` *(Tabler)* | `spinner` | `loader` |
| `empty` | `inbox-24` | `tray` | `inbox` |
| `locked` | `lock-24` | `lock` | `lock` |
| `notifications` | `bell-24` | `bell` | `bell` |
| `user` | `person-24` | `user` | `user` |
| `logout` | `sign-out-24` | `sign-out` | `logout` |
| `impersonate` | `spy` *(Tabler)* | `detective` | `spy` |
| `language` | `globe-24` | `globe` | `globe` |
| `theme-light` | `sun-24` | `sun` | `sun` |
| `theme-dark` | `moon-24` | `moon` | `moon` |
| `theme-system` | `device-desktop-24` | `desktop` | `device-desktop` |
| `email` | `mail-24` | `envelope` | `mail` |
| `history-create` | `folder-plus` *(Tabler)* | `folder-plus` | `folder-plus` |
| `history-transition` | `arrows-left-right` *(Tabler)* | `arrows-left-right` | `arrows-left-right` |
| `time` | `clock-24` | `clock` | `clock` |
| `calendar` | `calendar-24` | `calendar` | `calendar` |
| `repeat` | `repeat` *(Tabler)* | `repeat` | `repeat` |
| `file` | `file-24` | `file-text` | `file-text` |
| `file-csv` | `file-type-csv` *(Tabler)* | `file-csv` | `file-type-csv` |
| `file-excel` | `table-24` | `file-xls` | `file-excel` |
| `file-pdf` | `file-pdf` | `file-pdf` | `file-type-pdf` |
| `bold` | `bold-24` | `text-bold` | `bold` |
| `italic` | `italic-24` | `text-italic` | `italic` |
| `bulleted-list` | `list-unordered-24` | `list` | `list` |
| `numbered-list` | `list-ordered-24` | `list-numbers` | `list-numbers` |
| `quote` | `quote-24` | `quotes` | `quote` |
| `operator-equals` | `equal` *(Tabler)* | `equals` | `equal` |
| `operator-not-equals` | `equal-not` *(Tabler)* | `not-equals` | `equal-not` |
| `operator-less-than` | `math-lower` *(Tabler)* | `less-than` | `math-lower` |
| `operator-less-or-equal` | `math-equal-lower` *(Tabler)* | `less-than-or-equal` | `math-equal-lower` |
| `operator-greater-than` | `math-greater` *(Tabler)* | `greater-than` | `math-greater` |
| `operator-greater-or-equal` | `math-equal-greater` *(Tabler)* | `greater-than-or-equal` | `math-equal-greater` |
| `operator-between` | `arrows-left-right` *(Tabler)* | `arrows-left-right` | `arrows-left-right` |
| `operator-contains` | `text-size` | `text` | `text-size` |
| `operator-starts-with` | `align-left` *(Tabler)* | `text-align-left` | `align-left` |
| `operator-ends-with` | `align-right` *(Tabler)* | `text-align-right` | `align-right` |
| `operator-empty` | `square-24` | `square` | `square` |
| `operator-not-empty` | `square-fill-24` | `square-fill` | `square-filled` |
| `trend-up` | `trending-up` *(Tabler)* | `trend-up` | `trending-up` |
| `trend-down` | `trending-down` *(Tabler)* | `trend-down` | `trending-down` |
| `table` | `table-24` | `table` | `table` |

The Font Awesome table is the [catalogue](icons.md#catalogue).
