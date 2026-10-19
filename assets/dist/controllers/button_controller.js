import { Controller } from '@hotwired/stimulus';

/*
 * Guard of the buttons of the bundle (Button, IconButton, Tag): a button with aria-disabled="true" (loading, or
 * disabled while keeping the focus) ignores the click. A click, Enter on the button and Enter in a field of its form
 * (the implicit submission clicks the submit button) all go through the click event: none sends the form twice. The
 * listener is in the capture phase, so it runs before those of the project on the button.
 *
 * busyOnSubmit (value, true on a submit button by default): once the button sends its form, it is put in loading
 * (aria-disabled, aria-busy, the spinner of its template "spinner" in place of its icon), so a second click is
 * ignored. The submit is watched on the document, after the listeners of the form: a submit cancelled by a check of
 * the project (preventDefault()) leaves the button as it is. A page restored from the cache of the browser (back
 * button) gets its button back.
 */
export default class extends Controller {
    static targets = ['spinner'];

    static values = {busyOnSubmit: Boolean};

    connect() {
        this.guard = (event) => {
            if (this.element.getAttribute('aria-disabled') === 'true') {
                event.preventDefault();
                event.stopImmediatePropagation();
            }
        };
        this.element.addEventListener('click', this.guard, {capture: true});

        if (this.busyOnSubmitValue) {
            this.onSubmit = (event) => {
                if (event.submitter === this.element && !event.defaultPrevented) {
                    this.busy();
                }
            };
            this.onPageShow = (event) => {
                if (event.persisted) {
                    this.restore();
                }
            };
            document.addEventListener('submit', this.onSubmit);
            window.addEventListener('pageshow', this.onPageShow);
        }
    }

    disconnect() {
        this.element.removeEventListener('click', this.guard, {capture: true});
        if (this.onSubmit) {
            document.removeEventListener('submit', this.onSubmit);
            window.removeEventListener('pageshow', this.onPageShow);
        }
    }

    busy() {
        if (this.saved) {
            return;
        }
        const icon = this.element.querySelector(':scope > svg');
        this.saved = {icon};
        if (this.hasSpinnerTarget) {
            const spinner = this.spinnerTarget.content.firstElementChild.cloneNode(true);
            icon ? icon.replaceWith(spinner) : this.spinnerTarget.after(spinner);
            this.saved.spinner = spinner;
        }
        this.element.setAttribute('aria-disabled', 'true');
        this.element.setAttribute('aria-busy', 'true');
    }

    restore() {
        if (!this.saved) {
            return;
        }
        const {icon, spinner} = this.saved;
        if (spinner) {
            icon ? spinner.replaceWith(icon) : spinner.remove();
        }
        this.element.removeAttribute('aria-disabled');
        this.element.removeAttribute('aria-busy');
        this.saved = null;
    }
}
