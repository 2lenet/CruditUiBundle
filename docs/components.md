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
| `loading` | bool | `false` | spinner in place of the icon, `aria-disabled` and `aria-busy`: the focus stays on the button, the label too, and the click is ignored (no second sending of the form). A loading link loses its `href`, as a disabled one: neither followed (click, middle click, Ctrl+click) nor focused |
| `busyOnSubmit` | bool | `true` for `type="submit"` | once the button sends its form, it is put in loading: a double click, or Enter pressed twice, sends the form once. It goes back to normal if the submit is cancelled (`preventDefault()`, by any listener), when Turbo has sent the form (`turbo:submit-end`), and when the Back button restores the page. Nothing for a form sent to another window or frame (`target`, `formtarget`), nor for a `<form method="dialog">`, which sends nothing (it closes its dialog). Pass `:busyOnSubmit="false"` when the page does not change after the submit and the bundle cannot know it: a form sent by the project in Ajax (it handles `loading`), an **export** whose answer is a downloaded file |
| `count` | int | `null` | counter after the label, read with it (`Filters 3`) |

Block: `content`, the label. The other attributes (`class`, `form`, `data-action`, `aria-keyshortcuts`…) go on the
element; `class` and `data-controller` are added to those of the component, the others replace its own.

Events of its controller: `2lenet--crudit-ui-bundle--button:busy` and `…:idle`, when it goes in loading (busyOnSubmit)
and back.

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
| `size`, `href`, `type`, `disabled`, `loading`, `busyOnSubmit` | | | as for `Button` |
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
| `LleCruditUi:Status` | `tone` (`neutral`), `size` (`md`, `small`: less padding, same text size, as in the mockup) | `content`: the label | outlined pill; the dot is hidden, the label written |
| `LleCruditUi:Badge` | `tone` (`neutral`), `label` | `content`: the text | `label` is read after the text, not shown: a counter says what it counts (`12` + `visits to plan`) |
| `LleCruditUi:Boolean` | `value` (`true`, `false`, `null`), `yes`, `no` (translated Yes and No) | | icon hidden, Yes or No written; `null` is a dash read as "Not set" |
| `LleCruditUi:ColorSwatch` | `value` | | the swatch is hidden, the code written. The swatch is drawn only for a colour: a hexadecimal (`#0077a8`, `#07a`, with an alpha), `rgb()`, `rgba()`, `hsl()`, `hsla()`, `hwb()`, `lab()`, `lch()`, `oklab()`, `oklch()` with numbers and units only (`hsl(200deg 50% 40%)`; no parenthesis, semicolon, quote nor colon inside, so no `url()`), or a name (`teal`). Anything else (a value typed in a form, `red; background: url(…)`) gives an empty, dashed swatch |
| `LleCruditUi:Tag` | `pressed`, `busy` | `content`: the label | toggle button (`aria-pressed`): solid border, check and weight when associated; `busy` shows a spinner and ignores the click. The project switches the props; group the tags in a `role="group"` named by their title |
| `LleCruditUi:Chip` | `removeLabel`, `removable` (`true`) | `content`: the text only (it is rendered twice: shown, and in the name of the button) | an active filter, in a `<ul class="crudit-ui-chips">` named "Active filters"; the remove button is named "Remove the filter " and the text of the chip ("Remove the filter Status: planned"), or by `removeLabel`. Its attributes are passed with the `remove:` prefix: `remove:data-action="filters#remove"` |
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
| `LleCruditUi:Alert` | `tone` (`info`, `success`, `warning`, `danger`; `info`), `title`, `inline`, `dismissible` | `content`: the text, paragraphs or a list; `actions` | the icon is hidden; the type is read first ("Error:"), before the title if there is one. Give an error `role="alert"` (announced at once); a message that appears, `role="status"`. `dismissible` adds a close button (controller `alert`): it dispatches `2lenet--crudit-ui-bundle--alert:close` (cancelable) and removes the alert. If the focus was in the alert, it goes to the closest focusable container, or to `<main>`, which then keeps `tabindex="-1"`; a close called from elsewhere leaves the focus where it is |

### Menus and tooltips

#### LleCruditUi:Dropdown

A button that opens a panel: the actions of a row or of a page, the user menu, a choice of columns. A **disclosure**,
not an ARIA menu (`role="menu"`): the panel holds links, buttons or fields, Tab goes from one to the next, there are
no arrow keys.

