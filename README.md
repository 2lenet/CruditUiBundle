# UiBundle

Shared design system of the 2LE bundles (CruditBundle, DashboardBundle): tokens, base CSS, Twig components, Stimulus controllers, icons, languages and RTL.

UiBundle knows nothing about `CrudConfig`, bricks or the Crudit menu.

> Status: skeleton, no component yet.

## Installation

```bash
composer require 2lenet/ui-bundle
```

## Configuration

```yaml
lle_ui:
    icons:
        load_fontawesome: true   # false when the project loads its own icon library
        map:                     # icon replacements
            'fa-solid fa-trash': 'xx xx-trash'
```

## Development

```bash
composer install
vendor/bin/phpcs
vendor/bin/phpstan analyse -c phpstan.neon
vendor/bin/phpunit

cd assets && npm install && npm run build   # compiles the CSS into assets/dist
```
