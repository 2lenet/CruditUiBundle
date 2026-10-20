import { Controller } from '@hotwired/stimulus';
import { keepInViewport, placeFloating, resetPosition } from '../utils/position.js';

const BUTTON = '2lenet--crudit-ui-bundle--button';

/*
 * Dropdown (component Dropdown): a button (target "button", action "toggle") opens a panel (target "panel").
 * Disclosure pattern, not an ARIA menu (decision of 19/10/2026): the panel holds links, buttons or fields, Tab goes
 * from one to the next, no arrow keys.
 *
 * - Escape closes it and gives the focus back to the button; the event stops there, and its default is prevented: a
 *   dialog around (a native <dialog> too, closed by the browser otherwise) stays open.
 * - A click outside, or the focus leaving it, closes it.
 * - A click on an item of a menu (.crudit-ui-menu__item, not disabled) closes it and gives the focus back to the
 *   button, before the action of the item runs (capture): a dialog that the action opens takes the focus, and gives
 *   it back to the button when it closes.
 * - An item that sends a form (busyOnSubmit) puts the button of the dropdown in loading, as long as it is itself.
 *   The items of a dropdown inside the panel concern that one only (choice, loading).
 * - Shown under the button, shifted to stay in the screen. With the value "floating", in position: fixed, placed
 *   next to the button: it is not cut by a container that scrolls (a table). It closes when the page scrolls, and
 *   follows the button when the window is resized, or when the page scrolls while the focus is in it (the virtual
 *   keyboard of a phone, opened by a field of the panel); then it closes if the button leaves the screen, unless a
 *   field of the panel has the focus.
 * - Closed when the controller disconnects and before Turbo caches the page: a snapshot is never restored open.
 * - The root takes the class crudit-ui-dropdown--open: a sticky container can rise above its neighbours with :has().
 *
 * Events: "open" and "close" (2lenet--crudit-ui-bundle--dropdown:open / :close), on the root.
 */
export default class extends Controller {
    static targets = ['button', 'panel'];

    static values = {floating: Boolean, align: {type: String, default: 'end'}};

    connect() {
        // A click in the dropdown moves the focus (to <main> when the target is not focusable): the focusout it
        // causes does not close it. Reset once the click is over, when the focus has landed, and when the panel
        // opens or closes (a pointerup that never came, released outside the window).
        this.pointerInside = false;
        const pointerOver = () => setTimeout(() => {
            this.pointerInside = false;
        });

        this.onKeydown = (event) => {
            if (event.key === 'Escape' && this.isOpen()) {
                event.preventDefault();
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
            if (item && this.owns(item) && item.getAttribute('aria-disabled') !== 'true') {
                this.close();
                this.buttonTarget.focus();
            }
        };
        this.onItemBusy = (event) => this.owns(event.target) && this.trigger()?.busy();
        this.onItemIdle = (event) => this.owns(event.target) && this.trigger()?.restore();
        this.onDocumentPointerDown = (event) => {
            if (!this.element.contains(event.target)) {
                this.close();
            }
        };
        this.onScroll = (event) => {
            if (event.target instanceof Node && this.panelTarget.contains(event.target)) {
                return;
            }
            this.panelTarget.contains(document.activeElement) ? this.follow() : this.close();
        };
        // The button scrolled out of the screen: the panel would stay on the edge, away from it. Kept while a field
        // of the panel has the focus (the virtual keyboard).
        this.onFollowed = () => {
            const rect = this.buttonTarget.getBoundingClientRect();
            const visible = rect.bottom > 0 && rect.top < window.innerHeight && rect.right > 0 && rect.left < window.innerWidth;
            if (!visible && !document.activeElement?.matches('input, select, textarea, [contenteditable]')) {
                this.close();
                this.buttonTarget.focus({preventScroll: true});
            }
        };
        this.onResize = () => this.follow();
        this.onBeforeCache = () => this.close();

        this.element.addEventListener('keydown', this.onKeydown);
        this.element.addEventListener('focusout', this.onFocusOut);
        this.element.addEventListener('pointerdown', this.onPointerDown);
        this.panelTarget.addEventListener('click', this.onPanelClick, {capture: true});
        this.panelTarget.addEventListener(`${BUTTON}:busy`, this.onItemBusy);
        this.panelTarget.addEventListener(`${BUTTON}:idle`, this.onItemIdle);
        document.addEventListener('turbo:before-cache', this.onBeforeCache);
    }

    disconnect() {
        this.close();
        this.element.removeEventListener('keydown', this.onKeydown);
        this.element.removeEventListener('focusout', this.onFocusOut);
        this.element.removeEventListener('pointerdown', this.onPointerDown);
        this.panelTarget.removeEventListener('click', this.onPanelClick, {capture: true});
        this.panelTarget.removeEventListener(`${BUTTON}:busy`, this.onItemBusy);
        this.panelTarget.removeEventListener(`${BUTTON}:idle`, this.onItemIdle);
        document.removeEventListener('turbo:before-cache', this.onBeforeCache);
        cancelAnimationFrame(this.frame);
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
        this.pointerInside = false;
        this.panelTarget.hidden = false;
        this.buttonTarget.setAttribute('aria-expanded', 'true');
        this.element.classList.add('crudit-ui-dropdown--open');

        if (this.floatingValue) {
            this.place();
            window.addEventListener('scroll', this.onScroll, {capture: true, passive: true});
            window.addEventListener('resize', this.onResize);
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
        this.pointerInside = false;
        this.panelTarget.hidden = true;
        this.buttonTarget.setAttribute('aria-expanded', 'false');
        this.element.classList.remove('crudit-ui-dropdown--open');
        resetPosition(this.panelTarget);
        document.removeEventListener('pointerdown', this.onDocumentPointerDown);
        window.removeEventListener('scroll', this.onScroll, {capture: true});
        window.removeEventListener('resize', this.onResize);
        this.dispatch('close');
    }

    place() {
        placeFloating(this.buttonTarget, this.panelTarget, {align: this.alignValue});
    }

    // Places the floating panel again, once per frame; closes it if its button left the screen.
    follow() {
        cancelAnimationFrame(this.frame);
        this.frame = requestAnimationFrame(() => {
            if (this.isOpen()) {
                this.place();
                this.onFollowed();
            }
        });
    }

    // An element of this dropdown, not of a dropdown in its panel. Found by the controller, not by the class
    // crudit-ui-dropdown (styles only), which the markup of a project may not have.
    owns(element) {
        return this.panelTarget.contains(element) && element.closest(`[data-controller~="${this.identifier}"]`) === this.element;
    }

    // The controller "button" of the button of the dropdown (its loading state).
    trigger() {
        return this.application.getControllerForElementAndIdentifier(this.buttonTarget, BUTTON);
    }
}
