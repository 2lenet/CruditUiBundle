# Form theme

`@LleCruditUi/form/theme.html.twig` is a Symfony form theme built on `form_div_layout.html.twig`, **without Bootstrap**:
the inputs of the design system. It needs `symfony/form` in the project (the bundle does not require it). The design
kit page shows its markup (section *Form inputs*).

## Using it

For the whole project:

```yaml
# config/packages/twig.yaml
twig:
    form_themes: ['@LleCruditUi/form/theme.html.twig']
```

For one form only, whatever the global theme (`only` leaves out the themes of the project, `bootstrap_5_layout`…):

```twig
{% form_theme form with ['@LleCruditUi/form/theme.html.twig'] only %}
```

An application keeps its own layout (sections, grid, buttons) in a theme of its own, on top of this one:

```twig
{# templates/form/theme.html.twig #}
{% use '@LleCruditUi/form/theme.html.twig' %}

{% block crudit_ui_label_after %}{# after the label, on its line: a Toggletip #}{% endblock %}
```

CruditBundle does so (`@LleCrudit/form/theme.html.twig`, sections and grid of 4 columns).

## What it renders

| | Markup |
|---|---|
| Row | `div.crudit-ui-field` (`--invalid` with an error), `row_attr` merged on it |
| Label | above the input, `label.crudit-ui-label` pointing to it (`for`), in `div.crudit-ui-field__label-row` |
| Required | the attribute `required`, and a star `span.crudit-ui-required` hidden from assistive technologies: **explain it** above the form ("The fields marked with a star (*) are required") |
| Help | `p.crudit-ui-help#<id>_help` |
| Error | `p.crudit-ui-error#<id>_error`, with an icon; the input has `aria-invalid="true"` |
| `aria-describedby` | the help, then the error, after the ids given by the project in `attr` |
| Errors of the whole form | `div.crudit-ui-form-errors` with `role="alert"` |
| Compound field, choices as boxes | `fieldset.crudit-ui-field.crudit-ui-field__group` named by its `legend` |
| Text, number, date… | `input.crudit-ui-input` |
| List | `select.crudit-ui-select`; the empty option of a required list is disabled: once a value is chosen, it cannot be chosen again |
| Long text | `textarea.crudit-ui-input.crudit-ui-textarea` |
| Checkboxes, radios | `ul.crudit-ui-check-list > li > label.crudit-ui-check` (input and label); each input is attached to the help and the error of the field |
| A checkbox alone | its label next to it, inside the widget |
| Switch | a checkbox with `attr: {role: 'switch'}`: `label.crudit-ui-switch`, a native `input[type=checkbox][role=switch]` (sent with the form) and its state written, *Yes* / *No* (hidden from assistive technologies, which read the state of the switch); its label stays above |
| Amount, percentage | `div.crudit-ui-input-group` with the unit (`__unit`, hidden from assistive technologies): the focus surrounds the input and its unit |
| File | the native `input[type=file]`, restyled |

No floating label, no `placeholder` used as a label. The browser validation is the business of the form
(`novalidate`): the messages are those of the server.

## Classes

`crudit-ui-field`, `crudit-ui-field--invalid`, `crudit-ui-field__group`, `crudit-ui-field__label-row`,
`crudit-ui-label`, `crudit-ui-required`, `crudit-ui-help`, `crudit-ui-error`, `crudit-ui-form-errors`,
`crudit-ui-input`, `crudit-ui-select`, `crudit-ui-textarea`, `crudit-ui-input-group`, `crudit-ui-input-group__unit`,
`crudit-ui-input-group--color`, `crudit-ui-check-list`, `crudit-ui-check`, `crudit-ui-check__input`,
`crudit-ui-check__label`, `crudit-ui-switch`, `crudit-ui-switch__input`, `crudit-ui-switch__state` (`--on`, `--off`).
They can be used without the theme, in a form written by hand.

The help opened by a button next to a label is the component [Toggletip](components.md#llecrudituitoggletip).
