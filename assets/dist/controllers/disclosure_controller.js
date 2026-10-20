import { Controller } from '@hotwired/stimulus';

/*
 * Disclosure: its button (target "toggle") shows or hides its panel (target "panel", class --closed). The actions
 * "toggle", "open" and "close" dispatch "toggle" ({open}) when the state changes. The state is not remembered. A
 * disclosure inside another one has its own controller: Stimulus gives each one its own targets and its own button.
 */
export default class extends Controller {
    static targets = ['toggle', 'panel'];

    toggle() {
        this.setOpen(this.toggleTarget.getAttribute('aria-expanded') !== 'true');
    }

    open() {
        this.setOpen(true);
    }

    close() {
        this.setOpen(false);
    }

    setOpen(open) {
        if ((this.toggleTarget.getAttribute('aria-expanded') === 'true') === open) {
            return;
        }
        this.panelTarget.classList.toggle('crudit-ui-disclosure__panel--closed', !open);
        this.toggleTarget.setAttribute('aria-expanded', String(open));
        this.dispatch('toggle', {detail: {open}});
    }
}
