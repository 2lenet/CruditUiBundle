import { Controller } from '@hotwired/stimulus';

/*
 * Translated field (templates/form/_translatable.html.twig), next to the controller tabs on the same element: while
 * typing, the tab of the language says if its input is empty (" · empty", part of the name of the tab). A tab in error
 * keeps " · error" until the form is sent again: it has no target.
 */
export default class extends Controller {
    static targets = ['empty'];

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