```twig
<twig:LleCruditUi:Dropdown label="{{ 'More actions for %name%'|trans({'%name%': visit}) }}" iconOnly floating>
    <twig:LleCruditUi:Menu>
        <twig:LleCruditUi:MenuItem icon="edit" href="{{ path('visit_edit', {id: visit.id}) }}">{{ 'Edit'|trans }}</twig:LleCruditUi:MenuItem>
        <twig:LleCruditUi:MenuItem icon="delete" tone="danger" data-action="visit#delete">{{ 'Delete'|trans }}</twig:LleCruditUi:MenuItem>
    </twig:LleCruditUi:Menu>
</twig:LleCruditUi:Dropdown>
```

| Prop | Type | Default | |
|---|---|---|---|
| `label` | string | required | text of the button; with `iconOnly`, its accessible name, not shown: name the element (`More actions for V-0412`) |
| `icon` | string | `null` | before the label; with `iconOnly`, the icon of the button (`more`, ⋮, by default) |
| `iconOnly` | bool | `false` | an icon button, as `IconButton` |
| `variant`, `size` | | `secondary`, `md` | as for `Button` (not with `iconOnly`, which takes `size` only) |
| `align` | `end`, `start` | `end` | edge of the button the panel is aligned on, in the reading direction |
| `floating` | bool | `false` | the panel in `position: fixed`, placed next to the button by the controller: it is not cut by a container that scrolls or hides its overflow (a table, a card). It closes when the page scrolls, and follows the button when the window is resized or when the page scrolls while the focus is in the panel (the virtual keyboard of a phone, opened by a field); then it closes if the button leaves the screen, unless a field of the panel has the focus |

Blocks: `content`, the panel (a `Menu`, or anything: `crudit-ui-dropdown__header`, `__title`, `__body` and `__footer`
lay out a panel with a title and buttons); `trigger`, the content of the button in place of the icon, the label and the
chevron (an avatar and a name). Attributes: on the root; `trigger:` on the button (`trigger:data-action`, added to the
toggle), `panel:` on the panel (`panel:class`). The `id` passed by the project, if any, names the panel too
(`<id>-panel`).

Controller `2lenet--crudit-ui-bundle--dropdown`, events `…dropdown:open` and `…dropdown:close` on the root. A project
that writes its own markup gives the controller to a container, the target `button` and the action `toggle` to the
button (with `aria-expanded="false"`), the target `panel` to the panel (with `hidden`).

Accessibility: `aria-expanded` and `aria-controls` on the button. Escape closes the panel and gives the focus back to
the button (and stops there, its default prevented: a dialog around, a native `<dialog>` too, stays open); a click
outside or the focus leaving the dropdown close it; the choice of an item of a `Menu` closes it and gives the focus
back to the button, before the action of the item runs: a dialog that the action opens takes the focus, and gives it
back to the button. While an item sends a form (`type="submit"`), the button of the dropdown is in loading (its spinner
takes the place of the icon, or of the chevron). A dropdown in the panel of another one handles its own items: the
choice of one of them, or its loading, leaves the outer one as it is. The panel
closes when the controller disconnects and before Turbo caches the page. The panel is shifted to stay in the
screen; the root takes `crudit-ui-dropdown--open` while it is open, so that a sticky container (a cell, a bar) rises
above its neighbours: `.my-sticky:has(.crudit-ui-dropdown--open) { z-index: … }`.

#### LleCruditUi:Menu, LleCruditUi:MenuItem, LleCruditUi:MenuGroup

The list of a `Dropdown`: a `<ul>` of links and buttons.

```twig
<twig:LleCruditUi:Menu>
    <twig:LleCruditUi:MenuGroup label="{{ 'Export'|trans }}">
        <twig:LleCruditUi:MenuItem icon="file-csv" href="{{ path('visit_export', {format: 'csv'}) }}">CSV</twig:LleCruditUi:MenuItem>
    </twig:LleCruditUi:MenuGroup>
    <twig:LleCruditUi:MenuItem icon="locked" reason="{{ 'Only a finished visit can be archived'|trans }}">{{ 'Archive'|trans }}</twig:LleCruditUi:MenuItem>
</twig:LleCruditUi:Menu>
```

| Component | Props | Block | Accessibility |
|---|---|---|---|
| `LleCruditUi:Menu` | | `content`: the items | a list, not `role="menu"` |
| `LleCruditUi:MenuItem` | `icon`, `href` (a link, else a button), `type` (`button`), `tone` (`danger`: a destructive action, confirmed in a dialog that names the element), `disabled`, `reason` (why it is disabled: written under the label; disables the item), `current` (`null`: no check; `true`: checked; `false`: the room of the check is kept, in a list of options such as the languages) | `content`: the label | the attributes go on the button or the link (`data-action`, `form`, `hreflang`…), not on the `<li>`. Disabled: `aria-disabled`, still focusable, its reason read with its label; the click is ignored (controller `button`). A disabled link loses its `href` and is rendered as a button. `current`: `aria-current="true"`, the check is hidden |
| `LleCruditUi:MenuGroup` | `label` (required) | `content`: the items | a heading, then a list named by it; a rule sets the group apart |

