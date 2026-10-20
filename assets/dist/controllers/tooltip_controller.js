import { Controller } from '@hotwired/stimulus';
import { placeFloating, resetPosition } from '../utils/position.js';

/*
 * Tooltip (component Tooltip). It is shown in CSS, on the hover and the focus of its host; the controller adds:
 * - Escape hides the tooltips shown (WCAG 1.4.13, RGAA 10.13), until the next move of the pointer or of the focus:
 *   the class crudit-ui-tooltips-dismissed goes on <html>. One listener for the whole page.
 * - With the value "floating": the tooltip in position: fixed, placed next to its host when it is shown, so that a
 *   container that hides its overflow (the collapsed menu bar, a table) does not cut it. "placement": above, below
 *   or beside (inline end side); "align": start or end.
 */
const DISMISSED = 'crudit-ui-tooltips-dismissed';
let listening = false;

function listen() {
    if (listening) {
        return;
    }
    listening = true;
    const restore = () => document.documentElement.classList.remove(DISMISSED);
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            document.documentElement.classList.add(DISMISSED);
        }
    });
    document.addEventListener('pointermove', restore, {passive: true});
    document.addEventListener('focusin', restore);
}

export default class extends Controller {
    static targets = ['tooltip'];

    static values = {floating: Boolean, placement: {type: String, default: 'above'}, align: {type: String, default: 'end'}};

    connect() {
        listen();
        if (this.floatingValue) {
            this.place = () => placeFloating(this.element, this.tooltipTarget, {placement: this.placementValue, align: this.alignValue});
            this.element.addEventListener('pointerenter', this.place);
            this.element.addEventListener('focusin', this.place);
        }
    }

    disconnect() {
        if (this.place) {
            this.element.removeEventListener('pointerenter', this.place);
            this.element.removeEventListener('focusin', this.place);
            resetPosition(this.tooltipTarget);
        }
    }
}
