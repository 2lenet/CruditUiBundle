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
npm test        # breakpoints and the `media` mixin
```

`assets/dist/` is **committed**: the bundle ships the compiled CSS, projects have no build step for it.

| Path | Content |
|---|---|
| `assets/styles/` | Sass, in ITCSS order: `settings` → `tools` → `generic` → `elements` → `objects` → `components` → `utilities`. Only `ui.scss` is compiled; it loads each partial with `meta.load-css` inside `@layer lle-ui`, so a partial is plain CSS and never declares the layer itself. |
| `assets/dist/ui.css` | The compiled stylesheet. |
| `assets/dist/controllers/` | Stimulus controllers, native ES modules (no build). Their path is public API. |
| `assets/dist/utils/` | JavaScript helpers shared by the controllers (`breakpoints.js`). |

### Conventions

- Classes: prefix `lle-`, BEM (`lle-dropdown__button`). Tokens keep their plain names (`--color-primary`); a token specific to one component takes the prefix (`--lle-table-row-height`).
- **Breakpoints**: one set, `styles/settings/_breakpoints.scss`. In Sass use `@include media('>md')` / `media('<md')`; in JavaScript use `breakpoint('md')` or `matchBreakpoint('>md')` (`dist/utils/breakpoints.js`), which read the `--breakpoint-*` CSS variables. Never write a width in px in a media query or in a controller.
- JavaScript: native ES modules only, no CSS/HTML/JSON import, bare imports declared in `peerDependencies` and `symfony.importmap`.
- **Lazy loading**: a light controller is `"fetch": "eager"`; a heavy one (editor, Sortable, Chart.js…) is `"fetch": "lazy"` in `package.json`, or loads its library with a dynamic `import()`.
- The CSS is linked explicitly by the layout (never by the `autoimport` of a controller, which disappears when a project disables the controller).

The bundle must not depend on `2lenet/crudit-bundle` nor on `2lenet/dashboard-bundle`: it knows nothing about
`CrudConfig`, bricks or the menu.

## Test applications

`tests/apps/` holds one Symfony application per asset tool the bundle supports: `assetmapper` and `encore`.
Each one installs the bundle as a project does (Composer path repository with `symlink: false`, so the bundle is
**copied** into `vendor/`), loads the stylesheet explicitly, overrides a variable in its own CSS and shows the
dropdown controller. `tests/apps/smoke.mjs` drives a headless Chrome on the page: the stylesheet and the
breakpoint variables are loaded, the project wins over the `lle-ui` layer, the controller opens and closes the
menu, nothing fails to load. The `Test applications` workflow runs it for AssetMapper (dev and prod, with
`asset-map:compile`) and Encore.

To run one locally (Docker, no local PHP needed):

```bash
cd tests/apps/encore
docker run --rm -u $(id -u) -e COMPOSER_HOME=/tmp/c -v $PWD/../../..:/app -w /app/tests/apps/encore composer:2 composer install --ignore-platform-reqs
docker run --rm -u $(id -u) -e HOME=/tmp -v $PWD/../../..:/app -w /app/tests/apps/encore node:22 sh -c 'npm install && npm run build'
docker run -d --rm --name ui-app -u $(id -u) --network host -e APP_SECRET=x -v $PWD/../../..:/app -w /app/tests/apps/encore php:8.5-cli php -S 127.0.0.1:8081 -t public
cd .. && npm install && node smoke.mjs http://127.0.0.1:8081/
```

Pitfalls:

- **A change in `assets/` is not seen by an application that already copied the bundle**: copy `assets/dist/` again into `vendor/2lenet/ui-bundle/assets/dist/` (or reinstall the bundle).
- Composer copies the whole bundle, `vendor/` and `node_modules/` included, and the applications already installed with it: use a clean checkout (as the CI does) when installing a second application.
- `php -S` must run **without a router script**: with one, it sends the static files (`/build`, `/assets`) to `index.php` too.
- After `asset-map:compile`, delete `public/assets/` to go back to the development mode (the compiled files are served as is).
- Flex adds its default files when installing (`stimulus_bootstrap.js`, example controllers, `autoimport` in `controllers.json`): remove them, the stylesheet is imported explicitly.