#### LleCruditUi:Tooltip

A short text shown on the hover and the focus of the element it wraps: the name of an icon button, the meaning of a
pictogram. **Visual only** (`aria-hidden`): its text must also be the accessible name of the element (`label` of an
`IconButton`) or its description (`aria-describedby`). Never essential information nor links: it is not reachable on
a touch screen.

```twig
<twig:LleCruditUi:Tooltip text="{{ 'Edit'|trans }}">
    <twig:LleCruditUi:IconButton icon="edit" label="{{ 'Edit'|trans }}" href="{{ path('visit_edit', {id: visit.id}) }}"/>
</twig:LleCruditUi:Tooltip>
```

| Prop | Type | Default | |
|---|---|---|---|
| `text` | string | required | |
| `placement` | `above`, `below`, `beside` | `above` | `beside`: on the inline end side, centred (the collapsed menu bar); always floating |
| `align` | `end`, `start` | `end` | edge of the element it is aligned on, above or below |
| `floating` | bool | `false` | in `position: fixed`, placed by the controller when it is shown, and again when the page scrolls: inside a container that hides its overflow |

Block: `content`, the element (focusable). Attributes: on the host (`<span class="crudit-ui-tooltip-host">`).
Controller `2lenet--crudit-ui-bundle--tooltip`.

Accessibility (WCAG 1.4.13, RGAA 10.13): it can be hovered without closing; Escape hides the tooltips until the next
move of the pointer or of the focus (the class `crudit-ui-tooltips-dismissed` on `<html>`); it goes while the menu of
its button is open.

### Dialogs and drawer

#### LleCruditUi:Dialog

A modal window: the confirmation of an action, an action with fields, an export; with `variant="drawer"`, a side
panel (the filters on a phone, the configuration of a widget). A native `<dialog>`, opened with `showModal()`: the
browser keeps the focus in it, makes the rest of the page inert (neither clicked nor read), and gives the focus back to
the button that opened it. **Its title names the element concerned** ("Delete the visit V-0412?"); a destructive action
is confirmed in one.

```twig
<twig:LleCruditUi:Button variant="danger-outline" icon="delete" commandfor="visit-delete" command="show-modal">{{ 'Delete'|trans }}</twig:LleCruditUi:Button>

<twig:LleCruditUi:Dialog id="visit-delete" role="alertdialog" title="{{ 'Delete the visit %name%?'|trans({'%name%': visit}) }}" description="{{ 'This action cannot be undone.'|trans }}">
    <form id="visit-delete-form" method="post" action="{{ path('visit_delete', {id: visit.id}) }}">…</form>
    <twig:block name="footer">
        <twig:LleCruditUi:Button commandfor="visit-delete" command="close" autofocus>{{ 'Cancel'|trans }}</twig:LleCruditUi:Button>
        <twig:LleCruditUi:Button variant="danger" icon="delete" type="submit" form="visit-delete-form">{{ 'Delete'|trans }}</twig:LleCruditUi:Button>
    </twig:block>
</twig:LleCruditUi:Dialog>
```

| Prop | Type | Default | |
|---|---|---|---|
| `title` | string | required | the title (`h2`) and the accessible name of the dialog |
| `description` | string | `null` | under the title, read with it (`aria-describedby`): what the action does, what it changes |
| `variant` | `dialog`, `drawer` | `dialog` | `dialog`: centred, 32rem wide at most. `drawer`: on the inline end side (the left one in right to left), the whole height, 28rem wide, the whole width on a phone |
| `open` | bool | `false` | shown modal when the page loads: a form of the dialog sent back with its errors; also when a morph of Turbo puts it back. Never the attribute `open` of `<dialog>`, which shows a dialog that is not modal |

Blocks: `content`, the body, which scrolls (paragraphs, a form); `footer`, the buttons, hidden when empty (one primary
button, as in any zone). Attributes: on the `<dialog>` (`role="alertdialog"` for a confirmation, `class`, `data-`).
**Give it an `id`**: the target of the buttons, it names the title (`<id>-title`) and the description
(`<id>-description`). Without one, an id is generated, different at each rendering: no button can target it, and the
HTML changes from one rendering to the next.

**Open and close it** with the attributes of HTML, `commandfor="<id>"` and `command` on a `<button type="button">`
(a `Button`): `show-modal` opens it, `close` closes it (the `value` of the button becomes the `returnValue` of the
dialog), `request-close` closes it as Escape does. The browsers run them (Chrome 135, Firefox 144, Safari 26.2); the
controller runs them in the older ones, and dispatches on the dialog the same cancelable `command` event (`command`,
`source`). A `<form method="dialog">` in the dialog closes it too, without sending anything: its
submit button is then not put in loading (`busyOnSubmit`). In JavaScript, `dialog.showModal()` and `dialog.close()`,
or the actions `open`, `close` and `dismiss` of the controller.

