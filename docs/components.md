# Components

The bundle gives Twig components (Symfony UX TwigComponent) with the look of the crudit-v2 mockup, and a **design
kit** page that shows them in all their states. The components come with the lot 1.5 of the redesign; this page
gives the conventions they all follow and the contract each one documents.

## Using a component

```twig
<twig:LleCruditUi:Kit:Section id="colors" title="Colors">
    …
</twig:LleCruditUi:Kit:Section>
```

The prefix is always `LleCruditUi:`, the Twig namespace of the bundle: the template of `LleCruditUi:Kit:Section` is
`@LleCruditUi/components/Kit/Section.html.twig`. A shorter prefix such as `Ui:` is not used on purpose: some projects
already have components of their own named `Ui:Menu` or `Ui:Chip`, which TwigComponent would render in place of
those of the bundle, in the screens of CruditBundle too.

## Conventions

- **Anonymous by default**: one template, `templates/components/<Name>.html.twig`, with its `{% props %}`. A PHP
  class (`src/Twig/Component/<Name>.php`, namespace `Lle\CruditUiBundle\Twig\Component`) only when the props must be
  validated or something computed. It gives its name and its template, the same as an anonymous one, so a project does
  not see the difference: `#[AsTwigComponent('LleCruditUi:Dialog', template: '@LleCruditUi/components/Dialog.html.twig')]`.
- **CSS**: one partial per component, `assets/styles/components/_<name>.scss`, in the `crudit-ui` layer, with BEM
  classes prefixed `crudit-ui-` (`crudit-ui-btn`, `crudit-ui-btn--primary`, `crudit-ui-btn__icon`). The
  component reads the tokens (`var(--c-primary)`); a token of its own takes the prefix (`--crudit-ui-table-row-height`).
- **Attributes**: the root element takes the attributes the project passes (`{{ attributes.defaults({class: '…'}) }}`),
  so a project can add a class, an `id` or a `data-` attribute.
- **JavaScript**: a Stimulus controller of the bundle, `2lenet--crudit-ui-bundle--<name>`, never a script in the
  template. No JavaScript of Bootstrap.
- **Forced states**, for the design kit only: `crudit-ui-is-focus` draws the focus ring (generic, in
  `utilities/_a11y.scss`); `crudit-ui-is-hover` is added by each component next to its `:hover`
  (`&:hover, &.crudit-ui-is-hover`). The component stays usable in a forced state.
- **Texts** of the component are translated in the `LleCruditUiBundle` domain; the icons come from `ui_icon()`.
- **Variables in the content** of a component: its props hide the variables of the template with the same name.
  In `{% for variant in variants %}<twig:LleCruditUi:Button …>{{ variant.label }}</twig:LleCruditUi:Button>`,
  `variant` is the prop of the button: name the loop variable otherwise.

## Contract of a component

Each component documents what a project may rely on, and override. Copy this block in `docs/components.md` for each
new component:

```markdown
### LleCruditUi:Name

What it is, and when to use it (or not).

| Prop | Type | Default | |
|---|---|---|---|
| `tone` | `'neutral'`, `'success'`… | `'neutral'` | |

Blocks: `content` (…). Events: those its controller dispatches (name, detail), when it has one.
Accessibility: role, name, keyboard, what is announced.
```

The props, the blocks and the events are the contract: changing them is a breaking change for the projects that
override the component.

## Components of the bundle

### LleCruditUi:Button

A button, or a link that looks like one (`href`). **One primary button per zone**; the others are secondary, ghost
or link.

```twig
<twig:LleCruditUi:Button variant="primary" icon="save" type="submit">Save</twig:LleCruditUi:Button>
<twig:LleCruditUi:Button href="{{ path('list') }}">Back to the list</twig:LleCruditUi:Button>
```

| Prop | Type | Default | |
|---|---|---|---|
| `variant` | `primary`, `secondary`, `ghost`, `warning`, `danger`, `danger-outline`, `link` | `secondary` | `warning` goes on a warning background, `link` looks like a link |
| `size` | `md`, `small` | `md` | 32 and 28 px high, 44 and 40 px on touch screens |
| `icon` | string | `null` | logical or Iconify name, before the label |
| `href` | string | `null` | renders an `<a>` instead of a `<button>` |
| `type` | `button`, `submit`, `reset` | `button` | |
| `disabled` | bool | `false` | `disabled` on a button; a link loses its `href` and gets `aria-disabled` |
| `loading` | bool | `false` | spinner in place of the icon, `aria-disabled` and `aria-busy`: the focus stays on the button, the label too, and the click is ignored (no second sending of the form) |
| `count` | int | `null` | counter after the label, read with it (`Filters 3`) |

Block: `content`, the label. The other attributes (`class`, `form`, `data-action`, `aria-keyshortcuts`…) go on the
element; `class` and `data-controller` are added to those of the component, the others replace its own.

