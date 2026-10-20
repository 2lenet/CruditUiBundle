import { Controller } from '@hotwired/stimulus';

/*
 * Guard of the buttons of the bundle (Button, IconButton, Tag): a button with aria-disabled="true" (loading, or
 * disabled while keeping the focus) ignores the click. A click, Enter on the button and Enter in a field of its form
 * (the implicit submission clicks the submit button) all go through the click event: none sends the form twice. The
 * listener is in the capture phase, so it runs before those of the project on the button.
 *
 * busyOnSubmit (value, true on a submit button by default): once the button sends its form, it is put in loading
 * (aria-disabled, aria-busy, the spinner of its template "spinner" in place of its icon), so a second click is
 * ignored. It goes back to normal:
 * - when the submit is cancelled (preventDefault()), checked once the event has gone through all its listeners,
 *   those added later on the document included;
 * - when Turbo has sent the form (turbo:submit-end): the page does not change;
 * - when the page comes back from the cache of the browser (Back button).
 * Not for a form sent to another window or frame (target, formtarget): the page stays as it is; nor for a form
 * method="dialog", which sends nothing (it closes its dialog). A form whose answer
 * is a download (export) cannot be detected: such a button takes busyOnSubmit false (docs/components.md).
 *
 * Events: "busy" and "idle" (2lenet--crudit-ui-bundle--button:busy / :idle), when it goes in loading and back; a
 * dropdown puts its button in loading while one of its items sends a form.
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
                if (event.submitter !== this.element || !this.sendsHere()) {
                    return;
                }
                this.busy();
                // Once every listener has run, those added later on the document too.
                setTimeout(() => {
                    if (event.defaultPrevented && !this.turbo) {
                        this.restore();
                    }
                });
            };
            this.onTurboStart = (event) => {
                if (event.detail?.formSubmission?.submitter === this.element) {
                    this.turbo = true;
                    this.busy();
                }
            };
            this.onTurboEnd = (event) => {
                if (event.detail?.formSubmission?.submitter === this.element) {
                    this.turbo = false;
                    this.restore();
                }
            };
            this.onPageShow = (event) => {
                if (event.persisted) {
                    this.restore();
                }
            };
            document.addEventListener('submit', this.onSubmit);
            document.addEventListener('turbo:submit-start', this.onTurboStart);
            document.addEventListener('turbo:submit-end', this.onTurboEnd);
            window.addEventListener('pageshow', this.onPageShow);
        }
    }

    disconnect() {
        this.element.removeEventListener('click', this.guard, {capture: true});
        if (this.onSubmit) {
            document.removeEventListener('submit', this.onSubmit);
            document.removeEventListener('turbo:submit-start', this.onTurboStart);
            document.removeEventListener('turbo:submit-end', this.onTurboEnd);
            window.removeEventListener('pageshow', this.onPageShow);
        }
    }

    // The form is sent in this page, not in another window or frame. A form method="dialog" sends nothing: it closes
    // its dialog, if any.
    sendsHere() {
        const method = this.element.getAttribute('formmethod') ?? this.element.form?.getAttribute('method') ?? '';
        if (method.toLowerCase() === 'dialog') {
            return false;
        }
        const target = this.element.getAttribute('formtarget') ?? this.element.form?.getAttribute('target') ?? '';

        return target === '' || target === '_self';
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
        this.dispatch('busy');
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
        this.dispatch('idle');
    }
}
