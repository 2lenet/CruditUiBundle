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
| Help | `div.crudit-ui-help#<id>_help` (a `div`: with `help_html`, it may hold a list or paragraphs) |
| Error | `p.crudit-ui-error#<id>_error`, with an icon; the input has `aria-invalid="true"` |
| `aria-describedby` | the unit of an amount or a percentage, then the ids given by the project in `attr`, then the help, the counter of characters and the error; each list of a date or a time in lists is also attached to the help and the error of its field |
| Errors of the whole form | `div.crudit-ui-form-errors` with `role="alert"` |
| Compound field, choices as boxes | `fieldset.crudit-ui-field.crudit-ui-field__group` named by its `legend` |
| Text, number, date… | `input.crudit-ui-input` |
| Keyboard of a phone | `inputmode` for the kind of the value, unless `attr` gives one: an integer is `type="number"` (the keyboard of the browser, with a minus key on iOS), `numeric` (the digit pad, no minus key on iOS) only with `attr.min` at 0 or more; `numeric` for each input of a date or a time in inputs (`widget: 'text'`); `decimal` for a number, an amount or a percentage typed as text (Symfony sets it for a number and an amount, `numeric` with `scale: 0`); an e-mail, a phone and a URL have their keyboard from their type (`email`, `tel`, `url`); `spellcheck="false"` on an e-mail address. A number stays typed as text (Symfony reads the point and the comma); with `html5: true`, `type="number"` has the keyboard of the browser. The keyboard `decimal` of iOS has no minus key either: `attr: {inputmode: 'text'}` for a number that may be negative |
| Counter of characters | a long text with `attr.maxlength`: `p.crudit-ui-field__count#<id>_count` under the help ("27 / 80 characters"), attached to the input; the controller `char-count` (on the input) updates it while typing and tells the characters left to assistive technologies near the limit (the last tenth, 5 at least; `data-2lenet--crudit-ui-bundle--char-count-threshold-value` on the input to change it), once the typing pauses (not when the page is loaded, nothing typed yet), in a live region `span#<id>_count_live` next to the counter: not inside it, the description of the input stays the count. Two forms of the message, `crudit_ui.form.count.left_one` (the plural category `one`) and `left_many` (all the others), chosen in the locale of the request (`data-2lenet--crudit-ui-bundle--char-count-locale-value`, a tag BCP 47: `ar_SA` becomes `ar-SA`; the `lang` of the page without a request or if `Intl` refuses the locale): a language with the forms `few` or `many` (Polish, Russian, Arabic…) needs more messages; none left has its own message `full`. A text on one line has it with the variable `counter`, `{{ form_row(form.code, {counter: true}) }}` (Symfony puts the length of the column of a string in `maxlength`: every text would have one); `counter: false` takes it away from a long text. The counter comes with the row: `form_widget()` alone has none, and is not attached to one |
| Date or time in lists | `widget: 'choice'` or `'text'`: the lists or the inputs on one line (`.crudit-ui-date-lists`), wrapped when the room is short; an input as wide as its digits (two, four for the year: `.crudit-ui-input--year`), with the digit pad (`inputmode="numeric"`); the legend names the field, each list (or input of `widget: 'text'`) its part, `aria-label` *Day*, *Month*, *Year*, *Hour*, *Minute*, *Second* |
| List | `select.crudit-ui-select`; the empty option of a required list is disabled: once a value is chosen, it cannot be chosen again |
| Long text | `textarea.crudit-ui-input.crudit-ui-textarea` |
| List with search | a list (not `expanded`) with the variable `combobox`: see [List with search](#list-with-search) |
| Translated field, cron expression | templates included by a field type (CruditBundle): see [Translated field](#translated-field) and [Cron expression](#cron-expression) |
| Checkboxes, radios | `ul.crudit-ui-check-list > li > label.crudit-ui-check` (input and label); each input is attached to the help and the error of the field |
| Segmented control | short choices, one answer (`expanded`, not `multiple`), asked with the variable `segmented` (like `counter`, a variable of the call to `form_row()`, as `label` or `help`: no `crudit_ui_` prefix, but do not define a variable of these names in a theme stacked with `{% use %}`): `{{ form_row(form.size, {segmented: true}) }}` (CruditBundle: the option `segmented` of a choice). `div.crudit-ui-segmented > label.crudit-ui-segmented__option` with the native radio (`__input`, invisible over its label: the arrows of the keyboard, sent with the form) and its label (`__label`); each radio is attached to the help and the error. Not required: an empty choice first ("None", the option `placeholder` to name it, `placeholder: false` to take it away). Made for a few short choices: on one line, they go on the next lines when the room is short (a phone, each choice 6rem at least) |
| A checkbox alone | its label next to it, inside the widget: `label_attr` goes on this `label.crudit-ui-check` |
| Switch | a checkbox with `attr: {role: 'switch'}`: `label.crudit-ui-switch`, a native `input[type=checkbox][role=switch]` (sent with the form) and its state written, *Yes* / *No* (hidden from assistive technologies, which read the state of the switch); its label stays above |
| Amount, percentage | `div.crudit-ui-input-group` with the unit (`span.crudit-ui-input-group__unit#<id>_unit`, read with the input: `aria-describedby`): the focus surrounds the input and its unit |
| Colour | `div.crudit-ui-input-group.crudit-ui-input-group--color`: the native picker and its code next to it (hidden from assistive technologies: the picker tells its value), kept up to date by the controller `color-code` |
| File | the native `input[type=file]`, restyled |

No floating label, no `placeholder` used as a label.

The controllers `char-count` and `color-code` are enabled by Flex (`assets/controllers.json`, see
[Installation](installation.md)); without them the counter shows the length of the value when the page is loaded and
the code of a colour its value then. This length is counted by the server (characters), the browser and `maxlength`
count UTF-16 units and a line break as one character, where a stored value often has two (`\r\n`): without the
controller, the first count may differ a little; the controller counts again when it connects. Both controllers show the
values put back by a reset of the form; a value changed by a script is shown after an `input` event
(`input.dispatchEvent(new Event('input', {bubbles: true}))`).

## List with search

A list with the variable `combobox` (`{{ form_row(form.zones, {combobox: true}) }}`; CruditBundle sets it on a choice
with several answers) is a list with search, the ARIA combobox pattern (the field `UiCombobox` of the crudit-v2 mockup,
in place of Tom Select):

- `div.crudit-ui-combobox` (`--invalid`, `--disabled`), with the controller `combobox`;
- the choices made, `ul.crudit-ui-combobox__tokens` named "*Zones*: choices made", each `li.crudit-ui-combobox__token`
  with a button *Remove …* (none when the field is disabled);
- the input `input#<id>.crudit-ui-combobox__input`, `role="combobox"`, attached to `ul#<id>_listbox` (`role="listbox"`,
  `aria-multiselectable` with several answers), to the help and the error; `aria-required` instead of `required`;
  the placeholder of the field, else *Search…*;
- a live region that tells the count of choices, a choice added or removed, no result, an error, *Loading…* when
  Enter is pressed before the choices of the text are there;
- the value is sent by a hidden `<select>` with the name of the field (its options are the choices).

The choices are those of the field, groups flattened. The preferred choices that are not among the others
(`duplicate_preferred_choices: false`) come first, as in a native list. A disabled choice (`choice_attr` with
`disabled`) is shown (`aria-disabled`) but cannot be chosen; already chosen, it keeps its token without a button. The
options of the hidden `<select>` are read again each time the list opens: a script may change them (a field that
depends on another one). The reset of the form gives back the choices made of the server (also after a restore from the
cache of Turbo).

The keyboard: the list opens on the focus, a click and the arrows; up and down move the active option
(`aria-activedescendant`, the focus stays in the input), Enter chooses it without sending the form, Escape closes the
list (not a dialog around it), Backspace in an empty input removes the last choice. With several answers the list stays
open, else a choice replaces the other one and closes it. What is typed filters the choices, accents and case left
aside, and is marked in them. With one answer too, Backspace in an empty input removes the choice, as its button does
(as the mockup): a required field left empty is refused by the validation when the form is sent.

The first 50 choices that match are shown, then a line tells how many there are (*First 50 results of 1234: refine the
search.*); the count read by a screen reader is the total. For more than a few hundred choices, prefer the choices from
the server (`AutocompleteType` of CruditBundle): all the choices of the page are in the HTML.

The list is in `position: fixed`, under the field, or above it when there is no room below: a dialog, a card or a table
that scrolls does not cut it. It follows the field when the page scrolls, and stays in the part of the page seen on a
phone, above the virtual keyboard (`visualViewport`).

**From the server**: a field type whose widget block renders `crudit_ui_combobox_widget` and sets `combobox_url` (and
`combobox_selected`, the choices made: `[{value, label}]`), such as `AutocompleteType` of CruditBundle, asks its choices to `GET <url>?q=<text>&offset=<n>&limit=20`, answered by
`{"items": [{"id", "text"}], "next_offset": <n>, "total_count": <n>}` (the answer of the route `autocomplete` of
CruditBundle). The first choices are asked when the list opens (`combobox_min_length`: characters typed before a
request, 0), the ones of the text 200 ms after the last key, the next ones when the list is scrolled to its end;
the request still running is cancelled by a newer one. The first page of the empty text is kept: the list opens again on
it without a request (Tab through a form). The list asks no more once `next_offset` reaches `total_count`, or when a
page is empty. The value is sent by a hidden input, the values joined by a comma
(`combobox_separator`): the field type reads this text (a data transformer, as `EntityToIdTransformer` of CruditBundle),
so a value must not contain the separator (ids do not). A `ChoiceType` reads a list: on it, `combobox_url` is ignored and
its choices stay in the page.

The markup is in `@LleCruditUi/form/_combobox.html.twig`, which a page without a Symfony form can include with plain
values (see its header; the kit does so). JavaScript is needed: without it the choices cannot be changed. The controller
`combobox` is loaded when a page has one (`fetch: lazy`).

## Translated field

`@LleCruditUi/form/_translatable.html.twig` (the field `TranslatableField` of the crudit-v2 mockup; CruditBundle:
`GedmoTranslatableType`) shows one tab per language, the input of the language in its panel. It has the markup of the
component [Tabs](components.md) (classes `crudit-ui-tabs*`, ARIA tabs pattern, controller `tabs`: arrows, Home, End),
built from plain values (see the header of the template), since `Tabs` wants a Twig block per tab:

- the tablist is named "Languages: *Title*", a tab by the name of its language, then a star (hidden from assistive
  technologies) when the language is required;
- a tab says the state of its language, as part of its name: "English · empty", or "Deutsch · error" with an icon;
- each panel has a hidden label "*Title* (*language*)" (`<input_id>_label`), the input (given as HTML, with its own
  `lang`, `required`, `aria-describedby`), then its errors;
- the tab shown first is the first in error (after a form sent, the error is not hidden behind another tab), else
  `selected`, else the first.

The input of the first language, also pointed by the visible label of the field, gets
`aria-labelledby="<input_id>_label"`: its name is then "*Title* (*language*)", not the two labels. The controller
`translatable` (`fetch: lazy`) updates " · empty" on the event `input`; " · error" stays until the form is sent again.
A rich editor (CKEditor…) in a panel that does not dispatch `input` on its field leaves " · empty" as it was. Unlike
`Tabs`, the panels have no `tabindex`: each one holds an input, the next stop of the keyboard after the tab. The
controller takes `required` away from the inputs (`aria-required="true"` instead): a required input in a hidden panel
would stop the form in the browser with no message it can show (see [`novalidate`](#novalidate)); the server refuses an
empty value.

## Cron expression

`@LleCruditUi/form/_cron.html.twig` (the field `CronField` of the crudit-v2 mockup, with the controller `cron`, `fetch:
lazy`; CruditBundle: `CronExpressionType`) writes a recurrence in the real text input, which is what the form sends.
**Render it in a fieldset named by the label of the field**, the pattern of a compound field
(`fieldset.crudit-ui-field.crudit-ui-field__group > legend.crudit-ui-label`, as CruditBundle does): a `<label for>`
would point to the text input, hidden unless the frequency is Custom. In it:

- a segmented control of the frequency, a radiogroup named "Frequency": None (not with `required`), Every day, Every
  week, Every month, Custom;
- the days of the week, always Monday first whatever the language, each named in full, its short name shown ("Mo",
  "Tu"), the day of the month (1 to 28), the time;
- a sentence ("Every Monday and Thursday at 8:30 AM", the time in the format of the language; `aria-live` on the
  sentence only, once written; `<id>_summary`, in the `aria-describedby` of the days and the time, so that "Choose at
  least one day." is read on the day again) and the expression (`30 8 * * 1,4`), the value of the text input when the page loads.

Expressions written: `m h * * *`, `m h * * 1,4`, `m h D * *`, an empty value for None. A week without a day, or an empty
time: the sentence asks for it ("Choose at least one day.", "Choose a time."), the expression stays incomplete (four
fields) and the server refuses it; the days are invalid (`aria-invalid`) while none is checked. Custom shows the text
input, its expression kept as it is: an expression the widget cannot show (`*/15 6-20 * * 1-5`, `0 18 L * *`) is opened
in Custom; leaving Custom reads the expression typed when it has one of the forms of the widget (`00 08 * * *` too),
else the controls keep their state. The help and the error (`describedby`, `invalid`) are attached to every control.

Without JavaScript only the text input is shown and works (the controls have `hidden`, the controller takes it away).
Only the text input is read by the form: the radios of the frequency have a name outside the form (`<id>__frequency`,
sent with it, ignored by Symfony, which reads its own name), the other controls have none. The state of the widget is
read by the server from the value (`state`: see the header of the template; CruditBundle reads it). The validation of
the expression belongs to the server. A required text input is `required` only in Custom, when shown
(`aria-required="true"` always): hidden, it would stop the form in the browser with no message it can show (see
[`novalidate`](#novalidate)); the server refuses an empty value.

### novalidate

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
`crudit-ui-switch__input`, `crudit-ui-switch__state` (`--on`, `--off`), `crudit-ui-combobox` (`--invalid`,
`--disabled`, `__field`, `__tokens`, `__token`, `__token-label`, `__remove`, `__input`, `__chevron`, `__listbox`,
`__option`, `__option--active`, `__check`, `__label`, `__message`), `crudit-ui-translatable` (`__state`,
`__state--invalid`, `__panel`), `crudit-ui-cron` (`--invalid`, `__frequency`, `__details`, `__part`, `__hint`, `__days`,
`__day`, `__day-input`, `__day-label`, `__summary`, `__code`, `__custom`).
They can be used without the theme, in a form written by hand.

The help opened by a button next to a label is the component [Toggletip](components.md#llecrudituitoggletip).
