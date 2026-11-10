import { Controller } from '@hotwired/stimulus';

/*
 * Colour input of the form theme (block color_widget), on the native picker: the code shown next to it
 * ([data-color-code], in the same crudit-ui-input-group--color) follows the colour chosen.
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
    }

    disconnect() {
        if (this.onInput) {
            this.element.removeEventListener('input', this.onInput);
        }
    }
}