To keep a disabled button focusable (a form without change), pass `aria-disabled="true"` instead of `disabled`: it
looks the same. The controller of the component, `2lenet--crudit-ui-bundle--button`, then ignores the click, Enter on
the button and Enter in a field of its form; only a `form.submit()` in JavaScript still sends it. A disabled button
does not change on hover.

A value of `variant` or `size` outside the list is not checked: the button has no style. The design kit shows each
value.

### LleCruditUi:IconButton

A button with an icon only. Its `label` is its accessible name, read but not shown: **required**, and it holds the
whole information (`Notifications (3 unread)`).

```twig
<twig:LleCruditUi:IconButton icon="edit" label="Edit {{ entity }}" href="{{ path('edit', {id: entity.id}) }}"/>
```

| Prop | Type | Default | |
|---|---|---|---|
| `icon` | string | required | |
| `label` | string | required | accessible name, visually hidden |
| `size`, `href`, `type`, `disabled`, `loading` | | | as for `Button` |
| `count` | int | `null` | badge on the corner of the icon, hidden from assistive technologies: put the count in `label` |

No block. A button that opens a panel (`aria-expanded="true"`) keeps the look of its hover.

### LleCruditUi:ButtonGroup

Buttons side by side, with a small gap: save and cancel of the edit in place, the bar of a form.

```twig
<twig:LleCruditUi:ButtonGroup label="{{ 'Edit in place'|trans }}">
    <twig:LleCruditUi:IconButton icon="check" label="Save" size="small" type="submit"/>
    <twig:LleCruditUi:IconButton icon="close" label="Cancel" size="small"/>
</twig:LleCruditUi:ButtonGroup>
```

| Prop | Type | Default | |
|---|---|---|---|
| `label` | string | `null` | with a label, a named group (`role="group"`); without, a layout only |

Block: `content`, the buttons.

### Status and badges

What describes a value, in a list or a record. **Never the colour alone**: a status has its label, a badge its text,
a boolean its Yes or No, a colour its code. `tone` takes `neutral`, `info`, `primary`, `success`, `warning`,
`danger`, a hue of the extended palette (`blue`, `teal`, `green`, `lime`, `yellow`, `orange`, `red`, `pink`,
`purple`, `indigo`, `gray`), or a colour of the project. The component takes the class `crudit-ui-tone--<tone>`, which
sets two variables that it reads. A project adds a colour with its two tokens, in light and dark (contrast of 4.5:1
at its charge), and one rule:

```css
.crudit-ui-tone--coral {
    --crudit-ui-tone-bg: var(--c-coral-bg);
    --crudit-ui-tone-text: var(--c-coral-text);
}
```

A value without its rule gives a component in the neutral colours.

```twig
<twig:LleCruditUi:Status tone="{{ state.tone }}">{{ state.label }}</twig:LleCruditUi:Status>
<twig:LleCruditUi:Badge tone="danger" label="visits to plan">12</twig:LleCruditUi:Badge>
<twig:LleCruditUi:Boolean :value="visit.done"/>
```

| Component | Props | Block | Accessibility |
|---|---|---|---|
| `LleCruditUi:Status` | `tone` (`neutral`), `size` (`md`, `small`) | `content`: the label | outlined pill; the dot is hidden, the label written |
| `LleCruditUi:Badge` | `tone` (`neutral`), `label` | `content`: the text | `label` is read after the text, not shown: a counter says what it counts (`12` + `visits to plan`) |
| `LleCruditUi:Boolean` | `value` (`true`, `false`, `null`), `yes`, `no` (translated Yes and No) | | icon hidden, Yes or No written; `null` is a dash read as "Not set" |
| `LleCruditUi:ColorSwatch` | `value` | | the swatch is hidden, the code written. The swatch is drawn only for a colour: a hexadecimal (`#0077a8`, `#07a`, with an alpha), `rgb()`, `rgba()`, `hsl()`, `hsla()` with numbers only, or a name (`teal`). Anything else (a value typed in a form, `red; background: url(…)`) gives an empty, dashed swatch |
| `LleCruditUi:Tag` | `pressed`, `busy` | `content`: the label | toggle button (`aria-pressed`): solid border, check and weight when associated; `busy` shows a spinner and ignores the click. The project switches the props; group the tags in a `role="group"` named by their title |
| `LleCruditUi:Chip` | `removeLabel`, `removable` (`true`) | `content`: the text | an active filter, in a `<ul class="crudit-ui-chips">` named "Active filters"; the remove button is named "Remove the filter " and the text of the chip ("Remove the filter Status: planned"), or by `removeLabel`. Its attributes are passed with the `remove:` prefix: `remove:data-action="filters#remove"` |
| `LleCruditUi:Avatar` | `name` (required), `size` (`small`, `md`, `large`), `impersonated` | | the initials (two at most) are hidden: write the name next to it. The impersonation is read ("(impersonation)") |
| `LleCruditUi:ProgressBar` | `value`, `label` (required), `max` (`100`), `tone` (`primary`, `success` when `value` reaches `max`), `valueText` (the percentage in the format of the locale: `45 %` in French) | | the native `<progress>`, named by the label; the value is written. Above `max`, the bar is full and `valueText` says the real value. Write the level too when it matters: `valueText="85 %, to watch"`. An `id` passed by the project names the label too (`<id>-label`) |

