import { Controller } from '@hotwired/stimulus';

/*
 * Pager (component Pager): sends the form of the rows per page as soon as the choice is made. The form is a GET that
 * sends the first page (the page parameter at 1, or 0 in the mode "offset"): the list goes back to the first page.
 * Without JavaScript the form shows a button "Apply" (<noscript>). Under Turbo, requestSubmit() is a visit like any
 * other form.
 *
 * With the keyboard, a closed <select> fires "change" at each arrow, and at each letter or digit typed (Chrome and
 * Firefox on Windows): going from 10 to 100 would reload the list at each step. A change made by a key waits for Enter
 * or for the focus to leave the field, and only if the choice differs from the one the list shows. A choice made with
 * the mouse or the touch screen, or in the open list, is sent at once.
 *
 * How a change is known to come from a key: "change" is fired while the key is handled, so the flag set by the keydown is
 * cleared right after it (a timer), not by the keyup, which a native list (macOS: the arrow opens it) may take.
 */
export default class extends Controller {
    static keysOfTheList = ['Enter', 'Tab', 'Escape', 'Shift', 'Control', 'Alt', 'Meta'];

    pending = false;
    fromKey = false;
    initial = null;

    connect() {
        this.initial = this.select()?.value ?? null;
    }

    submit() {
        if (this.fromKey) {
            this.pending = true;

            return;
        }
        this.send();
    }

    // keydown on the select: Enter sends what was chosen with the keys; any other key (arrow, letter, digit, Home…)
    // makes the change that follows one "by key". Tab and Escape do not change the choice. Alt+arrow opens the list.
    key(event) {
        if (event.key === 'Enter' && this.pending) {
            event.preventDefault();
            this.sendIfChanged();

            return;
        }
        if (this.constructor.keysOfTheList.includes(event.key) || event.altKey || event.ctrlKey || event.metaKey) {
            return;
        }
        this.fromKey = true;
        setTimeout(() => { this.fromKey = false; }, 0);
    }

    // blur: the choice made with the keys is sent, unless the user goes to a link of the pager (the click leads there
    // and the form would start a second visit)
    leave(event) {
        if (this.pending && !(event.relatedTarget instanceof HTMLAnchorElement)) {
            this.sendIfChanged();
        }
    }

    sendIfChanged() {
        if (this.select()?.value === this.initial) {
            this.pending = false;

            return;
        }
        this.send();
    }

    send() {
        this.pending = false;
        const form = this.element.querySelector('form');
        if (form) {
            form.requestSubmit();
        }
    }

    select() {
        return this.element.querySelector('select');
    }
}