Controller `2lenet--crudit-ui-bundle--dialog`, on the `<dialog>`. Events `…dialog:open` and `…dialog:close` on the
dialog, however it opens or closes; the native `cancel` and `close` of `<dialog>` are there too. **Escape, the close
button and a click on the backdrop fire `cancel` first**: a project cancels it (`preventDefault()`) to keep the dialog
open (a form with changes, to confirm). Chrome closes it all the same without a click or a key of the user before
(a `dismiss` called by a timer), or on a second try with none in between. A press in the dialog released on the backdrop (a selection of text) does not
close it. The dialog closes when the controller disconnects and before Turbo caches the page, also when it is moved in
the page (Stimulus disconnects it, and a modal dialog moved is no longer modal): do not move an open dialog. A page
refreshed by a morph of Turbo: the dialog follows the server, closed unless it has `open` (it then stays open as it
is, the focus where it was); the morph would otherwise remove the attribute `open` and leave the dialog hidden but
still modal, the page inert. A dialog to keep open across refreshes (a broadcast refresh) takes
`data-turbo-permanent`.

Accessibility: `role="dialog"` (native) or `alertdialog`, named by its title, described by its description. **Initial
focus**: on the element with `autofocus`, else on the first focusable one (the close button). Put `autofocus` on
Cancel in a confirmation (the least destructive choice), on the first field in a form. Tab stays in the dialog (the
browser may give it to its own interface on the way, never to the page). Escape closes it, except when a dropdown of
the dialog is open: Escape closes the dropdown first. The focus goes back to the button that opened it; if that one is
gone (the row it deleted), to `<main>`, given `tabindex="-1"`. The page under it does not scroll. The opening is
animated (fade, or slide of the drawer), not when motion is reduced.

### Toasts

#### LleCruditUi:Toasts and LleCruditUi:Toast

A short message on the result of an action: "The visit is saved.", "The server refused the record.". The flash
messages of the session become toasts. On the top inline end side (the left one in right to left), under the header
(`--topbar-h`), the whole width on a phone. **Not for what the user must read to go on** (the errors of a form go in
the form, an `Alert` of the zone): a toast goes away.

The region, **once per page**, in its layout:

```twig
<twig:LleCruditUi:Toasts/>
```

| Prop | Type | Default | |
|---|---|---|---|
| `flashes` | bool | `true` | shows the flash messages of the session (`app.flashes`, which reads them once): `success`; `info` and `notice`; `warning`; `danger` and `error` (an error); another type is an information. The message is translated (`trans`, domain `messages`) and **escaped**: a message that holds HTML shows its tags |
| `duration` | int | `6000` | ms before a toast that is not persistent goes |
| `max` | int | `4` | toasts at most (1 at least); beyond, the oldest that is not persistent goes (never the one just added) |

Block: `content`, toasts of the page (`Toast`). Attributes: on the region; its `id` is `crudit-ui-toasts` by default,
the target of a Turbo Stream (`<turbo-stream action="append" target="crudit-ui-toasts">` with a `Toast` in its
template).

A toast:

```twig
<twig:LleCruditUi:Toast tone="success">{{ 'The visit is saved.'|trans }}</twig:LleCruditUi:Toast>
```

| Prop | Type | Default | |
|---|---|---|---|
| `tone` | `info`, `success`, `warning`, `danger` | `info` | icon, colour, and the type read first ("Error:") |
| `persistent` | bool | `true` for `danger` | stays until it is closed. An error is always announced at once, persistent or not |

Block: `content`, the message (text, one or two short sentences). Attributes: on the toast. Outside a region, a toast
is only shown (neither announced nor removed).

**From the JavaScript** (an edit in place, an action in Ajax), an event on the document:

```js
document.dispatchEvent(new CustomEvent('crudit-ui:toast', {detail: {message: 'The visit is saved.', tone: 'success'}}));
```

`detail`: `message` (text, never read as HTML), `tone` (`info` by default), `persistent` (to change the default). The
toast is a copy of a template of the region, with its icon and its translated texts: the JavaScript has no text of
its own to translate, only the message. With several regions in the page (a page that adds its own to a layout that
has one: avoid it), the one in the open modal dialog takes the event, else the first one of the page.

Controller `2lenet--crudit-ui-bundle--toasts`, on the region; action `dismiss` on the close button of a toast.

