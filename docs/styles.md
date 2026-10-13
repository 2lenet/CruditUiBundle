# Base styles, grid and utilities

The stylesheet of the bundle replaces Bootstrap in the templates of the 2LE bundles: its own reset, a grid in CSS grid
and a small set of utilities. All the classes take the `crudit-ui-` prefix, so they never collide with the classes of
Bootstrap or of your project. The spacing values are the tokens of the [theme](customization.md#theme).

## Reset and elements

The bare HTML elements (`body`, headings, links, lists, tables, `code`, `kbd`, `mark`…) get a neutral reset and the
look of the design system: font, colours and focus ring come from the tokens, in the light and in the dark theme.
The form controls keep their native look: the form components style them.

## Grid

`crudit-ui-grid` has 12 columns. A child takes the whole width, or `crudit-ui-col-<n>` columns; from a breakpoint
(`sm`, `md`, `lg`, `xl`, `xxl`, see `styles/settings/_breakpoints.scss`) with `crudit-ui-col-<breakpoint>-<n>`,
mobile first:

```html
<div class="crudit-ui-grid">
    <div class="crudit-ui-col-lg-6">Full width, half from lg</div>
    <div class="crudit-ui-col-lg-6">Full width, half from lg</div>
    <div class="crudit-ui-col-6 crudit-ui-col-xl-3">Half, quarter from xl</div>
</div>
```

`crudit-ui-grid--auto` puts as many equal columns as fit, each at least `--crudit-ui-grid-min` wide (16rem by
default), without a breakpoint:

```html
<div class="crudit-ui-grid crudit-ui-grid--auto" style="--crudit-ui-grid-min: 12rem">…</div>
```

The gap is `--crudit-ui-grid-gap` (`--space-4` by default).

| Bootstrap | CruditUiBundle |
|---|---|
| `row` | `crudit-ui-grid` |
| `col-12`, `col` alone in its row | nothing (whole width by default) |
| `col-lg-6` | `crudit-ui-col-lg-6` |
| several `col` of equal width | `crudit-ui-grid crudit-ui-grid--auto` |
| `g-3` | `style="--crudit-ui-grid-gap: var(--space-3)"` |

## Utilities

The utilities come after the components: at equal specificity, they win.

**Spacing**, on the scale of the tokens: `0` and `1` to `8` (`--space-1` to `--space-8`), and `auto` for the margins.
The sides are logical, as in Bootstrap 5: `s` (start) and `e` (end) are left and right in a left-to-right page, and the
other way round in a right-to-left page.

| Class | Property |
|---|---|
| `crudit-ui-m-<n>`, `crudit-ui-p-<n>` | `margin`, `padding` |
| `crudit-ui-mt-<n>`, `crudit-ui-mb-<n>` | top, bottom (`margin-block-start`, `-end`) |
| `crudit-ui-ms-<n>`, `crudit-ui-me-<n>` | start, end (`margin-inline-start`, `-end`) |
| `crudit-ui-mx-<n>`, `crudit-ui-my-<n>` | both sides (`margin-inline`, `margin-block`) |
| `crudit-ui-gap-<n>` | `gap` of a grid or a flex container |

`p` instead of `m` for the paddings: `crudit-ui-px-3`, `crudit-ui-pt-2`.

**Display**: `crudit-ui-d-<value>` with `none`, `block`, `inline`, `inline-block`, `flex`, `inline-flex`, `grid`; from a
breakpoint with `crudit-ui-d-<breakpoint>-<value>` (`crudit-ui-d-none crudit-ui-d-md-flex`).

**Flex and text**: `crudit-ui-justify-{start|end|center|space-between|space-around}`,
`crudit-ui-align-{start|end|center|baseline|stretch}`, `crudit-ui-flex-wrap`, `crudit-ui-flex-nowrap`,
`crudit-ui-flex-grow`, `crudit-ui-text-{start|end|center}`, `crudit-ui-text-truncate`, `crudit-ui-w-100`.

## Accessibility

`crudit-ui-visually-hidden` hides a text visually and leaves it to the screen readers (Bootstrap: `visually-hidden`).
Every interactive element has a visible focus ring (`--c-focus`).

## Print

A printed page has the light colours, even in dark mode, and whole table rows. Each component hides its own commands
(actions, filters, pagination); `crudit-ui-print-hidden` hides any other element (Bootstrap: `d-print-none`).

## Right to left

In a page with `dir="rtl"`, the logical utilities and the directional icons follow (see [Icons](icons.md)), and each
paragraph of a `textarea` takes the direction of its first letter. A template that displays a free text (long text,
Markdown, rich HTML) gives it `dir="auto"`; a phone number, `dir="ltr"`.
