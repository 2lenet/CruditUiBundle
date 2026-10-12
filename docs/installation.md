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
<link rel="stylesheet" href="{{ asset('@2lenet/crudit-ui-bundle/fontawesome.css') }}">
```

```js
// Webpack Encore, in your entry point (assets/app.js)
import '@2lenet/crudit-ui-bundle/dist/ui.css';
import '@2lenet/crudit-ui-bundle/dist/fontawesome.css';
```

`fontawesome.css` is Font Awesome 7 Free (solid and regular), with its fonts. Leave it out if the project already
loads Font Awesome or uses another icon library (see [Icons](#icons)).

All the rules are in the `crudit-ui` cascade layer: your own styles always win, whatever the order of the stylesheets.

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

The bundle ships Font Awesome 7 Free in `fontawesome.css` (see [Stylesheet](#stylesheet)). Your layout links it; the
layouts provided by the bundles (Crudit, Dashboard) link it only when the Twig global `lle_crudit_ui_load_fontawesome` is
true, which is the default:

```twig
{% if lle_crudit_ui_load_fontawesome %}
    <link rel="stylesheet" href="{{ asset('@2lenet/crudit-ui-bundle/fontawesome.css') }}">
{% endif %}
```

To use another icon library, set `load_fontawesome: false` (no bundle links Font Awesome any more) and map the
icons, see [Configuration](configuration.md#icons). The icon names are listed in [Icons](icons.md).
