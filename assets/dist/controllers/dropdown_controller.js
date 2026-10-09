import { Controller } from '@hotwired/stimulus';

/*
 * Dropdown menu: a button (target "button") opens a panel (target "panel").
 * Escape and a click outside close it, Escape gives the focus back to the button.
 */
export default class extends Controller {
    static targets = ['button', 'panel'];

    connect() {
        this.onDocumentClick = (event) => {
            if (!this.element.contains(event.target)) {
                this.close();
            }
        };
        document.addEventListener('click', this.onDocumentClick);
    }

    disconnect() {
        document.removeEventListener('click', this.onDocumentClick);
    }

    toggle() {
        this.panelTarget.hidden ? this.open() : this.close();
    }

    open() {
        this.panelTarget.hidden = false;
        this.buttonTarget.setAttribute('aria-expanded', 'true');
    }

    close() {
        this.panelTarget.hidden = true;
        this.buttonTarget.setAttribute('aria-expanded', 'false');
    }

    keydown(event) {
        if (event.key === 'Escape' && !this.panelTarget.hidden) {
            this.close();
            this.buttonTarget.focus();
        }
    }
}
