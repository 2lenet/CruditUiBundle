# Contributing

```bash
composer install
vendor/bin/phpcs
vendor/bin/phpstan analyse -c tests/phpstan.neon
vendor/bin/phpunit -c tests/phpunit/phpunit.xml
```

## Assets

```bash
cd assets
npm install
npm run build   # styles/ui.scss -> dist/ui.css
npm run watch
npm run check   # fails when dist/ui.css is not the compilation of styles/ (run by the CI)
npm test        # tokens and contrasts, prefix of the classes, grid, utilities, breakpoints
```

`assets/dist/` is **committed**: the bundle ships the compiled CSS, projects have no build step for it.

| Path | Content |
|---|---|
| `assets/styles/` | Sass, in ITCSS order: `settings` → `tools` → `generic` → `elements` → `objects` → `components` → `utilities`. Only `ui.scss` is compiled; it loads each partial with `meta.load-css` inside its layer, so a partial is plain CSS and never declares the layer itself: `generic` and `elements` in `@layer crudit-ui-base`, the rest in `@layer crudit-ui`. |
| `assets/dist/ui.css` | The compiled stylesheet. |
| `assets/dist/controllers/` | Stimulus controllers, native ES modules (no build). Their path is public API. |
| `assets/dist/utils/` | JavaScript helpers shared by the controllers (`breakpoints.js`). |

### Conventions

- **Layers**: a rule on a bare element (`a`, `button`, `h1`…) goes in `crudit-ui-base` (`generic/`, `elements/`); every rule of `crudit-ui` targets a `crudit-ui-` class (checked by `tests/base.test.mjs`). A project that keeps Bootstrap puts it between the two: an element rule in `crudit-ui` would win over its classes.

- Classes: prefix `crudit-ui-`, BEM (`crudit-ui-dropdown__button`). Tokens keep their plain names (`--brand`, `--c-text`); a token specific to one component takes the prefix (`--crudit-ui-table-row-height`).
- **Breakpoints**: one set, `styles/settings/_breakpoints.scss`. In Sass use `@include media('>md')` / `media('<md')`; in JavaScript use `breakpoint('md')` or `matchBreakpoint('>md')` (`dist/utils/breakpoints.js`), which read the `--breakpoint-*` CSS variables. Never write a width in px in a media query or in a controller.
- JavaScript: native ES modules only, no CSS/HTML/JSON import, bare imports declared in `peerDependencies` and `symfony.importmap`.
- **Lazy loading**: a light controller is `"fetch": "eager"`; a heavy one (editor, Sortable, Chart.js…) is `"fetch": "lazy"` in `package.json`, or loads its library with a dynamic `import()`.
- The CSS is linked explicitly by the layout (never by the `autoimport` of a controller, which disappears when a project disables the controller).
- **Texts**: every text goes through the `LleCruditUiBundle` domain, with a `crudit_ui.` key in nested YAML, in the four
  locales `fr`, `en`, `de` and `it` (`tests/phpunit/Unit/TranslationsTest.php` checks the keys, the empty texts and the
  parameters). A controller has no catalogue of its own: Twig translates its texts and passes them as values
  (`stimulus_controller(name, {labels: {close: 'crudit_ui.action.close'|trans(domain: 'LleCruditUiBundle')}})`).
- **Numbers and dates**: `ui_number`, `ui_date`… never the `format_*` filters of Twig nor a locale written in the
  template (see [Languages](languages.md)).

The bundle must not depend on `2lenet/crudit-bundle` nor on `2lenet/dashboard-bundle`: it knows nothing about
`CrudConfig`, bricks or the menu.

## Test applications

`tests/apps/` holds one Symfony application per asset tool the bundle supports: `assetmapper` and `encore`.
Each one installs the bundle as a project does (Composer path repository with `symlink: false`, so the bundle is
**copied** into `vendor/`), loads the stylesheet explicitly, overrides a variable in its own CSS and shows the
dropdown controller. `tests/apps/smoke.mjs` drives a headless Chrome on the page: the stylesheet and the
breakpoint variables are loaded, the project wins over the `crudit-ui` layer, the controller opens and closes the
menu, the page in French, Arabic and Persian has the right direction, texts, numbers and dates, nothing fails to load. The `Test applications` workflow runs it for AssetMapper (dev and prod, with
`asset-map:compile`) and Encore.

To run one locally (Docker, no local PHP needed):

```bash
cd tests/apps/encore
docker run --rm -u $(id -u) -e COMPOSER_HOME=/tmp/c -v $PWD/../../..:/app -w /app/tests/apps/encore composer:2 composer install --ignore-platform-reqs
docker run --rm -u $(id -u) -e HOME=/tmp -v $PWD/../../..:/app -w /app/tests/apps/encore node:22 sh -c 'npm install && npm run build'
docker build -t php-intl - <<< $'FROM php:8.5-cli\nRUN apt-get update && apt-get install -y libicu-dev && docker-php-ext-install intl'
docker run -d --rm --name ui-app -u $(id -u) --network host -e APP_SECRET=x -v $PWD/../../..:/app -w /app/tests/apps/encore php-intl php -S 127.0.0.1:8081 -t public
cd .. && npm install && node smoke.mjs http://127.0.0.1:8081/
```

Pitfalls:

- **A change in `assets/` is not seen by an application that already copied the bundle**: copy `assets/dist/` again into `vendor/2lenet/crudit-ui-bundle/assets/dist/` (or reinstall the bundle).
- Composer copies the whole bundle, `vendor/` and `node_modules/` included, and the applications already installed with it: use a clean checkout (as the CI does) when installing a second application.
- The bundle requires the `intl` extension of PHP, which the `php:*-cli` and `composer:2` images do not have (hence
  the `php-intl` image above, also needed by PHPUnit).
- The dates depend on the version of ICU (`13/10/2026 م` in `ar_SA` since ICU 74, era included): the tests check
  their digits and their order, not the exact text. The numbers are stable and checked as they are.
- `php -S` must run **without a router script**: with one, it sends the static files (`/build`, `/assets`) to `index.php` too.
- After `asset-map:compile`, delete `public/assets/` to go back to the development mode (the compiled files are served as is).
- Flex adds its default files when installing (`stimulus_bootstrap.js`, example controllers, `autoimport` in `controllers.json`): remove them, the stylesheet is imported explicitly.
