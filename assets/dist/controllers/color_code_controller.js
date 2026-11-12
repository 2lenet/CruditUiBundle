import { Controller } from '@hotwired/stimulus';

/*
 * Colour input of the form theme (block color_widget), on the native picker: the code shown next to it
 * ([data-color-code], in the same crudit-ui-input-group--color) follows the colour chosen, and the colour put back by
 * a reset of the form. A value changed by a script is shown after an input event.
 */
export default class extends Controller {
    connect() {
        this.code = this.element.parentElement?.querySelector('[data-color-code]');
        if (!this.code) {
            return;
        }
        this.onInput = () => {
            this.code.textContent = this.element.value;
        };
        this.element.addEventListener('input', this.onInput);
        // The browser puts the values back after the event reset
        this.form = this.element.form;
        this.onReset = () => setTimeout(this.onInput);
        this.form?.addEventListener('reset', this.onReset);
    }

    disconnect() {
        if (this.onInput) {
            this.element.removeEventListener('input', this.onInput);
        }
        if (this.onReset) {
            this.form?.removeEventListener('reset', this.onReset);
        }
    }
}
