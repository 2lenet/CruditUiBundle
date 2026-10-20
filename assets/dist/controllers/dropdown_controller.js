import { Controller } from '@hotwired/stimulus';
import { keepInViewport, placeFloating, resetPosition } from '../utils/position.js';

/*
 * Dropdown (component Dropdown): a button (target "button", action "toggle") opens a panel (target "panel").
 * Disclosure pattern, not an ARIA menu (decision of 19/10/2026): the panel holds links, buttons or fields, Tab goes
 * from one to the next, no arrow keys.
 *
 * - Escape closes it and gives the focus back to the button; the event stops there (a dialog around stays open).
 * - A click outside, or the focus leaving it, closes it.
 * - A click on an item of a menu (.crudit-ui-menu__item, not disabled) closes it and gives the focus back to the
 *   button: the action that follows (a dialog) gives it back there too when it closes.
 * - Shown under the button, shifted to stay in the screen. With the value "floating", in position: fixed, placed
 *   next to the button: it is not cut by a container that scrolls (a table); it closes when the page scrolls.
 * - The root takes the class crudit-ui-dropdown--open: a sticky container can rise above its neighbours with :has().
 *
 * Events: "open" and "close" (2lenet--crudit-ui-bundle--dropdown:open / :close), on the root.
 */
export default class extends Controller {
    static targets = ['button', 'panel'];

    static values = {floating: Boolean, align: {type: String, default: 'end'}};

    connect() {
        // A click in the dropdown moves the focus (to <main> when the target is not focusable): the focusout it
        // causes does not close it. Reset once the click is over, when the focus has landed.
        this.pointerInside = false;
        const pointerOver = () => setTimeout(() => {
            this.pointerInside = false;
        });

        this.onKeydown = (event) => {
            if (event.key === 'Escape' && this.isOpen()) {
                event.stopPropagation();
                this.close();
                this.buttonTarget.focus();
            }
        };
        this.onFocusOut = (event) => {
            const next = event.relatedTarget;
            if (this.isOpen() && next && !this.element.contains(next) && !this.pointerInside) {
                this.close();
            }
        };
        this.onPointerDown = () => {
            this.pointerInside = true;
            document.addEventListener('pointerup', pointerOver, {once: true});
            document.addEventListener('pointercancel', pointerOver, {once: true});
        };
        this.onPanelClick = (event) => {
            const item = event.target.closest('.crudit-ui-menu__item');
            if (item && this.panelTarget.contains(item) && item.getAttribute('aria-disabled') !== 'true') {
                this.close();
                this.buttonTarget.focus();
            }
        };
        this.onDocumentPointerDown = (event) => {
            if (!this.element.contains(event.target)) {
                this.close();
            }
        };
        this.onScroll = (event) => {
            if (!(event.target instanceof Node && this.panelTarget.contains(event.target))) {
                this.close();
            }
        };

        this.element.addEventListener('keydown', this.onKeydown);
        this.element.addEventListener('focusout', this.onFocusOut);
        this.element.addEventListener('pointerdown', this.onPointerDown);
        this.panelTarget.addEventListener('click', this.onPanelClick);
    }

    disconnect() {
        this.element.removeEventListener('keydown', this.onKeydown);
        this.element.removeEventListener('focusout', this.onFocusOut);
        this.element.removeEventListener('pointerdown', this.onPointerDown);
        this.panelTarget.removeEventListener('click', this.onPanelClick);
        this.removeDocumentListeners();
    }

    isOpen() {
        return !this.panelTarget.hidden;
    }

    toggle() {
        this.isOpen() ? this.close() : this.open();
    }

    open() {
        if (this.isOpen()) {
            return;
        }
        this.panelTarget.hidden = false;
        this.buttonTarget.setAttribute('aria-expanded', 'true');
        this.element.classList.add('crudit-ui-dropdown--open');

        if (this.floatingValue) {
            placeFloating(this.buttonTarget, this.panelTarget, {align: this.alignValue});
            window.addEventListener('scroll', this.onScroll, {capture: true, passive: true});
            window.addEventListener('resize', this.onScroll);
        } else {
            keepInViewport(this.panelTarget);
        }
        document.addEventListener('pointerdown', this.onDocumentPointerDown);
        this.dispatch('open');
    }

    /*
     * Closes the panel; the focus stays where it is (the controller gives it back to the button after Escape or the
     * choice of an item).
     */
    close() {
        if (!this.isOpen()) {
            return;
        }
        this.panelTarget.hidden = true;
        this.buttonTarget.setAttribute('aria-expanded', 'false');
        this.element.classList.remove('crudit-ui-dropdown--open');
        resetPosition(this.panelTarget);
        this.removeDocumentListeners();
        this.dispatch('close');
    }

    removeDocumentListeners() {
        document.removeEventListener('pointerdown', this.onDocumentPointerDown);
        window.removeEventListener('scroll', this.onScroll, {capture: true});
        window.removeEventListener('resize', this.onScroll);
    }
}
