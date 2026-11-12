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
| Required | the attribute `required`, and a star `span.crudit-ui-required` hidden from assistive technologies: **explain it** above the form ("The fields marked with a star (*) are required"). A compound field has the star only when it is one field (choices as boxes, a date or a time in lists), not a sub-form, a collection or a repeated field |
| Help | `p.crudit-ui-help#<id>_help` |
| Error | `p.crudit-ui-error#<id>_error`, with an icon; the input has `aria-invalid="true"` |
| `aria-describedby` | the help, the counter of characters, then the error, after the ids given by the project in `attr`; each list of a date or a time in lists is also attached to the help and the error of its field |
| Errors of the whole form | `div.crudit-ui-form-errors` with `role="alert"` |
| Compound field, choices as boxes | `fieldset.crudit-ui-field.crudit-ui-field__group` named by its `legend` |
| Text, number, date… | `input.crudit-ui-input` |
| Keyboard of a phone | `inputmode` for the kind of the value, unless `attr` gives one: an integer is `type="number"` (the keyboard of the browser, with a minus key on iOS), `numeric` (the digit pad, no minus key on iOS) only with `attr.min` at 0 or more; `numeric` for each input of a date or a time in inputs (`widget: 'text'`); `decimal` for a number, an amount or a percentage typed as text (Symfony sets it for a number and an amount, `numeric` with `scale: 0`); an e-mail, a phone and a URL have their keyboard from their type (`email`, `tel`, `url`); `spellcheck="false"` on an e-mail address. A number stays typed as text (Symfony reads the point and the comma); with `html5: true`, `type="number"` has the keyboard of the browser. The keyboard `decimal` of iOS has no minus key either: `attr: {inputmode: 'text'}` for a number that may be negative |
| Counter of characters | a long text with `attr.maxlength`: `p.crudit-ui-field__count#<id>_count` under the help ("27 / 80 characters"), attached to the input; the controller `char-count` (on the input) updates it while typing and tells the characters left to assistive technologies near the limit (the last tenth, 5 at least; `data-2lenet--crudit-ui-bundle--char-count-threshold-value` on the input to change it), once the typing pauses (not when the page is loaded, nothing typed yet), in a live region `span#<id>_count_live` next to the counter: not inside it, the description of the input stays the count. Two forms of the message, `crudit_ui.form.count.left_one` (the plural category `one`) and `left_many` (all the others), chosen in the locale of the request (`data-2lenet--crudit-ui-bundle--char-count-locale-value`; the `lang` of the page without a request): a language with the forms `few` or `many` (Polish, Russian, Arabic…) needs more messages. A text on one line has it with the variable `counter`, `{{ form_row(form.code, {counter: true}) }}` (Symfony puts the length of the column of a string in `maxlength`: every text would have one); `counter: false` takes it away from a long text. The counter comes with the row: `form_widget()` alone has none, and is not attached to one |
| Date or time in lists | `widget: 'choice'` or `'text'`: the lists or the inputs on one line (`.crudit-ui-date-lists`), wrapped when the room is short; an input as wide as its digits (two, four for the year: `.crudit-ui-input--year`), with the digit pad (`inputmode="numeric"`); the legend names the field, each list (or input of `widget: 'text'`) its part, `aria-label` *Day*, *Month*, *Year*, *Hour*, *Minute*, *Second* |
| List | `select.crudit-ui-select`; the empty option of a required list is disabled: once a value is chosen, it cannot be chosen again |
| Long text | `textarea.crudit-ui-input.crudit-ui-textarea` |
| Checkboxes, radios | `ul.crudit-ui-check-list > li > label.crudit-ui-check` (input and label); each input is attached to the help and the error of the field |
| Segmented control | short choices, one answer (`expanded`, not `multiple`), asked with the variable `segmented`: `{{ form_row(form.size, {segmented: true}) }}` (CruditBundle: the option `segmented` of a choice). `div.crudit-ui-segmented > label.crudit-ui-segmented__option` with the native radio (`__input`, invisible over its label: the arrows of the keyboard, sent with the form) and its label (`__label`); each radio is attached to the help and the error. Not required: an empty choice first ("None", the option `placeholder` to name it, `placeholder: false` to take it away). Made for a few short choices: on one line, they go on the next lines when the room is short (a phone, each choice 6rem at least) |
| A checkbox alone | its label next to it, inside the widget |
| Switch | a checkbox with `attr: {role: 'switch'}`: `label.crudit-ui-switch`, a native `input[type=checkbox][role=switch]` (sent with the form) and its state written, *Yes* / *No* (hidden from assistive technologies, which read the state of the switch); its label stays above |
| Amount, percentage | `div.crudit-ui-input-group` with the unit (`__unit`, hidden from assistive technologies): the focus surrounds the input and its unit |
| Colour | `div.crudit-ui-input-group.crudit-ui-input-group--color`: the native picker and its code next to it (hidden from assistive technologies like the units: the picker tells its value), kept up to date by the controller `color-code` |
| File | the native `input[type=file]`, restyled |

No floating label, no `placeholder` used as a label.

The controllers `char-count` and `color-code` are enabled by Flex (`assets/controllers.json`, see
[Installation](installation.md)); without them the counter shows the length of the value when the page is loaded and
the code of a colour its value then. This length is counted by the server (characters), the browser and `maxlength`
count UTF-16 units and a line break as one character, where a stored value often has two (`\r\n`): without the
controller, the first count may differ a little; the controller counts again when it connects. Both controllers show the
values put back by a reset of the form; a value changed by a script is shown after an `input` event
(`input.dispatchEvent(new Event('input', {bubbles: true}))`).

The theme does not change the `<form>` tag. Without `novalidate` on it, the browser checks the required fields itself
before sending the form and shows its own bubbles instead of the errors of the theme. To show only the messages of the
server, add it to the form:

```twig
{{ form_start(form, {attr: {novalidate: 'novalidate'}}) }}
```

## Classes

`crudit-ui-field`, `crudit-ui-field--invalid`, `crudit-ui-field__group`, `crudit-ui-field__label-row`,
`crudit-ui-label`, `crudit-ui-required`, `crudit-ui-help`, `crudit-ui-error`, `crudit-ui-form-errors`,
`crudit-ui-input`, `crudit-ui-select`, `crudit-ui-textarea`, `crudit-ui-input-group`, `crudit-ui-input-group__unit`,
`crudit-ui-input-group--color`, `crudit-ui-field__count`, `crudit-ui-date-lists`, `crudit-ui-check-list`,
`crudit-ui-check`, `crudit-ui-check__input`, `crudit-ui-check__label`, `crudit-ui-segmented`,
`crudit-ui-segmented__option`, `crudit-ui-segmented__input`, `crudit-ui-segmented__label`, `crudit-ui-switch`,
`crudit-ui-switch__input`, `crudit-ui-switch__state` (`--on`, `--off`).
They can be used without the theme, in a form written by hand.

The help opened by a button next to a label is the component [Toggletip](components.md#llecrudituitoggletip).
