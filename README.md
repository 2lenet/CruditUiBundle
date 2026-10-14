<h1 align="center">CruditUiBundle</h1>

<p align="center">
    <a href="https://github.com/2lenet/CruditUiBundle/actions" target="_blank">
        <img src="https://github.com/2lenet/CruditUiBundle/actions/workflows/phpstan.yml/badge.svg" alt="PHPStan status">
    </a>
    <a href="https://github.com/2lenet/CruditUiBundle/actions" target="_blank">
        <img src="https://github.com/2lenet/CruditUiBundle/actions/workflows/phpunit.yml/badge.svg" alt="PHPUnit status">
    </a>
    <a href="https://github.com/2lenet/CruditUiBundle/actions" target="_blank">
        <img src="https://github.com/2lenet/CruditUiBundle/actions/workflows/validate.yml/badge.svg" alt="PHPCS status">
    </a>
</p>

The design system shared by the 2LE bundles ([Crudit Bundle](https://github.com/2lenet/CruditBundle),
[Dashboard Bundle](https://github.com/2lenet/DashboardBundle)): design tokens, base CSS, Twig components,
Stimulus controllers, icons, languages and right-to-left support.

The bundle knows nothing about Crudit (`CrudConfig`, bricks, menu): it can be used on its own.

> The bundle is under construction: for now it provides the design tokens, the base CSS (reset, grid, utilities), the
> dark mode, the icons, and the languages (direction of the page, numbers and dates, translations). The components come
> next.

## Installation

```bash
composer require 2lenet/crudit-ui-bundle
```

Afterwards, visit the [documentation](docs/index.md) to set up and use CruditUiBundle.

## Licence

CruditUiBundle is released under the terms of the [MIT License](LICENSE).