- **In a modal dialog**: the page under it is inert, a toast there would be neither seen, nor read, nor closed. A toast
  added while a dialog is open moves the region into the dialog (in `position: fixed`, it stays in its place on the
  screen), once the opening of the dialog is over, until the dialog closes; then the region goes back with its toasts. A toast already shown when a dialog
  opens stays under it.
- **Turbo**: each page has its region and its flash messages; the toasts are removed before Turbo caches the page (a
  page shown again by Back does not show them again).

Accessibility: each new toast is announced, a little after it appears, by one of the two hidden live regions of the
region: polite, assertive for an error (the type first, "Error: The server refused the record."). The toasts are not a
live region themselves: a region moved into a dialog would read them again; the hidden live regions are emptied when
the region moves, for the same reason. A toast that is not persistent goes after
6 seconds; **the time stops** while the pointer is on the region or the focus in it (WCAG 2.2.1), and while the page is
hidden. An error stays until it is closed. The close button ("Close the message") is a real button; when it is
pressed, the focus goes to the next toast, else to the close button of the dialog (or the dialog), to the closest focusable
container or to `<main>`. The toasts appear with a slide, not when motion is reduced; they are not printed.

### Command palette

#### LleCruditUi:CommandPalette

A search field in a window, which asks the server for results **by groups** as you type and opens the one that is
chosen: the global search of an application ("a client, an invoice, a screen"). The component knows nothing of what is
searched: the project (or CruditBundle) answers the address `url`. A native `<dialog>` (controller `dialog`: focus
trapped, <kbd>Esc</kbd>, click on the backdrop, focus given back) and an ARIA combobox (controller `command-palette`).
The whole screen on a phone, a window from `md`.

```twig
<twig:LleCruditUi:Button icon="search" commandfor="global-search" command="show-modal" aria-keyshortcuts="Control+K Meta+K">{{ 'Search'|trans }}</twig:LleCruditUi:Button>
<twig:LleCruditUi:CommandPalette id="global-search" url="{{ path('app_search') }}" title="{{ 'Global search'|trans }}" placeholder="{{ 'Client, invoice, screen…'|trans }}"/>
```

| Prop | Type | Default | |
|---|---|---|---|
| `url` | string | required | `GET <url>?q=<text>`, answers `{"groups": [{"id", "label", "results": [{"id", "title", "subtitle", "url"}]}]}`; `subtitle` is optional, a group without result is not shown, a result whose `url` is not http or https (`javascript:`…) is ignored |
| `title` | string | required | names the dialog (read, not shown) |
| `label` | string | `title` | names the field |
| `placeholder` | string | none | |
| `help` | string | none | what is searched, read with the field (`aria-describedby`) after "Enter at least 2 characters." |
| `minLength` | int | `2` | characters before the first request |
| `shortcut` | bool | `true` | <kbd>Ctrl</kbd> <kbd>K</kbd> and <kbd>Cmd</kbd> <kbd>K</kbd> open the palette, on every page, unless another dialog is open. The browser's own shortcut (the address bar of Firefox) is replaced. **A page has one palette with the shortcut** |

Its `id` is the target of `commandfor`; the attributes go on the `<dialog>`.

Behaviour: a request starts 200 ms after the last key; a newer one cancels the one still running, whose answer is never
shown. The text of the results is **set as text, never as HTML**; the text typed is marked, as one piece and at its first place only (`<mark>`, without regard
to the accents and the case). The up and down arrows (and <kbd>Ctrl</kbd> + <kbd>Home</kbd> / <kbd>End</kbd>) change
the active option, from the last to the first and back; **nothing opens before it is chosen** (<kbd>Enter</kbd> or a
click), by Turbo Drive when the page has it, else by the browser. The event `2lenet--crudit-ui-bundle--command-palette:select`
(`detail.result`, cancelable) lets the project open the result itself. The field is emptied when the dialog closes.

Accessibility: the combobox pattern (the focus stays in the field, `aria-activedescendant` names the active option,
`aria-expanded`, groups named by their title); the count of results is announced in a hidden live region 600 ms after
the last answer, as are "no result" and the error of the search (shown in the window, in `role="alert"`); the keys are
repeated in the footer, hidden from assistive technologies and from a phone.

### Collapsibles and tabs

#### LleCruditUi:Disclosure

A button that shows or hides a panel: sections of a record, sub-menus, a FAQ. It is **the only collapsible** of the
bundle: use it for a FAQ too (no styled `<details>`), the look stays the same everywhere.

```twig
<twig:LleCruditUi:Disclosure title="{{ 'Address'|trans }}" :level="2" open>
    …
</twig:LleCruditUi:Disclosure>
```

