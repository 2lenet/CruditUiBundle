import { Controller } from '@hotwired/stimulus';

/*
 * Alert that can be closed (Alert with dismissible): the "close" action dispatches "close", which a project can
 * cancel (preventDefault()), then removes the alert. If the focus was in the alert (on its close button), it goes to
 * the closest focusable container, or to <main> (given tabindex="-1", which stays): it does not fall back to the top
 * of the page.
 */
export default class extends Controller {
    close() {
        if (this.dispatch('close', {cancelable: true}).defaultPrevented) {
            return;
        }

        // The focus moves only if it was in the alert (its close button): a close called by another controller
        // while the user types in a field leaves it there.
        const hadFocus = this.element.contains(document.activeElement);
        const target = this.element.parentElement?.closest('[tabindex], main') ?? document.querySelector('main');
        this.element.remove();

        if (hadFocus && target) {
            if (!target.hasAttribute('tabindex')) {
                target.setAttribute('tabindex', '-1');
            }
            target.focus({preventScroll: true});
        }
    }
}
