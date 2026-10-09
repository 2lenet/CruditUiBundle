# Contributing

```bash
composer install
vendor/bin/phpcs
vendor/bin/phpstan analyse -c tests/phpstan.neon
vendor/bin/phpunit -c tests/phpunit/phpunit.xml
```

The CSS is compiled with Sass into `assets/dist`:

```bash
cd assets
npm install
npm run build
```

The bundle must not depend on `2lenet/crudit-bundle` nor on `2lenet/dashboard-bundle`: it knows nothing about
`CrudConfig`, bricks or the menu.