### Loading, empty and messages

What concerns a whole zone: a list, a widget, a form.

```twig
<div aria-busy="true">
    <span class="crudit-ui-visually-hidden">{{ 'Loading the visits'|trans }}</span>
    <twig:LleCruditUi:Skeleton variant="title"/>
    <twig:LleCruditUi:Skeleton/>
</div>

<twig:LleCruditUi:EmptyState title="{{ 'No visit yet'|trans }}">
    {{ 'Visits appear here when they are created.'|trans }}
    <twig:block name="actions"><twig:LleCruditUi:Button variant="primary" icon="add" href="{{ path('visit_new') }}">{{ 'Create a visit'|trans }}</twig:LleCruditUi:Button></twig:block>
</twig:LleCruditUi:EmptyState>

<twig:LleCruditUi:Alert tone="danger" title="{{ 'The list could not be loaded'|trans }}" role="alert">…</twig:LleCruditUi:Alert>
```

| Component | Props | Blocks | Accessibility |
|---|---|---|---|
| `LleCruditUi:Spinner` | `label` (translated "Loading…") | | `role="status"`: the label is read, not shown |
| `LleCruditUi:Skeleton` | `variant` (`line`, `title`, `value`, `cell`) | | hidden; **its container** has `aria-busy="true"` and a hidden text. Not animated when motion is reduced |
| `LleCruditUi:EmptyState` | `title`, `icon` (`empty`; `null` for none), `compact`, `level` (`3`, level of the title, from 2 to 6) | `content`: the text, paragraphs or a list; `actions` | the icon is hidden; give the title the level that fits the page |
| `LleCruditUi:Alert` | `tone` (`info`, `success`, `warning`, `danger`; `info`), `title`, `inline`, `dismissible` | `content`: the text, paragraphs or a list; `actions` | the icon is hidden; the type is read first ("Error:"), before the title if there is one. Give an error `role="alert"` (announced at once); a message that appears, `role="status"`. `dismissible` adds a close button (controller `alert`): it dispatches `2lenet--crudit-ui-bundle--alert:close` (cancelable), removes the alert, and puts the focus on the closest focusable container, or on `<main>` |

### LleCruditUi:Kit:Section

A section of the design kit page, with its title; used by the kit only.

| Prop | Type | Default | |
|---|---|---|---|
| `id` | string | required | anchor `kit-<id>`, also the id of the title |
| `title` | string | required | title of the section (`h2`) |

Blocks: `content`. Accessibility: a `section` named by its `h2` (`aria-labelledby`).

## Content Security Policy

The components write no `style` attribute and no script: they need neither `'unsafe-inline'` in `style-src` nor in
`script-src` (their behaviour is in Stimulus controllers). The colour of a swatch is an SVG `fill`, the progress bar
is the native `<progress>`. Only the design kit page, for development, has style attributes (the swatches and scales
of the tokens). The rest of the page is the project's business: with AssetMapper, `importmap()` writes inline scripts,
which need a nonce under a strict policy.

## Overriding a component

A project replaces a component of the bundle as any template of a bundle, in `templates/bundles/LleCruditUiBundle/`:

```
templates/bundles/LleCruditUiBundle/components/Kit/Section.html.twig
```

It keeps the props and the blocks of the [contract](#contract-of-a-component). The design kit page is overridden the
same way (`templates/bundles/LleCruditUiBundle/kit/index.html.twig`).

## Design kit

The design kit page shows the tokens and every component in its states (forced hover and focus, disabled,
loading…), in the theme and the language of the page. It is meant for development: import its routes under
`when@dev`, in `config/routes/crudit_ui_kit.yaml`:

```yaml
when@dev:
    crudit_ui_kit:
        resource: '@LleCruditUiBundle/config/routes/kit.php'
```

The page is then at `/crudit-ui/kit` (prefix it with `prefix:` if needed), and in another language at
`/crudit-ui/kit/<locale>` (`/crudit-ui/kit/ar` for right to left). It extends the `base.html.twig` of the project,
which loads the stylesheet and the controllers of the bundle (see [Installation](installation.md#assets)), and declares
`<meta name="viewport" content="width=device-width, initial-scale=1">`: without it, a phone lays the page out on
980 px and shrinks it.

The styles of the page are in `ui.css` (`components/_kit.scss`, about 0.7 KB compressed), loaded in production
too: the page then works with Encore and AssetMapper alike, without a stylesheet of its own.

Each group of components has its section: `templates/kit/_<id>.html.twig`, listed in `sections` of
`templates/kit/index.html.twig` and wrapped in `<twig:LleCruditUi:Kit:Section>`. The test applications import the routes in
every environment, and their smoke test audits the page with axe-core (see [Contributing](development.md)).
