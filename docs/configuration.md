# Configuration reference

```yaml
# config/packages/lle_crudit_ui.yaml (default values)
lle_crudit_ui:
    icons:
        # load Font Awesome 7 from the bundle
        load_fontawesome: true
        # icon replacements: "icon used by the bundles": "icon of your library"
        map: {}
```

| Option | |
|---|---|
| `icons.load_fontawesome` | `false` when the project loads its own icon library |
| `icons.map` | Replaces an icon rendered by the bundles by another one, for example `'fa-solid fa-trash': 'xx xx-trash'` |

## Icons

With another library, disable Font Awesome and map the icons you need:

```yaml
lle_crudit_ui:
    icons:
        load_fontawesome: false
        map:
            'fa-solid fa-trash': 'xx xx-trash'
            'fa-solid fa-user': 'xx xx-user'
```
