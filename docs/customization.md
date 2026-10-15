# Customization

## Theme

The theme is made of CSS variables, the design tokens (`assets/styles/generic/_tokens.scss`). Override them in your own
stylesheet, no compilation needed:

```css
:root {
    --brand: #b3261e;
    --font-sans: 'Roboto', system-ui, sans-serif;
}
```

- **`--brand`** is the only colour a project has to provide: the primary colours (`--c-primary`, its hover, active
  and subtle variants, links, focus ring, first chart series) are derived from it, in the light and in the dark theme.
  Choose a brand colour dark enough for white text (4.5:1).
- **Fonts**: `--font-sans` and `--font-mono`. The bundle loads no font file: `Inter Variable` comes first in
  `--font-sans`, and the system font is used when the project does not load it.
- **Sizes**: `--font-size-*`, `--space-1` to `--space-8` (4 px scale), `--radius-*`, `--control-h`, `--z-*`…
- **Colours**: `--c-*` (backgrounds, surfaces, borders, text, success, warning, danger, info, neutral…), defined
  twice: for the light theme on `:root`, for the dark theme on `:root[data-theme='dark']`. Override a colour in both.

### Colours of the states

The extended palette has 11 hues for the states of a workflow and for the badges: `blue`, `teal`, `green`, `lime`,
`yellow`, `orange`, `red`, `pink`, `purple`, `indigo`, `gray`. Each hue is a background and a text colour,
`--c-<hue>-bg` and `--c-<hue>-text`, with a contrast of at least 4.5:1 in both themes. Add a hue by declaring the two
variables, for each theme:

```css
:root {
    --c-brown-bg: #f3e9e2;
    --c-brown-text: #6b3e1f;
}

:root[data-theme='dark'] {
    --c-brown-bg: #2e1d12;
    --c-brown-text: #e3c2a8;
}
```

### Dark mode

The theme follows the preference of the system (`prefers-color-scheme`), in CSS. The user can choose light or dark
instead: the `theme` controller puts the choice in the `data-theme` attribute of `<html>` and in the `crudit_ui_theme`
cookie, which the layout reads with `ui_theme()` (see [Installation](installation.md#language-direction-and-theme)),
so the next pages are displayed in the right theme from the first paint. "System" removes both.

The options are buttons (`aria-pressed` follows the choice), radio buttons or a select, whose value is `light`, `dark`
or `system`:

```twig
{% set theme = '@2lenet/crudit-ui-bundle/theme' %}
<div role="group" aria-label="{{ 'crudit_ui.theme.label'|trans(domain: 'LleCruditUiBundle') }}" {{ stimulus_controller(theme) }}>
    {% for value in ['light', 'dark', 'system'] %}
        <button type="button" value="{{ value }}" {{ stimulus_target(theme, 'option') }} {{ stimulus_action(theme, 'choose') }}>
            {{ ('crudit_ui.theme.' ~ value)|trans(domain: 'LleCruditUiBundle') }}
        </button>
    {% endfor %}
</div>
```

```twig
<select {{ stimulus_controller(theme) }} {{ stimulus_target(theme, 'option') }} {{ stimulus_action(theme, 'choose', 'change') }}>…</select>
```

The controller dispatches `2lenet--crudit-ui-bundle--theme:change` (`event.detail.theme`): what draws with the colours
(a chart) reads the CSS variables again. The CSS selects the colours on its own, the rest of the page has nothing to do.

### Cascade layers

The rules of the bundle are in two cascade layers: `crudit-ui-base` (reset, tokens, bare HTML elements), then
`crudit-ui` (grid, components, utilities, always on a `crudit-ui-` class). **Your styles, which are not in a layer,
always win**, whatever the order in which the stylesheets are loaded. A rule of the bundle can be rewritten with a
plain selector:

```css
.crudit-ui-dropdown__button {
    letter-spacing: 2px;
}
```

### Keeping Bootstrap

A rule outside any layer wins over every layer, even when it is less specific: a Bootstrap loaded as usual would win
with its reboot over the components of the bundle (`button { border-radius: 0 }` over the radius of a button of the
bundle). Load Bootstrap **in a layer between the two layers of the bundle**: the classes of Bootstrap then win over the
reset of the bundle, and the components of the bundle over the reboot of Bootstrap.

The order of the layers is fixed by their first mention: declare it **before** the stylesheet of the bundle.

```css
/* assets/styles/layers.css, loaded first */
@layer crudit-ui-base, bootstrap, crudit-ui;
@import url('bootstrap/dist/css/bootstrap.min.css') layer(bootstrap);
```

```js
// Webpack Encore (assets/app.js): the order of the imports is the order of the stylesheets
import './styles/layers.css';
import '@2lenet/crudit-ui-bundle/dist/ui.css';
import './styles/app.css';
```

With Sass, load Bootstrap in the layer with `meta.load-css`:

```scss
@use 'sass:meta';

@layer crudit-ui-base, bootstrap, crudit-ui;

@layer bootstrap {
    @include meta.load-css('bootstrap/scss/bootstrap');
}
```

Your own CSS stays outside the layers and keeps winning over Bootstrap and over the bundle.

## Replacing a Stimulus controller

The identifier of a controller of the bundle is `2lenet--crudit-ui-bundle--<name>` (for example
`2lenet--crudit-ui-bundle--dropdown`). To replace it, add a controller with the same identifier in your `assets/controllers/`:
`assets/controllers/2lenet--crudit-ui-bundle--dropdown_controller.js`. The path of a controller of the bundle
(`dist/controllers/…`) is a public API: it does not change without a note in the upgrade guide.

The two asset tools do not behave the same way.

### AssetMapper

Your controller **wins alone**: the controllers of your project replace those of the bundle.

To **extend** the controller of the bundle instead of rewriting it, make it importable in `importmap.php`:

```php
'@2lenet/crudit-ui-bundle/controllers/dropdown_controller.js' => [
    'path' => '@2lenet/crudit-ui-bundle/controllers/dropdown_controller.js',
],
```

```js
import Dropdown from '@2lenet/crudit-ui-bundle/controllers/dropdown_controller.js';

export default class extends Dropdown {
    open() {
        super.open();
        // …
    }
}
```

### Webpack Encore

Your controller **loses** against the one of `controllers.json`: the Stimulus application registers yours first, then
the controllers of `controllers.json`, and the last registration replaces the previous one. **Disable the controller of
the bundle** in `controllers.json`:

```json
{
    "controllers": {
        "@2lenet/crudit-ui-bundle": {
            "dropdown": {"enabled": false}
        }
    }
}
```

Your controller can then extend it:

```js
import Dropdown from '@2lenet/crudit-ui-bundle/dist/controllers/dropdown_controller.js';

export default class extends Dropdown {
    open() {
        super.open();
        // …
    }
}
```

This is why the stylesheet is never loaded by the `autoimport` of a controller: disabling the controller would
remove it. See [Installation](installation.md#stylesheet).
