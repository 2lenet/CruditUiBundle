# UiBundle

Design system commun des bundles 2LE (CruditBundle, DashboardBundle) : tokens, CSS de base, composants Twig, contrôleurs Stimulus, icônes, langues et RTL.

UiBundle ne connaît ni `CrudConfig`, ni les bricks, ni le menu de Crudit. Voir `doc/plan-migration.md` du projet crudit-v2 (§1, §6).

> État : squelette (lot 0.3). Pas encore de composant.

## Installation

```bash
composer require 2lenet/ui-bundle
```

## Configuration

```yaml
lle_ui:
    icons:
        load_fontawesome: true   # false si le projet charge sa propre librairie d'icônes
        map:                     # remplacement d'icônes
            'fa-solid fa-trash': 'xx xx-trash'
```

## Développement

```bash
make install       # composer install et npm install
make build-assets  # compile le CSS dans assets/dist
make lint          # phpcs et phpstan
make test          # phpunit
```
