import { Controller } from '@hotwired/stimulus';

/*
 * Prototype of a heavy controller (editor, Sortable...): declared with fetch "lazy",
 * its code is only downloaded when an element using it appears in the page.
 */
export default class extends Controller {
    connect() {
        this.element.textContent = 'lazy controller loaded';
        this.element.dataset.loaded = 'true';
    }
}
