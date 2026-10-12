# Customization

## Theme

The theme is made of CSS variables (`--color-primary`, `--radius`…). Override them in your own stylesheet, no
compilation needed:

```css
:root {
    --color-primary: #b3261e;
}
```

All the rules of the bundle are in the `lle-ui` cascade layer: **your styles, which are not in a layer, always win**,
whatever the order in which the stylesheets are loaded. A rule of the bundle can be rewritten with a plain selector:

```css
.lle-dropdown__button {
    letter-spacing: 2px;
}
```

## Replacing a Stimulus controller

The identifier of a controller of the bundle is `2lenet--ui-bundle--<name>` (for example
`2lenet--ui-bundle--dropdown`). To replace it, add a controller with the same identifier in your `assets/controllers/`:
`assets/controllers/2lenet--ui-bundle--dropdown_controller.js`. The path of a controller of the bundle
(`dist/controllers/…`) is a public API: it does not change without a note in the upgrade guide.

The two asset tools do not behave the same way.

### AssetMapper

Your controller **wins alone**: the controllers of your project replace those of the bundle.

To **extend** the controller of the bundle instead of rewriting it, make it importable in `importmap.php`:

```php
'@2lenet/ui-bundle/controllers/dropdown_controller.js' => [
    'path' => '@2lenet/ui-bundle/controllers/dropdown_controller.js',
],
```

```js
import Dropdown from '@2lenet/ui-bundle/controllers/dropdown_controller.js';

export default class extends Dropdown {
    open() {
        super.open();
        // …
    }
}
```

### Webpack Encore

Your controller **loses** against the one of `controllers.json`: the Stimulus application registers yours first, then
the controllers of `controllers.json`, and the last registration replaces the previous one. **Disable the controller of
the bundle** in `controllers.json`:

```json
{
    "controllers": {
        "@2lenet/ui-bundle": {
            "dropdown": {"enabled": false}
        }
    }
}
```

Your controller can then extend it:

```js
import Dropdown from '@2lenet/ui-bundle/dist/controllers/dropdown_controller.js';

export default class extends Dropdown {
    open() {
        super.open();
        // …
    }
}
```

This is why the stylesheet is never loaded by the `autoimport` of a controller: disabling the controller would
remove it. See [Installation](installation.md#stylesheet).
