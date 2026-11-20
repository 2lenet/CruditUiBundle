import { Controller } from '@hotwired/stimulus';

/*
 * Filter:Bar with `saved`: the sets of filters the user saves (lot 3.1.f of Crudit).
 *
 * - "Save these filters" (action "toggleSave", target "toggle") shows the field "Name of the filters" (target "region")
 *   under the filters of the panel, with the focus in it; Escape or "Cancel" fold it without closing the panel.
 * - The filters are saved by a POST of the form of the filters (a GET): the button "Save" (target "submit") has
 *   `formaction` and `formmethod="post"`, so the form sends the fields of the panel with it. The name and the two hidden
 *   fields (the token and the address to come back to, target "field") are kept out of the form the rest of the time, or a
 *   GET of the filters would carry them in the address: the name has no `name` attribute (a field that is disabled
 *   cannot be typed in) and the others are disabled. "arm" (click on "Save") gives them back, and they are taken away
 *   again at the end of the click, once the form has read its data (the submit comes before).
 * - `required` is on the name only for the click on "Save" (see arm()): the browser then asks for the name, in a field that is
 *   visible. Left on, it would stop every send of the filters (button "Apply", Enter, chips) while the name is empty.
 * - A name already taken (names, without regard to the case) turns "Save" into "Replace" and says so; with the maximum
 *   reached, a new name cannot be saved (the server refuses too).
 * - Enter in the name saves: the form of the filters would send them (a GET) instead.
 * - The bin of a set (action "askDelete") unfolds its confirmation in place (target "confirm"), with the focus on
 *   "Cancel"; Escape or "Cancel" fold it and the focus goes back to the bin. A dialog would close the dropdown.
 *
 * Values: names (the names of the sets), max, texts ({help, replaceHelp, save, replace, maxReached}).
 */
export default class extends Controller {
    static targets = ['toggle', 'region', 'name', 'help', 'submit', 'field', 'confirm', 'cancel', 'bin'];

    static values = {names: Array, max: Number, texts: Object};

    // --- Save ------------------------------------------------------------------------------------------------

    toggleSave() {
        if (this.regionTarget.hidden) {
            this.regionTarget.hidden = false;
            this.toggleTarget.setAttribute('aria-expanded', 'true');
            this.nameTarget.focus();
            this.nameChanged();
        } else {
            this.cancelSave();
        }
    }

    cancelSave(event) {
        // Escape in the name would otherwise close the panel (a dialog) too.
        event?.stopPropagation();
        event?.preventDefault();
        if (this.regionTarget.hidden) {
            return;
        }
        this.regionTarget.hidden = true;
        this.toggleTarget.setAttribute('aria-expanded', 'false');
        this.nameTarget.value = '';
        this.toggleTarget.focus();
    }

    nameChanged() {
        const name = this.cleanName(this.nameTarget.value);
        const taken = name !== '' && this.namesValue.some((existing) => existing.toLowerCase() === name.toLowerCase());
        const full = !taken && this.namesValue.length >= this.maxValue;
        const texts = this.textsValue;
        this.helpTarget.textContent = full ? texts.maxReached : taken ? texts.replaceHelp : texts.help;
        this.setLabel(this.submitTarget, taken ? texts.replace : texts.save);
        // aria-disabled, not disabled: the focus stays and the reason is read; the click is refused (see arm()).
        if (full) {
            this.submitTarget.setAttribute('aria-disabled', 'true');
        } else {
            this.submitTarget.removeAttribute('aria-disabled');
        }
    }

    enter(event) {
        // Not the Enter of the form of the filters (which sends them, a GET), nor of the dialog.
        event.preventDefault();
        event.stopPropagation();
        this.submitTarget.click();
    }

    arm(event) {
        if (this.submitTarget.hasAttribute('aria-disabled')) {
            event.preventDefault();
            return;
        }
        // The name is checked now, by the browser, and sent as it was compared: the spaces cleaned. `required` is only on for
        // this click: a `required` field, even without `name`, would stop every other send of the form while it is empty.
        this.nameTarget.value = this.cleanName(this.nameTarget.value);
        this.nameTarget.name = 'name';
        this.nameTarget.required = true;
        this.fieldTargets.forEach((field) => {
            field.disabled = false;
        });
        // The click is followed by the validation and the send of the form, in the same task: a timer is after them.
        setTimeout(() => {
            this.nameTarget.removeAttribute('name');
            this.nameTarget.required = false;
            this.fieldTargets.forEach((field) => {
                field.disabled = true;
            });
        }, 0);
    }

    cleanName(value) {
        return value.replace(/\s+/g, ' ').trim();
    }

    // The text of a Button is its last text node (an icon comes before): only that one changes.
    setLabel(button, text) {
        const nodes = Array.from(button.childNodes).filter((node) => node.nodeType === Node.TEXT_NODE && node.textContent.trim() !== '');
        const node = nodes.at(-1);
        if (node) {
            node.textContent = text;
        } else {
            button.append(document.createTextNode(text));
        }
    }

    // --- Delete ----------------------------------------------------------------------------------------------

    askDelete(event) {
        this.closeConfirms();
        const confirm = event.currentTarget.closest('.crudit-ui-filter-saved__item')?.querySelector('[data-2lenet--crudit-ui-bundle--saved-filters-target="confirm"]');
        if (!confirm) {
            return;
        }
        confirm.hidden = false;
        confirm.querySelector('[data-2lenet--crudit-ui-bundle--saved-filters-target="cancel"]')?.focus();
    }

    cancelDelete(event) {
        // Escape would otherwise close the dropdown too.
        event.stopPropagation();
        event.preventDefault();
        const item = event.currentTarget.closest('.crudit-ui-filter-saved__item');
        const confirm = item?.querySelector('[data-2lenet--crudit-ui-bundle--saved-filters-target="confirm"]');
        if (confirm) {
            confirm.hidden = true;
        }
        this.binTargets.find((bin) => item?.contains(bin))?.focus();
    }

    closeConfirms() {
        this.confirmTargets.forEach((confirm) => {
            confirm.hidden = true;
        });
    }
}
