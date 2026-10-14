# Installation

## Bundle

```bash
composer require 2lenet/crudit-ui-bundle
```

The bundle is registered in `config/bundles.php`:

```php
Lle\CruditUiBundle\LleCruditUiBundle::class => ['all' => true],
```

## Assets

The Stimulus controllers and the compiled CSS are delivered as a [Symfony UX](https://symfony.com/bundles/StimulusBundle/current/index.html)
package (`assets/package.json`, `@2lenet/crudit-ui-bundle`).

- **AssetMapper**: nothing to do, the bundle registers its `assets/dist` folder.
- **Webpack Encore**: the controllers are enabled with `assets/controllers.json`, as for any Symfony UX package.

The Flex recipe (`2lenet/flex`) creates `config/packages/lle_crudit_ui.yaml`. On a new project, Flex also creates
`assets/controllers.json`, enables `enableStimulusBridge()` and adds `@2lenet/crudit-ui-bundle` to `package.json`.

### Stylesheet

The stylesheet is **not** loaded by the Stimulus controllers (disabling a controller to replace it would remove it).
Link it explicitly in your layout:

```twig
{# AssetMapper #}
<link rel="stylesheet" href="{{ asset('@2lenet/crudit-ui-bundle/ui.css') }}">
```

```js
// Webpack Encore, in your entry point (assets/app.js)
import '@2lenet/crudit-ui-bundle/dist/ui.css';
```

All the rules are in two cascade layers, `crudit-ui-base` and `crudit-ui`: your own styles always win, whatever the
order of the stylesheets. If your project keeps Bootstrap, load it in its own layer first: see
[Keeping Bootstrap](customization.md#keeping-bootstrap).

### Language, direction and theme

Put the language, the direction and the theme chosen by the user on `<html>`, in your layout:

- `ui_lang()` gives the locale of the request in the form of the `lang` attribute (`ar-MA`);
- `ui_dir()` gives `rtl` for a language written from right to left, and the CSS mirrors the layout (see
  [Languages](languages.md));
- `ui_theme()` displays the page in the right theme from the first paint (see [Dark mode](customization.md#dark-mode)).
  Without a choice, the CSS follows the system.

```twig
<html lang="{{ ui_lang() }}" dir="{{ ui_dir() }}"{% if ui_theme() %} data-theme="{{ ui_theme() }}"{% endif %}>
```

### After the installation

AssetMapper and Encore read `controllers.json` once: run `bin/console cache:clear` after `composer require`
(AssetMapper does not see a `controllers.json` created or changed before), and rebuild with Encore.

### Project created before Stimulus (Encore)

Flex does not create the Stimulus configuration of a project that did not have the Stimulus recipe. Add:

```json
// assets/controllers.json
{
    "controllers": {
        "@2lenet/crudit-ui-bundle": {
            "dropdown": {"enabled": true, "fetch": "eager"}
        }
    },
    "entrypoints": []
}
```

```js
// assets/bootstrap.js, imported by your entry point (assets/app.js)
import { startStimulusApp } from '@symfony/stimulus-bridge';

export const app = startStimulusApp(require.context(
    '@symfony/stimulus-bridge/lazy-controller-loader!./controllers',
    true,
    /\.[jt]sx?$/
));
```

```js
// webpack.config.js
Encore.enableStimulusBridge('./assets/controllers.json')
```

`tests/apps/encore` is a complete example. Keep the controllers of the bundle in `controllers.json`: a project that
does not list them does not have them.

## Icons

The icons are SVG rendered by [Symfony UX Icons](https://symfony.com/bundles/ux-icons/current/index.html): no icon
font to link. Choose the icon library of the project (Font Awesome 7 by default, see
[Icon libraries](icon-libraries.md) and [Configuration](configuration.md#icons)).

The icons come from [Iconify](https://iconify.design). In development, Symfony UX Icons downloads them, which
requires the HTTP client, and `auto_lock` copies each displayed icon to `assets/icons/`, including the names built
dynamically (icons of a `CrudConfig`, of the database…) that `ux:icons:lock` cannot find:

```bash
composer require symfony/http-client
```

```yaml
# config/packages/ux_icons.yaml
when@dev:
    ux_icons:
        iconify:
            auto_lock: true
```

The web server must be able to write to `assets/icons/` in development (with Docker, the folder created by
Composer often belongs to `root`): otherwise the log shows `Could not auto-lock icon` and nothing is copied, although
the icons are displayed.

**Commit `assets/icons/`**: in production, the icons are read from it, without calling Iconify. Display the screens
of the project in development after changing the library or adding an icon, so that its SVG is copied. The icon
names are listed in [Icons](icons.md).
