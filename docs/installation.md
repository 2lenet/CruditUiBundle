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

## Icons

The bundle loads Font Awesome 7. To use another icon library, see [Configuration](configuration.md#icons).
