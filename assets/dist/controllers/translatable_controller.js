import { Controller } from '@hotwired/stimulus';

/*
 * Translated field (templates/form/_translatable.html.twig), next to the controller tabs on the same element: while
 * typing, the tab of the language says if its input is empty (" · empty", part of the name of the tab). A tab in error
 * keeps " · error" until the form is sent again: it has no target. A required input in a hidden panel would stop the
 * form in the browser, with no message it can show (it cannot focus it): the inputs lose required (aria-required
 * instead), the server refuses an empty value.
 */
export default class extends Controller {
    static targets = ['empty'];

    connect() {
        this.element.querySelectorAll('[role="tabpanel"] [required]').forEach((input) => {
            input.required = false;
            input.setAttribute('aria-required', 'true');
        });
    }

    update(event) {
        const panel = event.target.closest('[role="tabpanel"]');
        if (!panel || !this.element.contains(panel)) {
            return;
        }

        const state = this.emptyTargets.find(target => target.dataset.language === panel.dataset.language);
        if (state) {
            state.hidden = String(event.target.value ?? '').trim() !== '';
        }
    }
}
