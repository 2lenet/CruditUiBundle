# Configuration reference

```yaml
# config/packages/lle_crudit_ui.yaml (default values)
lle_crudit_ui:
    icons:
        # icon library of the project
        library: fontawesome
        # icon replacements: "logical name or Iconify name": "Iconify name"
        map: {}
    # languages written from right to left, without region
    rtl_languages: [ar, fa, he, ur]
```

| Option | |
|---|---|
| `icons.library` | Icon library of the project, one of `fontawesome`, `bootstrap-icons`, `boxicons`, `flowbite`, `heroicons`, `iconoir`, `ionicons`, `lucide`, `mdi`, `octicons`, `phosphor`, `tabler` (see [Icon libraries](icon-libraries.md)) |
| `icons.map` | Replaces an icon rendered by the bundles by another one, for example `delete: 'tabler:trash-x'` |
| `rtl_languages` | Languages written from right to left (Arabic, Persian, Hebrew, Urdu by default): `ui_dir()` gives `rtl` for them. A language code covers all its regions, `ar` also covers `ar_MA` (see [Languages](languages.md)) |

## Icons

Choose the library of the project; the bundles then render all their icons from it. Replace the icons you want
by any Iconify name ([ux.symfony.com/icons](https://ux.symfony.com/icons)), keyed by logical name
([catalogue](icons.md)) or, for an icon passed by the project, by its Iconify name:

```yaml
lle_crudit_ui:
    icons:
        library: lucide
        map:
            delete: 'tabler:trash-x'
            'lucide:truck': 'tabler:truck-delivery'
```
