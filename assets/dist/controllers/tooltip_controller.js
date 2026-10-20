import { Controller } from '@hotwired/stimulus';
import { placeFloating, resetPosition } from '../utils/position.js';

/*
 * Tooltip (component Tooltip). It is shown in CSS, on the hover and the focus of its host; the controller adds:
 * - Escape hides the tooltips shown (WCAG 1.4.13, RGAA 10.13), until the next move of the pointer or of the focus:
 *   the class crudit-ui-tooltips-dismissed goes on <html>. One listener of keydown for the whole page; those of the
 *   moves are added only once the tooltips are hidden.
 * - With the value "floating": the tooltip in position: fixed, placed next to its host while it is hovered or
 *   focused, and again when the page scrolls, so that a container that hides its overflow (the collapsed menu bar,
 *   a table) does not cut it. "placement": above, below or beside (inline end side); "align": start or end.
 */
const DISMISSED = 'crudit-ui-tooltips-dismissed';
let listening = false;

function restore() {
    document.documentElement.classList.remove(DISMISSED);
    document.removeEventListener('pointermove', restore);
    document.removeEventListener('focusin', restore);
}

function listen() {
    if (listening) {
        return;
    }
    listening = true;
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !document.documentElement.classList.contains(DISMISSED)) {
            document.documentElement.classList.add(DISMISSED);
            document.addEventListener('pointermove', restore, {passive: true});
            document.addEventListener('focusin', restore);
        }
    });
}

export default class extends Controller {
    static targets = ['tooltip'];

    static values = {floating: Boolean, placement: {type: String, default: 'above'}, align: {type: String, default: 'end'}};

    connect() {
        listen();
        if (!this.floatingValue) {
            return;
        }
        // Shown while hovered or focused: placed, then followed when the page scrolls (once per frame).
        this.reasons = new Set();
        this.place = () => placeFloating(this.element, this.tooltipTarget, {placement: this.placementValue, align: this.alignValue});
        this.onScroll = () => {
            cancelAnimationFrame(this.frame);
            this.frame = requestAnimationFrame(this.place);
        };
        this.show = (event) => {
            if (this.reasons.size === 0) {
                window.addEventListener('scroll', this.onScroll, {capture: true, passive: true});
            }
            this.reasons.add(event.type === 'focusin' ? 'focus' : 'pointer');
            this.place();
        };
        this.hide = (event) => {
            if (event.type === 'focusout' && this.element.contains(event.relatedTarget)) {
                return;
            }
            this.reasons.delete(event.type === 'focusout' ? 'focus' : 'pointer');
            if (this.reasons.size === 0) {
                window.removeEventListener('scroll', this.onScroll, {capture: true});
            }
        };
        this.element.addEventListener('pointerenter', this.show);
        this.element.addEventListener('focusin', this.show);
        this.element.addEventListener('pointerleave', this.hide);
        this.element.addEventListener('focusout', this.hide);
    }

    disconnect() {
        if (!this.place) {
            return;
        }
        this.element.removeEventListener('pointerenter', this.show);
        this.element.removeEventListener('focusin', this.show);
        this.element.removeEventListener('pointerleave', this.hide);
        this.element.removeEventListener('focusout', this.hide);
        window.removeEventListener('scroll', this.onScroll, {capture: true});
        cancelAnimationFrame(this.frame);
        resetPosition(this.tooltipTarget);
    }
}
