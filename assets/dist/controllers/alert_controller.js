import { Controller } from '@hotwired/stimulus';

/*
 * Alert that can be closed (Alert with dismissible): the "close" action dispatches "close", which a project can
 * cancel (preventDefault()), then removes the alert. The focus, which was on its close button, goes to the closest
 * focusable container, or to <main>: it does not fall back to the top of the page.
 */
export default class extends Controller {
    close() {
        if (this.dispatch('close', {cancelable: true}).defaultPrevented) {
            return;
        }

        const target = this.element.parentElement?.closest('[tabindex], main') ?? document.querySelector('main');
        this.element.remove();

        if (target) {
            if (!target.hasAttribute('tabindex')) {
                target.setAttribute('tabindex', '-1');
            }
            target.focus({preventScroll: true});
        }
    }
}
