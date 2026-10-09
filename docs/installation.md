# Installation

## Bundle

```bash
composer require 2lenet/ui-bundle
```

The bundle is registered in `config/bundles.php`:

```php
Lle\UiBundle\LleUiBundle::class => ['all' => true],
```

## Assets

The Stimulus controllers and the compiled CSS are delivered as a [Symfony UX](https://symfony.com/bundles/StimulusBundle/current/index.html)
package (`assets/package.json`, `@2lenet/ui-bundle`).

- **AssetMapper**: nothing to do, the bundle registers its `assets/dist` folder.
- **Webpack Encore**: the controllers are enabled with `assets/controllers.json`, as for any Symfony UX package.

The Flex recipe (`2lenet/flex`) creates `config/packages/lle_ui.yaml`. On a new project, Flex also creates
`assets/controllers.json`, enables `enableStimulusBridge()` and adds `@2lenet/ui-bundle` to `package.json`.

### Stylesheet

The stylesheet is **not** loaded by the Stimulus controllers (disabling a controller to replace it would remove it).
Link it explicitly in your layout:

```twig
{# AssetMapper #}
<link rel="stylesheet" href="{{ asset('@2lenet/ui-bundle/ui.css') }}">
```

```js
// Webpack Encore, in your entry point (assets/app.js)
import '@2lenet/ui-bundle/dist/ui.css';
```

All the rules are in the `lle-ui` cascade layer: your own styles always win, whatever the order of the stylesheets.

### Project created before Stimulus

Flex does not create the Stimulus configuration of an existing project. Add `assets/controllers.json` (listing the
controllers of `@2lenet/ui-bundle`), start the Stimulus application in your entry point (`startStimulusApp()` from
`@symfony/stimulus-bridge`) and enable `enableStimulusBridge('./assets/controllers.json')` in `webpack.config.js`.

## Icons

The bundle loads Font Awesome 7. To use another icon library, see [Configuration](configuration.md#icons).
