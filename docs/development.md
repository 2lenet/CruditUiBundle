# Contributing

```bash
composer install
vendor/bin/phpcs
vendor/bin/phpstan analyse -c tests/phpstan.neon
vendor/bin/phpunit -c tests/phpunit/phpunit.xml
```

PHPUnit needs the icon sets of Iconify, devDependencies of `assets/` (`npm ci` in `assets/`, as the CI does):
`IconLibraryTest` checks every icon name against them, and fails when a set is missing.

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
| `assets/dist/utils/` | JavaScript helpers shared by the controllers (`breakpoints.js`; `position.js`, the place of a panel next to its button). |

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

## A new component

A component is done when:

- it follows the [conventions](components.md#conventions), and its [contract](components.md#contract-of-a-component)
  is in `docs/components.md`;
- it has its section in the design kit page, in all its states;
- it works at 360, 768, 1280 and 1600 px wide, in light and dark, right to left;
- a PHPUnit test renders it (`RendersTwig` in `tests/phpunit/Functional`: `render()` with `<twig:LleCruditUi:…>`, then
  `html()` and `querySelector()`), and the smoke test passes, axe included;
- if it has a Stimulus controller, the smoke test drives it on the design kit page in a real browser: clicks, keyboard,
  right to left, the events it dispatches. PHPUnit only sees the HTML rendered by the server;
- it works under Turbo: it is in a page of the application `turbo`, and `smoke-turbo.mjs` checks it after visits,
  Back, a Turbo Stream or a morph (see below).

## Test applications

`tests/apps/` holds one Symfony application per asset tool the bundle supports, `assetmapper` and `encore`, and
`turbo`: the application `assetmapper` with Symfony UX Turbo (its latest version, Turbo 8).
Each one installs the bundle as a project does (Composer path repository with `symlink: false`, so the bundle is
**copied** into `vendor/`), loads the stylesheet explicitly, overrides a variable in its own CSS and shows the
dropdown controller. `tests/apps/smoke.mjs` drives a headless Chrome on the page: the stylesheet and the
breakpoint variables are loaded, the project wins over the `crudit-ui` layer, the controller opens and closes the
menu, the page in French, Arabic and Persian has the right direction, texts, numbers and dates, nothing fails to load,
and the [design kit](components.md#design-kit) page has no accessibility violation for axe-core (WCAG 2.1 A and AA,
in light, dark, right to left and on a phone, 360 px wide), and its components work: buttons, alerts, collapsibles
and tabs (keyboard, right to left, address). The `Test applications` workflow runs it for AssetMapper (dev and prod, with
`asset-map:compile`), Encore and Turbo (dev and prod).

### Turbo

No project uses Turbo yet: the application `turbo` makes sure the bundle already works with it. Turbo changes the
life of a page: a link or a form does not load a new page, Turbo replaces the `<body>` (the controllers are
disconnected and connected again, the listeners on `document` and `window` stay if a controller does not remove
them), keeps a snapshot of the page left and shows it on Back, sends the forms with `fetch` (`turbo:submit-start`,
`turbo:submit-end`), applies the Turbo Streams of the answer, loads the Turbo Frames, and refreshes a page by a
morph (the elements stay, their attributes follow the server). The smoke test of the other applications only
simulates some of its events.

`tests/apps/smoke.mjs` runs on it too (the design kit with Turbo Drive on), then `tests/apps/smoke-turbo.mjs` drives
the pages of `TurboController` (`/turbo`, layout `templates/turbo/layout.html.twig`, with the region of the toasts):

- visits back and forth: no listener left on `document` or `window` (counted by the DevTools protocol), one controller
  per element (a toast shown once, a dropdown opened by one click, a dialog opened once);
- Back: a dropdown, a dialog or a toast open when the page was left are not restored, the page reached from a
  dialog is not locked, the theme switch shows the current choice, the tabs come back on their tab without a full
  load (the state of Turbo in the history is kept);
- forms: a redirect (303) and its flash message as a toast, announced; a Turbo Stream that appends a toast and
  replaces a fragment; a slow answer (the button in loading, a second click sends nothing); a form in a dialog sent
  back with its errors (422), the dialog open;
- a page refreshed by a morph (`/turbo/morph`): the dialog sent back with its errors stays open without closing in
  between, the focus where it was; the redirect to the same page closes it and frees the page;
- a Turbo Frame loaded lazily, then navigated by its link; the language by a Turbo visit (`lang` and `dir`);
- axe-core on the pages reached by Turbo.

A new controller, or a component that keeps a state in the page, gets its case in these pages and in
`smoke-turbo.mjs`.

To run one locally (Docker, no local PHP needed):

```bash
cd tests/apps/encore
docker run --rm -u $(id -u) -e COMPOSER_HOME=/tmp/c -v $PWD/../../..:/app -w /app/tests/apps/encore composer:2 composer install --ignore-platform-reqs
docker run --rm -u $(id -u) -e HOME=/tmp -v $PWD/../../..:/app -w /app/tests/apps/encore node:22 sh -c 'npm install && npm run build'
docker build -t php-intl - <<< $'FROM php:8.5-cli\nRUN apt-get update && apt-get install -y libicu-dev && docker-php-ext-install intl'
docker run -d --rm --name ui-app -u $(id -u) --network host -e APP_SECRET=x -v $PWD/../../..:/app -w /app/tests/apps/encore php-intl php -S 127.0.0.1:8081 -t public
cd .. && npm install && node smoke.mjs http://127.0.0.1:8081/
```

The application `turbo` the same way, without the Encore build, then its own smoke test:
`node smoke-turbo.mjs http://127.0.0.1:8081/`.

Without Node on the machine, the image of Puppeteer runs the smoke test; the path of its Chrome is the second
argument. Not with `-u`: Chrome does not start.

```bash
cd tests/apps
docker run --rm --network host -v $PWD:/work -w /work ghcr.io/puppeteer/puppeteer:24.10.0 node smoke.mjs http://127.0.0.1:8081/ /home/pptruser/.cache/puppeteer/chrome/linux-137.0.7151.55/chrome-linux64/chrome
```

Pitfalls:

- **A change in `assets/` is not seen by an application that already copied the bundle**: copy `assets/dist/` again into `vendor/2lenet/crudit-ui-bundle/assets/dist/` (or reinstall the bundle).
- Composer copies the whole bundle, `vendor/` and `node_modules/` included, and the applications already installed with it: use a clean checkout (as the CI does) when installing a second application. Locally, the copy can take minutes (more than 1 GB): copy the application elsewhere, make a light copy of the bundle (`composer.json`, `src`, `templates`, `config`, `translations`, `assets/package.json`, `assets/dist`), and point the `path` repository at it in `composer.json` **and** `composer.lock` (its `dist.url`). The installation then takes a second.
- The bundle requires the `intl` extension of PHP, which the `php:*-cli` and `composer:2` images do not have (hence
  the `php-intl` image above, also needed by PHPUnit).
- The spaces and separators of the dates depend on the version of ICU: the tests check their digits and their order,
  not the exact text (and no era, which the default calendar of a locale would add: `13/10/2026 م` in `ar_SA` with
  ICU 74). The numbers are stable and checked as they are.
- `php -S` must run **without a router script**: with one, it sends the static files (`/build`, `/assets`) to `index.php` too.
- After `asset-map:compile`, delete `public/assets/` to go back to the development mode (the compiled files are served as is).
- Flex adds its default files when installing (`stimulus_bootstrap.js`, example controllers, `autoimport` in `controllers.json`): remove them, the stylesheet is imported explicitly.
