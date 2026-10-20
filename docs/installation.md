# Installation

## Bundle

The bundle needs PHP 8.4 and the `intl` extension of PHP (numbers and dates, see [Languages](languages.md)).

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

In development, the design kit page shows the tokens and the components of the bundle in the project: import its
routes under `when@dev` (see [Components](components.md#design-kit)).

### Project created before Stimulus (Encore)

Flex does not create the Stimulus configuration of a project that did not have the Stimulus recipe. Add:

```json
// assets/controllers.json
{
    "controllers": {
        "@2lenet/crudit-ui-bundle": {
            "alert": {"enabled": true, "fetch": "eager"},
            "button": {"enabled": true, "fetch": "eager"},
            "dialog": {"enabled": true, "fetch": "eager"},
            "disclosure": {"enabled": true, "fetch": "eager"},
            "dropdown": {"enabled": true, "fetch": "eager"},
            "tabs": {"enabled": true, "fetch": "eager"},
            "theme": {"enabled": true, "fetch": "eager"},
            "tooltip": {"enabled": true, "fetch": "eager"}
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

The icons come from [Iconify](https://iconify.design). **Before committing**, import them into `assets/icons/` with
the command of Symfony UX Icons, which downloads them and requires the HTTP client (in development only):

```bash
composer require --dev symfony/http-client
bin/console ux:icons:lock
```

**Commit `assets/icons/`**: in production, the icons are read from it, without calling Iconify. The command imports
the icons given by the *finders* (`Symfony\UX\Icons\IconFinderInterface`):

- the bundle gives every icon of the [catalogue](icons.md) in the library of the project, and the replacements of
  `lle_crudit_ui.icons.map`;
- Symfony UX Icons gives the Iconify names written in the templates (`ui_icon('lucide:truck')`);
- the icons built in PHP or stored in the database are found by nobody: the project declares them in a finder of
  its own (any service implementing the interface, autoconfigured).

```php
use Symfony\UX\Icons\IconFinderInterface;

final class CategoryIconFinder implements IconFinderInterface
{
    public function __construct(private CategoryRepository $categories) {}

    public function icons(): iterable
    {
        return $this->categories->findAllIcons(); // ['lucide:truck', 'tabler:package', ...]
    }
}
```

`auto_lock`, which the recipe of Symfony UX Icons enables in development (`when@dev` in
`config/packages/ux_icons.yaml`), is not used: remove it. It only copies the icons displayed while developing, so an
icon never displayed would be missing in production.

In development, Symfony UX Icons fetches from Iconify an icon missing from `assets/icons/` (`iconify.on_demand`): the
screens look right even when the command was forgotten. In production, the icon is then missing, or, if the HTTP client
is installed there too, fetched from Iconify at its first rendering and kept in the cache: fetched again after each
cache clear (and by `asset-map:compile` with AssetMapper). Run the command before every commit that adds an icon or
changes `lle_crudit_ui.icons`, and turn `on_demand` off in production, so that a forgotten command shows instead of
being hidden by a call to Iconify. With AssetMapper, the HTTP client is installed in production anyway
(`symfony/asset-mapper` requires it): `composer require --dev symfony/http-client` does not remove it, only
`on_demand: false` stops the calls to Iconify.

```yaml
# config/packages/ux_icons.yaml
when@prod:
    ux_icons:
        iconify:
            on_demand: false
```

The command skips the icons already in `assets/icons/` and deletes none. **After changing the library**, delete the
folder of the former one (`assets/icons/fa7-solid/` and `assets/icons/fa7-regular/` for Font Awesome; the SVG of the
project are not in these folders) and run it again.

The command reports nothing by default. An icon that Iconify does not have is only shown with `-v`, and an unknown icon
set only with `-vv`: a typo in a prefix (`tabelr:trash-x`) is a valid name, so the check of `lle_crudit_ui.icons.map`
lets it through. Check with `bin/console ux:icons:lock -vv`. The command only imports the names with exactly one `:`
(`prefix:name`); a name with more is only valid for an SVG of the project in a subfolder.
