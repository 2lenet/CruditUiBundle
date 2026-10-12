# Icons

> **Draft (crudit#660): the list of logical names below is waiting for validation.**

Templates never write an icon class. They render an icon from its **logical name**, which describes what the icon
means, not what it looks like. Arrows and chevrons (except `dropdown`) are the exception: the same drawing serves
many purposes (back, previous page, old value → new value…), so they get a generic name (`back`, `next`, `forward`,
`arrow-up`…). **One drawing has one name**, shared by every place that uses it:

```twig
{{ ui_icon('delete') }}
```

The bundle maps each logical name to a Font Awesome 7 Free class. The icons chosen by the projects (actions, menu,
tabs) are not in the catalogue: the project passes their Font Awesome class as is.

```twig
{{ ui_icon('fa-solid fa-truck') }}
```

`lle_crudit_ui.icons.map` replaces any of these classes by another one, whether it comes from the catalogue or from the
project (see [Configuration](configuration.md#icons)). It is keyed by the Font Awesome class, not by the logical
name.

Logical names are in kebab-case (`external-link`, `sort-asc`). **RTL** marks the directional icons: they are
mirrored when the page is right-to-left (`dir="rtl"`).

## Catalogue

The previews are the SVG files of [Font Awesome Free](https://fontawesome.com) 7.3.1 (icons under
[CC BY 4.0](https://creativecommons.org/licenses/by/4.0/)), in grey so that they show on light and dark themes. They
are not mirrored: the RTL column says which icons are.

### Actions

| | Name | Font Awesome 7 | RTL | Used in |
|---|---|---|---|---|
| <img src="images/icons/solid-plus.svg" width="16" height="16" alt=""> | `add` | `fa-solid fa-plus` | | Crudit (new, add a filter, collection), mockup |
| <img src="images/icons/solid-pen.svg" width="16" height="16" alt=""> | `edit` | `fa-solid fa-pen` | | Crudit (`edit`), mockup |
| <img src="images/icons/solid-trash.svg" width="16" height="16" alt=""> | `delete` | `fa-solid fa-trash` | | Crudit (`trash-alt`), mockup |
| <img src="images/icons/solid-minus.svg" width="16" height="16" alt=""> | `remove` | `fa-solid fa-minus` | | Crudit (fewer filters) |
| <img src="images/icons/solid-floppy-disk.svg" width="16" height="16" alt=""> | `save` | `fa-solid fa-floppy-disk` | | mockup (form, tab form) |
| <img src="images/icons/solid-rotate-left.svg" width="16" height="16" alt=""> | `undo` | `fa-solid fa-rotate-left` | yes | mockup (reset the form) |
| <img src="images/icons/solid-rotate.svg" width="16" height="16" alt=""> | `refresh` | `fa-solid fa-rotate` | | mockup (list, dashboard) |
| <img src="images/icons/solid-play.svg" width="16" height="16" alt=""> | `resume` | `fa-solid fa-play` | | mockup (resume the automatic refresh of a list) |
| <img src="images/icons/solid-xmark.svg" width="16" height="16" alt=""> | `close` | `fa-solid fa-xmark` | | Crudit (`close`, `window-close`), mockup |
| <img src="images/icons/solid-magnifying-glass.svg" width="16" height="16" alt=""> | `search` | `fa-solid fa-magnifying-glass` | | Crudit (search, show action), mockup |
| <img src="images/icons/solid-filter.svg" width="16" height="16" alt=""> | `filter` | `fa-solid fa-filter` | | Crudit, mockup |
| <img src="images/icons/solid-filter-circle-xmark.svg" width="16" height="16" alt=""> | `filter-clear` | `fa-solid fa-filter-circle-xmark` | | mockup (no result with the current filters) |
| <img src="images/icons/regular-bookmark.svg" width="16" height="16" alt=""> | `bookmark` | `fa-regular fa-bookmark` | | mockup (save the filters) |
| <img src="images/icons/solid-bookmark.svg" width="16" height="16" alt=""> | `bookmark-active` | `fa-solid fa-bookmark` | | mockup (saved filters in use) |
| <img src="images/icons/solid-sliders.svg" width="16" height="16" alt=""> | `settings` | `fa-solid fa-sliders` | | mockup (list columns, widget settings) |
| <img src="images/icons/solid-ellipsis-vertical.svg" width="16" height="16" alt=""> | `more` | `fa-solid fa-ellipsis-vertical` | | Crudit (kebab menu), mockup |
| <img src="images/icons/solid-grip-vertical.svg" width="16" height="16" alt=""> | `drag` | `fa-solid fa-grip-vertical` | | Crudit (sortable rows), mockup |
| <img src="images/icons/solid-print.svg" width="16" height="16" alt=""> | `print` | `fa-solid fa-print` | | mockup |
| <img src="images/icons/solid-copy.svg" width="16" height="16" alt=""> | `copy` | `fa-solid fa-copy` | | mockup |
| <img src="images/icons/solid-download.svg" width="16" height="16" alt=""> | `download` | `fa-solid fa-download` | | Crudit (file widget, `file-export` action), mockup (export, file upload) |
| <img src="images/icons/solid-cloud-arrow-up.svg" width="16" height="16" alt=""> | `upload` | `fa-solid fa-cloud-arrow-up` | | mockup (file upload, data import widget) |
| <img src="images/icons/solid-paper-plane.svg" width="16" height="16" alt=""> | `send` | `fa-solid fa-paper-plane` | yes | mockup |
| <img src="images/icons/solid-link.svg" width="16" height="16" alt=""> | `link` | `fa-solid fa-link` | | mockup (rich text editor) |
| <img src="images/icons/solid-arrow-up-right-from-square.svg" width="16" height="16" alt=""> | `external-link` | `fa-solid fa-arrow-up-right-from-square` | yes | Crudit (`external-link-alt`), mockup |

### Navigation

| | Name | Font Awesome 7 | RTL | Used in |
|---|---|---|---|---|
| <img src="images/icons/solid-bars.svg" width="16" height="16" alt=""> | `menu` | `fa-solid fa-bars` | | Crudit, mockup (open the menu) |
| <img src="images/icons/solid-chevron-down.svg" width="16" height="16" alt=""> | `dropdown` | `fa-solid fa-chevron-down` | | Crudit (`chevron-down`, `chevron-up`), mockup (menus, sections, history). Rotated with CSS when open |
| <img src="images/icons/solid-chevron-right.svg" width="16" height="16" alt=""> | `forward` | `fa-solid fa-chevron-right` | yes | mockup (breadcrumb separator, submenu) |
| <img src="images/icons/solid-angles-left.svg" width="16" height="16" alt=""> | `sidebar-toggle` | `fa-solid fa-angles-left` | yes | mockup (reduce the menu to icons and back). Mirrored with CSS when the menu is reduced |
| <img src="images/icons/solid-backward-step.svg" width="16" height="16" alt=""> | `first` | `fa-solid fa-backward-step` | yes | Crudit (`angle-double-left`), mockup (pagination) |
| <img src="images/icons/solid-arrow-left.svg" width="16" height="16" alt=""> | `back` | `fa-solid fa-arrow-left` | yes | Crudit (`arrow-circle-left`, `angle-left`), mockup (breadcrumb, pagination) |
| <img src="images/icons/solid-arrow-right.svg" width="16" height="16" alt=""> | `next` | `fa-solid fa-arrow-right` | yes | Crudit (`angle-right`, history: old value → new value), mockup (pagination, widget link) |
| <img src="images/icons/solid-forward-step.svg" width="16" height="16" alt=""> | `last` | `fa-solid fa-forward-step` | yes | Crudit (`angle-double-right`), mockup (pagination) |
| <img src="images/icons/solid-arrow-up.svg" width="16" height="16" alt=""> | `arrow-up` | `fa-solid fa-arrow-up` | | Crudit (`angle-up`, back to top), mockup (back to top, move up in a collection or cards) |
| <img src="images/icons/solid-arrow-down.svg" width="16" height="16" alt=""> | `arrow-down` | `fa-solid fa-arrow-down` | | mockup (move down in a collection or cards) |
| <img src="images/icons/solid-sort.svg" width="16" height="16" alt=""> | `sort` | `fa-solid fa-sort` | | Crudit, mockup |
| <img src="images/icons/solid-sort-up.svg" width="16" height="16" alt=""> | `sort-asc` | `fa-solid fa-sort-up` | | Crudit, mockup |
| <img src="images/icons/solid-sort-down.svg" width="16" height="16" alt=""> | `sort-desc` | `fa-solid fa-sort-down` | | Crudit, mockup |

### Status and feedback

| | Name | Font Awesome 7 | RTL | Used in |
|---|---|---|---|---|
| <img src="images/icons/solid-circle-check.svg" width="16" height="16" alt=""> | `success` | `fa-solid fa-circle-check` | | mockup (toasts) |
| <img src="images/icons/solid-circle-info.svg" width="16" height="16" alt=""> | `info` | `fa-solid fa-circle-info` | | Crudit (`info-circle`, help tooltips), mockup |
| <img src="images/icons/solid-triangle-exclamation.svg" width="16" height="16" alt=""> | `warning` | `fa-solid fa-triangle-exclamation` | | Crudit (`exclamation-triangle`), mockup |
| <img src="images/icons/solid-circle-exclamation.svg" width="16" height="16" alt=""> | `error` | `fa-solid fa-circle-exclamation` | | mockup (form errors) |
| <img src="images/icons/solid-check.svg" width="16" height="16" alt=""> | `check` | `fa-solid fa-check` | | Crudit (batch, fields), mockup (selected item, boolean) |
| <img src="images/icons/solid-spinner.svg" width="16" height="16" alt=""> | `loading` | `fa-solid fa-spinner fa-spin-pulse` | | mockup |
| <img src="images/icons/solid-inbox.svg" width="16" height="16" alt=""> | `empty` | `fa-solid fa-inbox` | | mockup (empty list) |
| <img src="images/icons/solid-lock.svg" width="16" height="16" alt=""> | `locked` | `fa-solid fa-lock` | | mockup (read-only record, no access) |
| <img src="images/icons/solid-bell.svg" width="16" height="16" alt=""> | `notifications` | `fa-solid fa-bell` | | Crudit, mockup |

### User and header

| | Name | Font Awesome 7 | RTL | Used in |
|---|---|---|---|---|
| <img src="images/icons/solid-user.svg" width="16" height="16" alt=""> | `user` | `fa-solid fa-user` | | Crudit (user menu, history), mockup |
| <img src="images/icons/solid-right-from-bracket.svg" width="16" height="16" alt=""> | `logout` | `fa-solid fa-right-from-bracket` | yes | Crudit (`sign-out-alt`), mockup |
| <img src="images/icons/solid-user-secret.svg" width="16" height="16" alt=""> | `impersonate` | `fa-solid fa-user-secret` | | mockup (impersonation banner) |
| <img src="images/icons/solid-globe.svg" width="16" height="16" alt=""> | `language` | `fa-solid fa-globe` | | mockup |
| <img src="images/icons/solid-sun.svg" width="16" height="16" alt=""> | `theme-light` | `fa-solid fa-sun` | | mockup |
| <img src="images/icons/solid-moon.svg" width="16" height="16" alt=""> | `theme-dark` | `fa-solid fa-moon` | | mockup |
| <img src="images/icons/solid-desktop.svg" width="16" height="16" alt=""> | `theme-system` | `fa-solid fa-desktop` | | mockup |
| <img src="images/icons/solid-envelope.svg" width="16" height="16" alt=""> | `email` | `fa-solid fa-envelope` | | Crudit (messages), mockup |

### History, dates and fields

| | Name | Font Awesome 7 | RTL | Used in |
|---|---|---|---|---|
| <img src="images/icons/solid-folder-plus.svg" width="16" height="16" alt=""> | `history-create` | `fa-solid fa-folder-plus` | | Crudit (history) |
| <img src="images/icons/solid-arrow-right-arrow-left.svg" width="16" height="16" alt=""> | `history-transition` | `fa-solid fa-arrow-right-arrow-left` | | Crudit (`exchange`), mockup (workflow transition) |
| <img src="images/icons/regular-clock.svg" width="16" height="16" alt=""> | `time` | `fa-regular fa-clock` | | Crudit (history) |
| <img src="images/icons/solid-calendar-days.svg" width="16" height="16" alt=""> | `calendar` | `fa-solid fa-calendar-days` | | mockup (period picker) |
| <img src="images/icons/solid-repeat.svg" width="16" height="16" alt=""> | `repeat` | `fa-solid fa-repeat` | | mockup (cron field) |
| <img src="images/icons/solid-file-lines.svg" width="16" height="16" alt=""> | `file` | `fa-solid fa-file-lines` | | mockup (file upload) |
| <img src="images/icons/solid-file-csv.svg" width="16" height="16" alt=""> | `file-csv` | `fa-solid fa-file-csv` | | mockup (export) |
| <img src="images/icons/solid-file-excel.svg" width="16" height="16" alt=""> | `file-excel` | `fa-solid fa-file-excel` | | mockup (export) |
| <img src="images/icons/solid-file-pdf.svg" width="16" height="16" alt=""> | `file-pdf` | `fa-solid fa-file-pdf` | | mockup (export) |

### Rich text editor

| | Name | Font Awesome 7 | RTL | Used in |
|---|---|---|---|---|
| <img src="images/icons/solid-bold.svg" width="16" height="16" alt=""> | `bold` | `fa-solid fa-bold` | | mockup |
| <img src="images/icons/solid-italic.svg" width="16" height="16" alt=""> | `italic` | `fa-solid fa-italic` | | mockup |
| <img src="images/icons/solid-list-ul.svg" width="16" height="16" alt=""> | `bulleted-list` | `fa-solid fa-list-ul` | yes | mockup |
| <img src="images/icons/solid-list-ol.svg" width="16" height="16" alt=""> | `numbered-list` | `fa-solid fa-list-ol` | yes | mockup |
| <img src="images/icons/solid-quote-right.svg" width="16" height="16" alt=""> | `quote` | `fa-solid fa-quote-right` | | mockup |

`link` is in [Actions](#actions).

### Filter operators

| | Name | Font Awesome 7 | RTL | Used in |
|---|---|---|---|---|
| <img src="images/icons/solid-equals.svg" width="16" height="16" alt=""> | `operator-equals` | `fa-solid fa-equals` | | Crudit (filter types), mockup |
| <img src="images/icons/solid-not-equal.svg" width="16" height="16" alt=""> | `operator-not-equals` | `fa-solid fa-not-equal` | | Crudit, mockup |
| <img src="images/icons/solid-less-than.svg" width="16" height="16" alt=""> | `operator-less-than` | `fa-solid fa-less-than` | | Crudit, mockup |
| <img src="images/icons/solid-less-than-equal.svg" width="16" height="16" alt=""> | `operator-less-or-equal` | `fa-solid fa-less-than-equal` | | mockup |
| <img src="images/icons/solid-greater-than.svg" width="16" height="16" alt=""> | `operator-greater-than` | `fa-solid fa-greater-than` | | Crudit, mockup |
| <img src="images/icons/solid-greater-than-equal.svg" width="16" height="16" alt=""> | `operator-greater-or-equal` | `fa-solid fa-greater-than-equal` | | mockup |
| <img src="images/icons/solid-arrows-left-right.svg" width="16" height="16" alt=""> | `operator-between` | `fa-solid fa-arrows-left-right` | | Crudit (`arrows-alt-h`), mockup |
| <img src="images/icons/solid-text-width.svg" width="16" height="16" alt=""> | `operator-contains` | `fa-solid fa-text-width` | | Crudit, mockup |
| <img src="images/icons/regular-square-caret-right.svg" width="16" height="16" alt=""> | `operator-starts-with` | `fa-regular fa-square-caret-right` | yes | Crudit (`caret-square-right`), mockup |
| <img src="images/icons/regular-square-caret-left.svg" width="16" height="16" alt=""> | `operator-ends-with` | `fa-regular fa-square-caret-left` | yes | Crudit (`caret-square-left`), mockup |
| <img src="images/icons/regular-square.svg" width="16" height="16" alt=""> | `operator-empty` | `fa-regular fa-square` | | Crudit, mockup |
| <img src="images/icons/solid-square.svg" width="16" height="16" alt=""> | `operator-not-empty` | `fa-solid fa-square` | | Crudit, mockup |

### Dashboard

| | Name | Font Awesome 7 | RTL | Used in |
|---|---|---|---|---|
| <img src="images/icons/solid-arrow-trend-up.svg" width="16" height="16" alt=""> | `trend-up` | `fa-solid fa-arrow-trend-up` | yes | mockup (KPI variation) |
| <img src="images/icons/solid-arrow-trend-down.svg" width="16" height="16" alt=""> | `trend-down` | `fa-solid fa-arrow-trend-down` | yes | mockup (KPI variation) |
| <img src="images/icons/solid-table.svg" width="16" height="16" alt=""> | `table` | `fa-solid fa-table` | | mockup (show a chart as a table) |

The checklist widget uses `success`, `warning` and `error`.

## Not in the catalogue

- **Icons chosen by the projects** (menu entries, actions, tabs, search results: `building`, `truck`,
  `helmet-safety`…): the project passes their Font Awesome class to `ui_icon()`.
- **Crudit icons that disappear with the redesign**: the sample notifications of the `sb_admin` layout (`donate`,
  `file-alt`).
- **Mockup-only icons**: the mockup panel and the placeholder pages (`compass`, `layer-group`).
