import { Controller } from '@hotwired/stimulus';

/*
 * Toggletip (component Toggletip), on its button. The panel is a native popover opened by popovertarget: a click (or a
 * touch) opens and closes it, Escape and a click outside close it, with or without this controller. The controller
 * adds the hover and the keyboard focus:
 * - the pointer of a mouse or a pen on the button opens the panel, "for a look"; it closes once the pointer has left
 *   both the button and the panel (the panel can be hovered: WCAG 1.4.13);
 * - the keyboard focus (focus-visible) opens it the same way; it closes once the focus has left both the button and
 *   the panel (a link in the panel can be reached with Tab);
 * - a click on a panel opened for a look keeps it open (instead of the native toggle that would close it): it then
 *   closes like a panel opened by a click.
 * A touch fires no hover: it is the native click.
 *
 * The panel is a popover "hint" where the browser supports it (otherwise "auto"): opened by the hover, it does not
 * close the other popovers open (a menu), as an "auto" one would.
 */
const OPEN_DELAY = 100;
const CLOSE_DELAY = 200;

export default class extends Controller {
    connect() {
        this.panel = document.getElementById(this.element.getAttribute('popovertarget') ?? '');
        if (!this.panel || typeof this.panel.showPopover !== 'function') {
            return;
        }
        if (supportsHint()) {
            this.panel.popover = 'hint';
        }
        this.look = false;
        // Closed by Escape from the panel, the browser gives the focus back to the button: this focus must not open
        // it again. beforetoggle is fired before the focus comes back, toggle (asynchronous) after.
        this.closing = false;
        this.onEnter = (event) => {
            if (event.pointerType === 'touch') {
                return;
            }
            this.cancel();
            if (!this.isOpen()) {
                this.timer = setTimeout(() => this.open(), event.currentTarget === this.panel ? 0 : OPEN_DELAY);
            }
        };
        this.onLeave = (event) => {
            if (event.pointerType === 'touch') {
                return;
            }
            this.cancel();
            this.timer = setTimeout(() => this.closeLook(), CLOSE_DELAY);
        };
        this.onFocus = () => {
            if (!this.closing && this.element.matches(':focus-visible') && !this.isOpen()) {
                this.open();
            }
        };
        this.onBlur = (event) => {
            if (!this.panel.contains(event.relatedTarget)) {
                this.closeLook();
            }
        };
        // The focus leaves the panel: back to its button, it stays open.
        this.onPanelFocusOut = (event) => {
            if (event.relatedTarget !== this.element && !this.panel.contains(event.relatedTarget)) {
                this.closeLook();
            }
        };
        this.onClick = (event) => {
            this.cancel();
            if (this.look && this.isOpen()) {
                // Opened for a look: the click keeps it open, the native toggle would close it.
                event.preventDefault();
            }
            this.look = false;
        };
        this.onBeforeToggle = (event) => {
            if (event.newState === 'closed') {
                this.closing = true;
                setTimeout(() => {
                    this.closing = false;
                }, 0);
            }
        };
        this.onToggle = (event) => {
            if (event.newState === 'closed') {
                this.cancel();
                this.look = false;
            }
        };

        this.element.addEventListener('pointerenter', this.onEnter);
        this.element.addEventListener('pointerleave', this.onLeave);
        this.element.addEventListener('focus', this.onFocus);
        this.element.addEventListener('blur', this.onBlur);
        this.element.addEventListener('click', this.onClick);
        this.panel.addEventListener('pointerenter', this.onEnter);
        this.panel.addEventListener('pointerleave', this.onLeave);
        this.panel.addEventListener('focusout', this.onPanelFocusOut);
        this.panel.addEventListener('beforetoggle', this.onBeforeToggle);
        this.panel.addEventListener('toggle', this.onToggle);
    }

    disconnect() {
        if (!this.onEnter) {
            return;
        }
        this.cancel();
        this.element.removeEventListener('pointerenter', this.onEnter);
        this.element.removeEventListener('pointerleave', this.onLeave);
        this.element.removeEventListener('focus', this.onFocus);
        this.element.removeEventListener('blur', this.onBlur);
        this.element.removeEventListener('click', this.onClick);
        this.panel.removeEventListener('pointerenter', this.onEnter);
        this.panel.removeEventListener('pointerleave', this.onLeave);
        this.panel.removeEventListener('focusout', this.onPanelFocusOut);
        this.panel.removeEventListener('beforetoggle', this.onBeforeToggle);
        this.panel.removeEventListener('toggle', this.onToggle);
    }

    isOpen() {
        return this.panel.matches(':popover-open');
    }

    // Opened for a look, under its button (source: the same implicit anchor as popovertarget).
    open() {
        if (this.isOpen()) {
            return;
        }
        try {
            this.panel.showPopover({source: this.element});
        } catch {
            this.panel.showPopover();
        }
        this.look = true;
    }

    // Closes a panel opened for a look, unless the pointer or the keyboard focus is still on the button or the panel.
    closeLook() {
        if (!this.look || !this.isOpen()) {
            return;
        }
        if (this.element.matches(':hover, :focus-visible') || this.panel.matches(':hover, :focus-within')) {
            return;
        }
        this.panel.hidePopover();
        this.look = false;
    }

    cancel() {
        clearTimeout(this.timer);
    }
}

function supportsHint() {
    const probe = document.createElement('div');
    probe.popover = 'hint';

    return probe.popover === 'hint';
}