| Prop | Type | Default | |
|---|---|---|---|
| `title` | string | required | text of the button |
| `open` | bool | `false` | state when the page is rendered |
| `level` | int | `null` | from 2 to 6: the button is in a heading of that level (a section of a record). `null`, or another value: the button alone (a sub-menu) |
| `icon` | string | `dropdown` | the chevron: down when closed, up when open (turned by 180°, the same right to left) |
| `id` | string | random | base of the ids: the panel is `<id>-panel`. Give one for an anchor or a test, and for a disclosure rendered again (a Live Component, a morph of Turbo): without one, the ids change at each rendering |

Block: `content`, the panel. The button is a `<button type="button">` with `aria-expanded` and `aria-controls`: Enter
and Space work, and the focus stays on it. The server renders the state: the page is right before the JavaScript.
A closed panel has the class `crudit-ui-disclosure__panel--closed`, not the `hidden` attribute: `[hidden]` of the
reset is `!important`, the print could not open it. **Printed open.** The state is not remembered.

Controller `2lenet--crudit-ui-bundle--disclosure`: actions `toggle`, `open` and `close`, and the event
`2lenet--crudit-ui-bundle--disclosure:toggle` (`detail.open`) when the state changes. A disclosure inside another
one (a sub-menu of a sub-menu) has its own controller: it opens and closes by itself.

#### LleCruditUi:Tabs

Tabs, ARIA pattern: a `tablist` named by `label`, one `tab` per entry, one `tabpanel` per tab. All the panels are in
the page; only the selected one is shown.

```twig
<twig:LleCruditUi:Tabs id="visit" label="{{ 'Visit'|trans }}" urlParam="tab" :tabs="[
    {id: 'general', label: 'General'|trans},
    {id: 'parts', label: 'Parts'|trans, count: visit.parts|length},
]">
    <twig:block name="general">…</twig:block>
    <twig:block name="parts">…</twig:block>
</twig:LleCruditUi:Tabs>
```

| Prop | Type | Default | |
|---|---|---|---|
| `id` | string | required | base of the ids: `<id>-tab-<tab>`, `<id>-panel-<tab>` |
| `label` | string | required | name of the list of tabs, read by screen readers |
| `tabs` | list of `{id, label, count?}` | required | `id` names the block of the panel: letters, digits and `_`, starting with a letter or `_`, as a Twig block (`2024` or `1_general` cannot name a block). A tab without its block is an error of Twig (`Block "parts" … does not exist`). `count`: a counter after the label, read with it (`Parts 3`), like the one of a button; `0` is shown |
| `selected` | string | the first tab | id of the selected tab; an unknown id gives the first one |
| `urlParam` | string | `null` | the address follows the tab: `?<urlParam>=<id>` |

Keyboard: Tab reaches the selected tab only, then the panel (`tabindex="0"`, as the ARIA pattern advises: a panel that starts with text is reached too; in a panel that starts with a field, it is one more stop before the field). ← and → select the next or previous tab at once (they
follow the screen: right to left, → goes to the previous tab), and loop; Home and End go to the first and the last
one. Controller `2lenet--crudit-ui-bundle--tabs`, event `2lenet--crudit-ui-bundle--tabs:change` (`detail.id`).

**The tab chosen by the server.** With `urlParam`, the component reads the parameter of the request: a valid tab
in the address wins over `selected`, and a reload, a bookmark or a shared link opens the same tab. After a form saved
in a tab, redirect to the address with the parameter (`path('visit_show', {id: visit.id, tab: 'parts'})`). The
address changes without a new entry in the history: Back leaves the page, it does not go through the tabs (five tabs
seen would need five Back to leave).

Printed: the selected tab only, as on the screen (and in the mockup). A project that needs all of them on paper
prints a page made for it.

Not in this component: a panel loaded when it is opened, the title of the window per tab (dashboard, lot 4.2).

### Page header and breadcrumb

#### LleCruditUi:PageHeader

The header of a page: the breadcrumb, the only `<h1>` of the page with its status, a subtitle, metadata and the
actions. Nothing about Crudit: the application gives the title and the levels of the breadcrumb.

```twig
<twig:LleCruditUi:PageHeader title="{{ visit.reference }}" subtitle="{{ visit.client }}" :breadcrumb="[
    {label: 'Home'|trans, url: path('home')},
    {label: 'Visits'|trans, url: path('visit_index')},
    {label: visit.reference},
]">
    <twig:block name="badge"><twig:LleCruditUi:Status tone="warning">{{ 'To plan'|trans }}</twig:LleCruditUi:Status></twig:block>
    <twig:block name="meta">{{ 'Created on %date%'|trans({'%date%': visit.createdAt|ui_date}) }}</twig:block>
    <twig:block name="actions"><twig:LleCruditUi:Button icon="edit" href="…">{{ 'Edit'|trans }}</twig:LleCruditUi:Button></twig:block>
</twig:LleCruditUi:PageHeader>
```

