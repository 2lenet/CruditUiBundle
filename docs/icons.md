# Icons

Templates never write an icon class. They render an icon from its **logical name**, which describes what the icon
means, not what it looks like. Arrows and chevrons (except `dropdown`) are the exception: the same drawing serves
many purposes (back, previous page, old value → new value…), so they get a generic name (`back`, `next`, `forward`,
`arrow-up`…). **One drawing has one name**, shared by every place that uses it:

```twig
{{ ui_icon('delete') }}
```

Icons are SVG rendered by [Symfony UX Icons](https://symfony.com/bundles/ux-icons/current/index.html). The project
chooses its **icon library** (`lle_crudit_ui.icons.library`, Font Awesome 7 by default) and the bundle builds the
Iconify name of each logical name from it (`prefix:name`): `delete` is `fa7-solid:trash` with Font Awesome,
`lucide:trash` with Lucide. The table of each library is in [Icon libraries](icon-libraries.md); the catalogue below
shows the Font Awesome one.

The icons chosen by the projects (actions, menu, tabs) are not in the catalogue: the project passes their Iconify
name as is, from any icon set ([ux.symfony.com/icons](https://ux.symfony.com/icons)).

```twig
{{ ui_icon('lucide:truck') }}
```

`lle_crudit_ui.icons.map` replaces any icon by another Iconify name, keyed by logical name for the catalogue and by
Iconify name for the icons passed by the project (see [Configuration](configuration.md#icons)).

An icon that cannot be rendered (unknown name, icon missing from the library, Iconify name that does not exist)
does not break the page: `ui_icon()` renders nothing and logs a warning.

Logical names are in kebab-case (`external-link`, `sort-asc`). **RTL** marks the directional icons: they are
mirrored when the page is right-to-left (`dir="rtl"`), whatever the library: `ui_icon()` gives them the
`crudit-ui-icon--mirror` class.

## Catalogue

The previews are the SVG files of [Font Awesome Free](https://fontawesome.com) 7.3.1 (icons under
[CC BY 4.0](https://creativecommons.org/licenses/by/4.0/)), in grey so that they show on light and dark themes. They
are not mirrored: the RTL column says which icons are.

### Actions

| | Name | Font Awesome 7 (default) | RTL | Used in |
|---|---|---|---|---|
| <img src="images/icons/solid-plus.svg" width="16" height="16" alt=""> | `add` | `fa7-solid:plus` | | Crudit (new, add a filter, collection), mockup |
| <img src="images/icons/solid-pen.svg" width="16" height="16" alt=""> | `edit` | `fa7-solid:pen` | | Crudit (`edit`), mockup |
| <img src="images/icons/solid-trash.svg" width="16" height="16" alt=""> | `delete` | `fa7-solid:trash` | | Crudit (`trash-alt`), mockup |
| <img src="images/icons/solid-minus.svg" width="16" height="16" alt=""> | `remove` | `fa7-solid:minus` | | Crudit (fewer filters) |
| <img src="images/icons/solid-floppy-disk.svg" width="16" height="16" alt=""> | `save` | `fa7-solid:floppy-disk` | | mockup (form, tab form) |
| <img src="images/icons/solid-rotate-left.svg" width="16" height="16" alt=""> | `undo` | `fa7-solid:rotate-left` | yes | mockup (reset the form) |
| <img src="images/icons/solid-rotate.svg" width="16" height="16" alt=""> | `refresh` | `fa7-solid:rotate` | | mockup (list, dashboard) |
| <img src="images/icons/solid-play.svg" width="16" height="16" alt=""> | `resume` | `fa7-solid:play` | | mockup (resume the automatic refresh of a list) |
| <img src="images/icons/solid-xmark.svg" width="16" height="16" alt=""> | `close` | `fa7-solid:xmark` | | Crudit (`close`, `window-close`), mockup |
| <img src="images/icons/solid-magnifying-glass.svg" width="16" height="16" alt=""> | `search` | `fa7-solid:magnifying-glass` | | Crudit (search, show action), mockup |
| <img src="images/icons/solid-filter.svg" width="16" height="16" alt=""> | `filter` | `fa7-solid:filter` | | Crudit, mockup |
| <img src="images/icons/solid-filter-circle-xmark.svg" width="16" height="16" alt=""> | `filter-clear` | `fa7-solid:filter-circle-xmark` | | mockup (no result with the current filters) |
| <img src="images/icons/regular-bookmark.svg" width="16" height="16" alt=""> | `bookmark` | `fa7-regular:bookmark` | | mockup (save the filters) |
| <img src="images/icons/solid-bookmark.svg" width="16" height="16" alt=""> | `bookmark-active` | `fa7-solid:bookmark` | | mockup (saved filters in use) |
| <img src="images/icons/solid-sliders.svg" width="16" height="16" alt=""> | `settings` | `fa7-solid:sliders` | | mockup (list columns, widget settings) |
| <img src="images/icons/solid-ellipsis-vertical.svg" width="16" height="16" alt=""> | `more` | `fa7-solid:ellipsis-vertical` | | Crudit (kebab menu), mockup |
| <img src="images/icons/solid-grip-vertical.svg" width="16" height="16" alt=""> | `drag` | `fa7-solid:grip-vertical` | | Crudit (sortable rows), mockup |
| <img src="images/icons/solid-print.svg" width="16" height="16" alt=""> | `print` | `fa7-solid:print` | | mockup |
| <img src="images/icons/solid-copy.svg" width="16" height="16" alt=""> | `copy` | `fa7-solid:copy` | | mockup |
| <img src="images/icons/solid-download.svg" width="16" height="16" alt=""> | `download` | `fa7-solid:download` | | Crudit (file widget, `file-export` action), mockup (export, file upload) |
| <img src="images/icons/solid-cloud-arrow-up.svg" width="16" height="16" alt=""> | `upload` | `fa7-solid:cloud-arrow-up` | | mockup (file upload, data import widget) |
| <img src="images/icons/solid-paper-plane.svg" width="16" height="16" alt=""> | `send` | `fa7-solid:paper-plane` | yes | mockup |
| <img src="images/icons/solid-link.svg" width="16" height="16" alt=""> | `link` | `fa7-solid:link` | | mockup (rich text editor) |
| <img src="images/icons/solid-arrow-up-right-from-square.svg" width="16" height="16" alt=""> | `external-link` | `fa7-solid:arrow-up-right-from-square` | yes | Crudit (`external-link-alt`), mockup |

### Navigation

| | Name | Font Awesome 7 (default) | RTL | Used in |
|---|---|---|---|---|
| <img src="images/icons/solid-bars.svg" width="16" height="16" alt=""> | `menu` | `fa7-solid:bars` | | Crudit, mockup (open the menu) |
| <img src="images/icons/solid-chevron-down.svg" width="16" height="16" alt=""> | `dropdown` | `fa7-solid:chevron-down` | | Crudit (`chevron-down`, `chevron-up`), mockup (menus, sections, history). Rotated with CSS when open |
| <img src="images/icons/solid-chevron-right.svg" width="16" height="16" alt=""> | `forward` | `fa7-solid:chevron-right` | yes | mockup (breadcrumb separator, submenu) |
| <img src="images/icons/solid-angles-left.svg" width="16" height="16" alt=""> | `sidebar-toggle` | `fa7-solid:angles-left` | yes | mockup (reduce the menu to icons and back). Mirrored with CSS when the menu is reduced |
| <img src="images/icons/solid-backward-step.svg" width="16" height="16" alt=""> | `first` | `fa7-solid:backward-step` | yes | Crudit (`angle-double-left`), mockup (pagination) |
| <img src="images/icons/solid-arrow-left.svg" width="16" height="16" alt=""> | `back` | `fa7-solid:arrow-left` | yes | Crudit (`arrow-circle-left`, `angle-left`), mockup (breadcrumb, pagination) |
| <img src="images/icons/solid-arrow-right.svg" width="16" height="16" alt=""> | `next` | `fa7-solid:arrow-right` | yes | Crudit (`angle-right`, history: old value → new value), mockup (pagination, widget link) |
| <img src="images/icons/solid-forward-step.svg" width="16" height="16" alt=""> | `last` | `fa7-solid:forward-step` | yes | Crudit (`angle-double-right`), mockup (pagination) |
| <img src="images/icons/solid-arrow-up.svg" width="16" height="16" alt=""> | `arrow-up` | `fa7-solid:arrow-up` | | Crudit (`angle-up`, back to top), mockup (back to top, move up in a collection or cards) |
| <img src="images/icons/solid-arrow-down.svg" width="16" height="16" alt=""> | `arrow-down` | `fa7-solid:arrow-down` | | mockup (move down in a collection or cards) |
| <img src="images/icons/solid-sort.svg" width="16" height="16" alt=""> | `sort` | `fa7-solid:sort` | | Crudit, mockup |
| <img src="images/icons/solid-sort-up.svg" width="16" height="16" alt=""> | `sort-asc` | `fa7-solid:sort-up` | | Crudit, mockup |
| <img src="images/icons/solid-sort-down.svg" width="16" height="16" alt=""> | `sort-desc` | `fa7-solid:sort-down` | | Crudit, mockup |

### Status and feedback

| | Name | Font Awesome 7 (default) | RTL | Used in |
|---|---|---|---|---|
| <img src="images/icons/solid-circle-check.svg" width="16" height="16" alt=""> | `success` | `fa7-solid:circle-check` | | mockup (toasts) |
| <img src="images/icons/solid-circle-info.svg" width="16" height="16" alt=""> | `info` | `fa7-solid:circle-info` | | Crudit (`info-circle`, help tooltips), mockup |
| <img src="images/icons/solid-triangle-exclamation.svg" width="16" height="16" alt=""> | `warning` | `fa7-solid:triangle-exclamation` | | Crudit (`exclamation-triangle`), mockup |
| <img src="images/icons/solid-circle-exclamation.svg" width="16" height="16" alt=""> | `error` | `fa7-solid:circle-exclamation` | | mockup (form errors) |
| <img src="images/icons/solid-check.svg" width="16" height="16" alt=""> | `check` | `fa7-solid:check` | | Crudit (batch, fields), mockup (selected item, boolean) |
| <img src="images/icons/solid-spinner.svg" width="16" height="16" alt=""> | `loading` | `fa7-solid:spinner` | | mockup |
| <img src="images/icons/solid-inbox.svg" width="16" height="16" alt=""> | `empty` | `fa7-solid:inbox` | | mockup (empty list) |
| <img src="images/icons/solid-lock.svg" width="16" height="16" alt=""> | `locked` | `fa7-solid:lock` | | mockup (read-only record, no access) |
| <img src="images/icons/solid-bell.svg" width="16" height="16" alt=""> | `notifications` | `fa7-solid:bell` | | Crudit, mockup |

### User and header

| | Name | Font Awesome 7 (default) | RTL | Used in |
|---|---|---|---|---|
| <img src="images/icons/solid-user.svg" width="16" height="16" alt=""> | `user` | `fa7-solid:user` | | Crudit (user menu, history), mockup |
| <img src="images/icons/solid-right-from-bracket.svg" width="16" height="16" alt=""> | `logout` | `fa7-solid:right-from-bracket` | yes | Crudit (`sign-out-alt`), mockup |
| <img src="images/icons/solid-user-secret.svg" width="16" height="16" alt=""> | `impersonate` | `fa7-solid:user-secret` | | mockup (impersonation banner) |
| <img src="images/icons/solid-globe.svg" width="16" height="16" alt=""> | `language` | `fa7-solid:globe` | | mockup |
| <img src="images/icons/solid-sun.svg" width="16" height="16" alt=""> | `theme-light` | `fa7-solid:sun` | | mockup |
| <img src="images/icons/solid-moon.svg" width="16" height="16" alt=""> | `theme-dark` | `fa7-solid:moon` | | mockup |
| <img src="images/icons/solid-desktop.svg" width="16" height="16" alt=""> | `theme-system` | `fa7-solid:desktop` | | mockup |
| <img src="images/icons/solid-envelope.svg" width="16" height="16" alt=""> | `email` | `fa7-solid:envelope` | | Crudit (messages), mockup |

### History, dates and fields

| | Name | Font Awesome 7 (default) | RTL | Used in |
|---|---|---|---|---|
| <img src="images/icons/solid-folder-plus.svg" width="16" height="16" alt=""> | `history-create` | `fa7-solid:folder-plus` | | Crudit (history) |
| <img src="images/icons/solid-arrow-right-arrow-left.svg" width="16" height="16" alt=""> | `history-transition` | `fa7-solid:arrow-right-arrow-left` | | Crudit (`exchange`), mockup (workflow transition) |
| <img src="images/icons/regular-clock.svg" width="16" height="16" alt=""> | `time` | `fa7-regular:clock` | | Crudit (history) |
| <img src="images/icons/solid-calendar-days.svg" width="16" height="16" alt=""> | `calendar` | `fa7-solid:calendar-days` | | mockup (period picker) |
| <img src="images/icons/solid-repeat.svg" width="16" height="16" alt=""> | `repeat` | `fa7-solid:repeat` | | mockup (cron field) |
| <img src="images/icons/solid-file-lines.svg" width="16" height="16" alt=""> | `file` | `fa7-solid:file-lines` | | mockup (file upload) |
| <img src="images/icons/solid-file-csv.svg" width="16" height="16" alt=""> | `file-csv` | `fa7-solid:file-csv` | | mockup (export) |
| <img src="images/icons/solid-file-excel.svg" width="16" height="16" alt=""> | `file-excel` | `fa7-solid:file-excel` | | mockup (export) |
| <img src="images/icons/solid-file-pdf.svg" width="16" height="16" alt=""> | `file-pdf` | `fa7-solid:file-pdf` | | mockup (export) |

### Rich text editor

| | Name | Font Awesome 7 (default) | RTL | Used in |
|---|---|---|---|---|
| <img src="images/icons/solid-bold.svg" width="16" height="16" alt=""> | `bold` | `fa7-solid:bold` | | mockup |
| <img src="images/icons/solid-italic.svg" width="16" height="16" alt=""> | `italic` | `fa7-solid:italic` | | mockup |
| <img src="images/icons/solid-list-ul.svg" width="16" height="16" alt=""> | `bulleted-list` | `fa7-solid:list-ul` | yes | mockup |
| <img src="images/icons/solid-list-ol.svg" width="16" height="16" alt=""> | `numbered-list` | `fa7-solid:list-ol` | yes | mockup |
| <img src="images/icons/solid-quote-right.svg" width="16" height="16" alt=""> | `quote` | `fa7-solid:quote-right` | | mockup |

`link` is in [Actions](#actions).

### Filter operators

| | Name | Font Awesome 7 (default) | RTL | Used in |
|---|---|---|---|---|
| <img src="images/icons/solid-equals.svg" width="16" height="16" alt=""> | `operator-equals` | `fa7-solid:equals` | | Crudit (filter types), mockup |
| <img src="images/icons/solid-not-equal.svg" width="16" height="16" alt=""> | `operator-not-equals` | `fa7-solid:not-equal` | | Crudit, mockup |
| <img src="images/icons/solid-less-than.svg" width="16" height="16" alt=""> | `operator-less-than` | `fa7-solid:less-than` | | Crudit, mockup |
| <img src="images/icons/solid-less-than-equal.svg" width="16" height="16" alt=""> | `operator-less-or-equal` | `fa7-solid:less-than-equal` | | mockup |
| <img src="images/icons/solid-greater-than.svg" width="16" height="16" alt=""> | `operator-greater-than` | `fa7-solid:greater-than` | | Crudit, mockup |
| <img src="images/icons/solid-greater-than-equal.svg" width="16" height="16" alt=""> | `operator-greater-or-equal` | `fa7-solid:greater-than-equal` | | mockup |
| <img src="images/icons/solid-arrows-left-right.svg" width="16" height="16" alt=""> | `operator-between` | `fa7-solid:arrows-left-right` | | Crudit (`arrows-alt-h`), mockup |
| <img src="images/icons/solid-text-width.svg" width="16" height="16" alt=""> | `operator-contains` | `fa7-solid:text-width` | | Crudit, mockup |
| <img src="images/icons/regular-square-caret-right.svg" width="16" height="16" alt=""> | `operator-starts-with` | `fa7-regular:square-caret-right` | yes | Crudit (`caret-square-right`), mockup |
| <img src="images/icons/regular-square-caret-left.svg" width="16" height="16" alt=""> | `operator-ends-with` | `fa7-regular:square-caret-left` | yes | Crudit (`caret-square-left`), mockup |
| <img src="images/icons/regular-square.svg" width="16" height="16" alt=""> | `operator-empty` | `fa7-regular:square` | | Crudit, mockup |
| <img src="images/icons/solid-square.svg" width="16" height="16" alt=""> | `operator-not-empty` | `fa7-solid:square` | | Crudit, mockup |

### Dashboard

| | Name | Font Awesome 7 (default) | RTL | Used in |
|---|---|---|---|---|
| <img src="images/icons/solid-arrow-trend-up.svg" width="16" height="16" alt=""> | `trend-up` | `fa7-solid:arrow-trend-up` | yes | mockup (KPI variation) |
| <img src="images/icons/solid-arrow-trend-down.svg" width="16" height="16" alt=""> | `trend-down` | `fa7-solid:arrow-trend-down` | yes | mockup (KPI variation) |
| <img src="images/icons/solid-table.svg" width="16" height="16" alt=""> | `table` | `fa7-solid:table` | | mockup (show a chart as a table) |

The checklist widget uses `success`, `warning` and `error`.

## Not in the catalogue

- **Icons chosen by the projects** (menu entries, actions, tabs, search results: `building`, `truck`,
  `helmet-safety`…): the project passes their Iconify name to `ui_icon()` (`lucide:truck`); the Font Awesome classes
  (`fa fa-truck`, `fas fa-truck`) are no longer understood.
- **Crudit icons that disappear with the redesign**: the sample notifications of the `sb_admin` layout (`donate`,
  `file-alt`).
- **Mockup-only icons**: the mockup panel and the placeholder pages (`compass`, `layer-group`).
