import { Controller } from '@hotwired/stimulus';

/*
 * Pager (component Pager): sends the form of the rows per page as soon as the choice is made. The form is a GET that
 * sends the first page (the page parameter at 1, or 0 in the mode "offset"): the list goes back to the first page.
 * Without JavaScript the form shows a button "Apply" (<noscript>). Under Turbo, requestSubmit() is a visit like any
 * other form.
 *
 * With the keyboard, the arrows of a closed <select> fire "change" at each press (Chrome and Firefox on Windows): going
 * from 10 to 100 would reload the list at each step. A change made with an arrow key waits for Enter or for the focus to
 * leave the field; a choice made with the mouse or the touch screen, or in the open list, is sent at once.
 */
export default class extends Controller {
    pending = false;
    arrow = false;

    submit(event) {
        if (this.arrow) {
            this.pending = true;

            return;
        }
        this.send();
    }

    // keydown on the select: remember that the next "change" comes from an arrow; Enter sends what was chosen
    key(event) {
        if (event.key === 'Enter' && this.pending) {
            event.preventDefault();
            this.send();

            return;
        }
        this.arrow = ['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'Home', 'End', 'PageUp', 'PageDown'].includes(event.key) && !event.altKey;
    }

    // keyup, and the end of the key press: the next change is not from the keyboard unless a key comes first
    release() {
        // "change" is fired during keydown: the flag is only needed until then
        this.arrow = false;
    }

    leave() {
        if (this.pending) {
            this.send();
        }
    }

    send() {
        this.pending = false;
        const form = this.element.querySelector('form');
        if (form) {
            form.requestSubmit();
        }
    }
}