| Prop | Type | Default | |
|---|---|---|---|
| `title` | string | required | text of the `<h1>` |
| `subtitle` | string | `null` | under the title, muted; not rendered when null or empty |
| `breadcrumb` | list of `{label, url?}` | `[]` | items of the `Breadcrumb` (see below): none with fewer than two |
| `titleId` | string | `page-title` | id of the `<h1>` (an `id` passed by the project goes on the root, as for the other components) |

Blocks: `breadcrumb` (the `Breadcrumb` of the prop `breadcrumb`: give the block to replace it), `top` (at the end of
the line of the breadcrumb: a navigation between records…), `badge` (next to the title: a `Status`), `meta` (under the
subtitle, small and muted: dates, author), `actions` (at the end of the line of the title; under it below 576 px). The
zones left empty are not shown: `top` and `actions` without an element (and `top` below 768 px when the breadcrumb is
hidden), `meta` when it renders nothing but spaces.

Accessibility: **one `<h1>` per page**, this one (the design kit is the only exception: its examples add theirs). It
has `tabindex="-1"`, so that the application can give it the focus when the page changes without a full load (Turbo):
a screen reader then starts at the title. **The component does not do it**: it is the job of the application
(CruditBundle does it for its pages). The title has no focus ring, it is not a control.

One `PageHeader` per page: its `<h1>` has the id `page-title`. Two in the same page (a page under Turbo with a drawer
that holds another one): give the second its own `titleId`.

#### LleCruditUi:Breadcrumb

Where the page is, from the home page: a `nav` named "Breadcrumb", an ordered list, a chevron between the levels.

```twig
<twig:LleCruditUi:Breadcrumb :items="[
    {label: 'Home'|trans, url: path('home')},
    {label: 'Operations'|trans},
    {label: 'Visits'|trans},
]"/>
```

| Prop | Type | Default | |
|---|---|---|---|
| `items` | list of `{label, url?}` | required | from the home page to the current page; `label` already translated. The last one is the current page: never a link, `aria-current="page"`. Another one without `url` is a text (a group of the menu) |
| `label` | string | `crudit_ui.breadcrumb.label` | name of the `nav` ("Fil d'Ariane", "Breadcrumb"); an empty one gives the default too |

**Nothing is rendered with fewer than two items** (the home page: a breadcrumb of one level says nothing).
**Below 768 px**, only the last level with an url before the current page is shown, as a link "← parent" (arrow
`back`); without such a level, the whole `nav` is hidden (no empty landmark). The link may skip levels without an
url: Home › Operations (no url) › Settings gives "← Home". The chevrons and the arrow are hidden
from screen readers and follow the direction of reading. The levels wrap, they are never cut.

### Table

`LleCruditUi:Table`, with `LleCruditUi:Table:Row` and `LleCruditUi:Table:Actions`: the rows of a list, as a table from `md`
up and as cards below (the same data, the CSS shows one of the two). It is the table of the list and the sublist of
CruditBundle and of the List widget of DashboardBundle. It knows nothing of `CrudConfig`: the caller gives **columns and
rows as arrays**, with the cells already rendered.

```twig
<twig:LleCruditUi:Table id="visits" caption="Visits, sorted by date" :columns="columns" :rows="rows" :sort="{column: 'date', dir: 'asc'}" selectable>
    <twig:block name="footer">{# the pager #}</twig:block>
</twig:LleCruditUi:Table>
```

| Prop | Type | Default | |
|---|---|---|---|
| `id` | string | required | base of the ids (box of the page, help of the order, column help) |
| `caption` | string | required | read, not shown: title, sort, page |
| `columns` | list of columns | required | see below |
| `rows` | list of rows | `[]` | the rows of the page |
| `groups` | list of groups | `[]` | replace `rows`: one or two levels, each with its subtotals |
| `totals` | list of totals | `[]` | aligned on the columns; `standalone` ones are listed under the table |
| `totalLabel` | string | translated "Total" | label of the totals row |
| `sort` | `{column, dir}` | `null` | the current sort: `aria-sort` of its column (`asc`, `desc`) |
| `selectable` | bool | `false` | a column of boxes named after the row, and a box for the page |
| `sortable` | bool, or `{url, disabledReason}` | `false` | manual order of the rows (inside each group); `disabledReason`: why it cannot change now (a filter is active) |
| `state` | `ready`, `loading`, `empty`, `error` | `ready` | `empty` is also chosen when there is no row |
| `retryUrl` | string | `null` | the "Try again" link of the `error` state |

