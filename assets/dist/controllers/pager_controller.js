import { Controller } from '@hotwired/stimulus';

/*
 * Pager (component Pager): sends the form of the rows per page as soon as the choice changes. The form is a GET that
 * does not carry the page, so the list goes back to the first page. Without JavaScript the form shows a button
 * "Apply" (<noscript>). Under Turbo, requestSubmit() is a visit like any other form.
 */
export default class extends Controller {
    submit() {
        const form = this.element.querySelector('form');
        if (form) {
            form.requestSubmit();
        }
    }
}
