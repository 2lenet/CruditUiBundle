import { Controller } from '@hotwired/stimulus';

/*
 * Guard of the buttons of the bundle (Button, IconButton): a button with aria-disabled="true" (loading, or disabled
 * while keeping the focus) ignores the click. A click, Enter on the button and Enter in a field of its form (the
 * implicit submission clicks the submit button) all go through the click event: none sends the form twice. The
 * listener is in the capture phase, so it runs before those of the project on the button.
 */
export default class extends Controller {
    connect() {
        this.guard = (event) => {
            if (this.element.getAttribute('aria-disabled') === 'true') {
                event.preventDefault();
                event.stopImmediatePropagation();
            }
        };
        this.element.addEventListener('click', this.guard, {capture: true});
    }

    disconnect() {
        this.element.removeEventListener('click', this.guard, {capture: true});
    }
}