**Column**: `id` (key of `row.cells`), `label` (already translated), `sortUrl` (a link: the sort works without
JavaScript; none: not sortable, no `aria-sort`), `align` (`start`, `end` for numbers and amounts), `primary` (main
column: `<th scope="row">`, link to the record, title of the card, sticky), `priority` (`medium` hidden below `lg`, `low`
below `xl`, in the table only), `card` (shown in the card), `hidden` (not rendered), `info` (help in a dropdown, a button
named "Help on the column …").

**Row**: `id`, `domId` (the `id` attribute of the row), `label` (names the box, the actions and the handle), `url` (the row is clickable with the mouse, the link of
the main cell does the job by keyboard), `tone` (`warning`, `danger`, `success`, `info`: a tinted background, the state is
also written in a cell), `selected`, `actions`, `cells` (column id → a string, which is text and is escaped, or
`{html, secondary, url, id}`). **`html` is rendered as it is: the caller escapes it.** `secondary` is a second line under the
value (a text, or `{text, tone}`), `url` makes the value a link.

**Action** (`row.actions`): `label`, `icon`, `url`, `visible` (an icon button named "Edit V-0412"; the others go in the
"More actions for V-0412" dropdown), `tone` (`danger`), `disabledReason` (the action stays focusable, the reason is read and
written under the label in the menu), `attr` (map of attributes: the `data-` attributes that open a confirmation or a
dialog), `items` (a group of actions in the menu).

**Group**: `key`, `label`, `count` (text), `rows` or `children` (groups of the second level), `subtotals` (column id → html).
**Total**: `column` (id; none for a `standalone` one), `label`, `html`, `standalone`, `id`. The component adds nothing up: the
caller gives the values. The first visible column of the totals row holds the label (`totalLabel`, a row header for
screen readers): a total aimed at that column is ignored, so aim it at another one.

Blocks: `before` (above the table), `empty` (the empty state: put the action that creates the first record), `footer`
(under the table: the pager). The cells are not a block: the caller renders them.
Controllers: `table` (second scrollbar above the table, sticky header, click on a row, folding of groups, selection;
dispatches `select` with `{ids}`) and `table-sort` (when `sortable`, loaded on demand).
Events: `2lenet--crudit-ui-bundle--table:select` (`event.detail.ids`); `2lenet--crudit-ui-bundle--table-sort:sorted` and `…:failed` (`event.detail.ids`).
Accessibility: `<caption>`, `scope`, `aria-sort`, a `<tbody>` per group with a `aria-expanded` button, boxes named after
the row, a handle that is a button (↑ ↓ move the row and announce the new position; "Move up" and "Move down" buttons in
the cards, for touch), `aria-busy` while loading, `role="alert"` on the error. The second scrollbar is `aria-hidden` and
out of the tab order.

The order is sent as CruditBundle saves it: a `POST` of the JSON list of the ids, in the new order, to `sortable.url`.
When the server refuses, the rows go back to their place and the error is announced. With groups, a row moves inside
its group (and its subgroup), never into another one; the order sent is the one of the whole table.

The heading of the table follows the page under the header of the application (the first `header` element, or the
selector of the value `header-selector` of the controller) with an animation linked to the scroll, without JavaScript
in the loop. Without that CSS function (Firefox) it scrolls with the table. The table scrolls horizontally when it is too
wide: the main column and the actions stay visible.

The cards repeat the cells of the table in the page, hidden by the CSS: the HTML of a cell is rendered twice, so **the
`id` of a row, a cell or a total (`domId`, `id`) goes on the table only**, and on the card as `data-row-id` and
`data-cell-id`. A script that refreshes a cell by its id (CruditBundle, `fieldsToUpdate`) updates `#<id>` and
`[data-cell-id="<id>"]`: the page has each id once, and the table and the card stay in step. The content of the
cell, in the HTML you give, must not carry an `id`.

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
which loads the stylesheet and the controllers of the bundle (see [Installation](installation.md#assets)). The base
needs the blocks `title` and `body`, and `<meta name="viewport" content="width=device-width, initial-scale=1">`:
without it, a phone lays the page out on 980 px and shrinks it. The kit is a `<div>`, not a `<main>`: the layout of the
project may have its own.

The styles of the page are in `ui.css` (`components/_kit.scss`, about 0.7 KB compressed), loaded in production
too: the page then works with Encore and AssetMapper alike, without a stylesheet of its own.

Each group of components has its section: `templates/kit/_<id>.html.twig`, listed in `sections` of
`templates/kit/index.html.twig` and wrapped in `<twig:LleCruditUi:Kit:Section>`. The test applications import the routes in
every environment, and their smoke test audits the page with axe-core (see [Contributing](development.md)).
