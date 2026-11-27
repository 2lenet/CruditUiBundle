import { Controller } from '@hotwired/stimulus';

/*
 * Collection of the form theme (block collection_widget): add a line from the prototype, remove a line, move a line up
 * or down. Same as `CollectionField` of the crudit-v2 mockup.
 *
 * - Each line is named from the value entry ("Line %index%"): its aria-label, its number, the name of the line after
 *   the label of each of its fields and the names of its buttons are written again each time the lines change.
 * - With the value sortable, the hidden field of the position of each line (data-crudit-ui-collection-position) gets
 *   1, 2, 3… each time the lines change (not on load: the positions of the server are kept until the user changes the
 *   order). The first line cannot go up, the last one cannot go down.
 * - The focus: the first field of a line added; after a removal, the first field of the line that takes its place, else
 *   of the line before, else the button Add (else the collection); after a move, the same button, or the other one when
 *   it is disabled.
 * - The change is told in a live region (status).
 * - A line added takes the next index (value index): the names of the fields stay unique, Symfony reads the lines in
 *   the order of the page.
 * - A reset of the form gives back the values of the fields, not the lines: the lines added, removed or moved stay (the
 *   hidden positions are not put back either: writing .value of a hidden input changes its attribute value; they
 *   follow the lines of the page).
 * - A disabled form (value disabled) never gets its buttons Move up and Move down enabled again.
 * The texts come from the theme, translated by the server (%item%, %position%, %total%).
 */
export default class extends Controller {
    static targets = ['rows', 'row', 'prototype', 'empty', 'add', 'status'];

    static values = {
        entry: String,
        index: Number,
        prototypeName: { type: String, default: '__name__' },
        sortable: Boolean,
        disabled: Boolean,
        moveUpText: String,
        moveDownText: String,
        removeText: String,
        addedText: String,
        removedText: String,
        movedText: String,
    };

    // The lines of this collection, in the order of the page (not those of a collection inside a line)
    rows() {
        return [...this.rowsTarget.children].filter((row) => this.rowTargets.includes(row));
    }

    add() {
        if (!this.hasPrototypeTarget) {
            return;
        }
        const html = this.prototypeTarget.innerHTML.replaceAll(this.prototypeNameValue, String(this.indexValue));
        this.indexValue += 1;
        this.rowsTarget.insertAdjacentHTML('beforeend', html);
        const row = this.rowsTarget.lastElementChild;
        this.renumber();
        this.announce(this.addedTextValue, { item: this.nameOf(row) });
        this.firstField(row)?.focus();
    }

    remove(event) {
        const row = this.rowOf(event);
        if (!row) {
            return;
        }
        const rows = this.rows();
        const index = rows.indexOf(row);
        const name = this.nameOf(row);
        row.remove();
        this.renumber();
        this.announce(this.removedTextValue, { item: name });
        const next = this.rows()[Math.min(index, rows.length - 2)];
        const target = (next && this.firstField(next)) ?? (this.hasAddTarget && !this.addTarget.disabled ? this.addTarget : null);
        if (target) {
            target.focus();
        } else {
            this.element.tabIndex = -1;
            this.element.focus();
        }
    }

    moveUp(event) {
        this.move(event, -1);
    }

    moveDown(event) {
        this.move(event, 1);
    }

    move(event, delta) {
        const row = this.rowOf(event);
        const rows = this.rows();
        const index = rows.indexOf(row);
        const to = index + delta;
        if (!row || to < 0 || to >= rows.length) {
            return;
        }
        if (delta < 0) {
            rows[to].before(row);
        } else {
            rows[to].after(row);
        }
        this.renumber();
        this.announce(this.movedTextValue, { position: to + 1, total: rows.length });
        const up = row.querySelector(':scope > .crudit-ui-collection__actions [data-crudit-ui-collection-up]');
        const down = row.querySelector(':scope > .crudit-ui-collection__actions [data-crudit-ui-collection-down]');
        const same = delta < 0 ? up : down;
        (same && !same.disabled ? same : (delta < 0 ? down : up))?.focus();
    }

    // Names, numbers, buttons and positions of the lines, after a change
    renumber() {
        const rows = this.rows();
        rows.forEach((row, index) => {
            const name = this.entryValue.replace('%index%', String(index + 1));
            row.setAttribute('aria-label', name);
            const number = row.querySelector(':scope > [data-crudit-ui-collection-index]');
            if (number) {
                number.textContent = String(index + 1);
            }
            for (const element of this.own(row, '[data-crudit-ui-collection-entry-name]')) {
                element.textContent = `, ${name}`;
            }
            for (const element of this.own(row, '[data-crudit-ui-collection-entry-label]')) {
                element.textContent = name;
            }
            const texts = { moveUp: this.moveUpTextValue, moveDown: this.moveDownTextValue, remove: this.removeTextValue };
            for (const element of row.querySelectorAll(':scope > .crudit-ui-collection__actions [data-crudit-ui-collection-text]')) {
                element.textContent = (texts[element.dataset.cruditUiCollectionText] ?? '').replace('%item%', name);
            }
            const up = row.querySelector(':scope > .crudit-ui-collection__actions [data-crudit-ui-collection-up]');
            const down = row.querySelector(':scope > .crudit-ui-collection__actions [data-crudit-ui-collection-down]');
            // A disabled collection (value disabled, from the form) keeps its buttons disabled
            if (up) {
                up.disabled = this.disabledValue || index === 0;
            }
            if (down) {
                down.disabled = this.disabledValue || index === rows.length - 1;
            }
            if (this.sortableValue) {
                for (const input of this.own(row, '[data-crudit-ui-collection-position]')) {
                    input.value = String(index + 1);
                }
            }
        });
        if (this.hasEmptyTarget) {
            this.emptyTarget.hidden = rows.length > 0;
        }
    }

    // The elements of a line, not those of a collection inside it
    own(row, selector) {
        return [...row.querySelectorAll(selector)].filter((element) => element.closest('.crudit-ui-collection__row') === row);
    }

    rowOf(event) {
        const row = event.currentTarget.closest('.crudit-ui-collection__row');

        return row && this.rowTargets.includes(row) ? row : null;
    }

    nameOf(row) {
        return row.getAttribute('aria-label') ?? '';
    }

    firstField(row) {
        return this.own(row, 'input:not([type="hidden"]), select, textarea, [role="combobox"]').find((field) => !field.disabled && !field.hidden) ?? null;
    }

    announce(text, params) {
        const message = Object.entries(params).reduce((result, [key, value]) => result.replace(`%${key}%`, String(value)), text);
        // Emptied first: the same message twice (two lines removed) is told again
        this.statusTarget.textContent = '';
        requestAnimationFrame(() => {
            this.statusTarget.textContent = message;
        });
    }
}
