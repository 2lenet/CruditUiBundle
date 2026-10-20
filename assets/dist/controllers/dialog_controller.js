import { Controller } from '@hotwired/stimulus';

// Invoker Commands (commandfor, command): Chrome 135, Firefox 144, Safari 26.2. Before, the controller runs them.
const NATIVE_COMMANDS = 'commandForElement' in HTMLButtonElement.prototype;

/*
 * Dialog (component Dialog): a native <dialog>, opened modal. The browser traps the focus (Tab never reaches the page
 * under it), makes the rest of the page inert, gives the focus to the element with autofocus, else to the first
 * focusable one, and gives it back to the element that had it when the dialog closes. The controller adds:
 *
 * - The commands of a button of the page, commandfor="<id of the dialog>" with command="show-modal", "close" or
 *   "request-close", in the browsers that do not run them yet. As natively, the dialog receives the event "command"
 *   (command, source; cancelable), and nothing runs for a submit button of a form, nor when the click is cancelled
 *   (a button in loading or disabled with aria-disabled).
 * - dismiss (the close button, a click on the backdrop): fires "cancel" first, as Escape does, then closes the
 *   dialog. A project cancels it (preventDefault()) to keep the dialog open (a form with changes). requestClose() of
 *   the browser when it has it, with its rules (Chrome closes all the same on a second try without an action of the
 *   user in between).
 * - The focus put on <main> when the element that opened the dialog is gone (the row it deleted): it does not fall
 *   back to the top of the page.
 * - The value "open": the dialog is shown modal when it connects (a form sent back with its errors), or when the value
 *   is set again (a page refreshed by a morph of Turbo, which keeps the element); then the value is removed.
 * - Closed when the controller disconnects and before Turbo caches the page: a snapshot is never restored open. A
 *   dialog moved in the page is closed too (Stimulus disconnects it, and a modal dialog moved is no longer modal).
 * - A page refreshed by a morph of Turbo: the dialog follows the server, closed unless it has the value "open" (it then
 *   stays open, untouched). A dialog to keep open across refreshes takes data-turbo-permanent.
 *
 * Events: "open" and "close" (2lenet--crudit-ui-bundle--dialog:open / :close), on the dialog, however it opens or
 * closes. The native "cancel" and "close" of <dialog> are there too.
 */
export default class extends Controller {
    static values = {open: Boolean};

    connect() {
        // Each change of the attribute "open", even several in one task (opened and closed at once): a record without
        // old value is an opening, the removal of the attribute a closing.
        this.observer = new MutationObserver((records) => records.forEach((record, index) => {
            const next = records[index + 1];
            const present = next ? next.oldValue !== null : this.element.hasAttribute('open');
            if (record.oldValue === null && present) {
                this.dispatch('open');
            } else if (record.oldValue !== null && !present) {
                this.dispatch('close');
            }
        }));
        this.observer.observe(this.element, {attributes: true, attributeFilter: ['open'], attributeOldValue: true});

        // A click on the backdrop: pressed and released outside the box of the dialog. A selection of text that ends
        // on the backdrop does not close it.
        this.onPointerDown = (event) => {
            this.pressedOutside = this.outside(event);
        };
        this.onClick = (event) => {
            if (event.target === this.element && this.pressedOutside && this.outside(event)) {
                this.dismiss();
            }
            this.pressedOutside = false;
        };
        // The browser has already given the focus back, when it could: during close(), before this event. Otherwise it
        // is still on an element of the closed dialog (Chrome moves it to <body> later), or on <body>.
        this.onClose = () => {
            const focus = document.activeElement;
            if (focus === null || focus === document.body || this.element.contains(focus)) {
                const main = document.querySelector('main');
                if (main) {
                    if (!main.hasAttribute('tabindex')) {
                        main.setAttribute('tabindex', '-1');
                    }
                    main.focus({preventScroll: true});
                }
            }
        };
        this.onBeforeCache = () => this.close();
        // A morph of Turbo removes the attribute "open", which the server never renders: the dialog would be hidden but
        // stay modal, the page locked and inert. It is closed instead, unless the morph has just set the value "open"
        // (a form sent back with its errors): it then stays open as it is, the focus where it was.
        this.onBeforeMorphAttribute = (event) => {
            if (event.target !== this.element || event.detail?.attributeName !== 'open' || event.detail.mutationType !== 'remove') {
                return;
            }
            event.preventDefault();
            if (!this.element.hasAttribute(`data-${this.identifier}-open-value`)) {
                this.close();
            }
        };

        this.element.addEventListener('pointerdown', this.onPointerDown);
        this.element.addEventListener('click', this.onClick);
        this.element.addEventListener('close', this.onClose);
        this.element.addEventListener('turbo:before-morph-attribute', this.onBeforeMorphAttribute);
        document.addEventListener('turbo:before-cache', this.onBeforeCache);

        if (!NATIVE_COMMANDS) {
            this.onCommand = (event) => this.command(event);
            document.addEventListener('click', this.onCommand);
        }

        this.connected = true;
        if (this.openValue) {
            this.openFromValue();
        }
    }

    // Also called before connect(), which then opens the dialog once its listeners are there.
    openValueChanged(open) {
        if (open && this.connected) {
            this.openFromValue();
        }
    }

    openFromValue() {
        this.element.removeAttribute(`data-${this.identifier}-open-value`);
        this.open();
    }

    disconnect() {
        this.connected = false;
        this.close();
        this.observer.disconnect();
        this.element.removeEventListener('pointerdown', this.onPointerDown);
        this.element.removeEventListener('click', this.onClick);
        this.element.removeEventListener('close', this.onClose);
        this.element.removeEventListener('turbo:before-morph-attribute', this.onBeforeMorphAttribute);
        document.removeEventListener('turbo:before-cache', this.onBeforeCache);
        if (this.onCommand) {
            document.removeEventListener('click', this.onCommand);
        }
    }

    open() {
        if (!this.element.open && this.element.isConnected) {
            this.element.showModal();
        }
    }

    // Closes the dialog, without "cancel": a choice made in it (Cancel, Save).
    close() {
        if (this.element.open) {
            this.element.close();
        }
    }

    // Closes the dialog unless "cancel" is cancelled: the close button, the backdrop.
    dismiss() {
        if (!this.element.open) {
            return;
        }
        if (typeof this.element.requestClose === 'function') {
            this.element.requestClose();
        } else if (this.element.dispatchEvent(new Event('cancel', {cancelable: true}))) {
            this.element.close();
        }
    }

    outside(event) {
        const rect = this.element.getBoundingClientRect();

        return event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom;
    }

    // The command of a button of the page, for this dialog (browsers without Invoker Commands).
    command(event) {
        const button = event.target instanceof Element ? event.target.closest('button[commandfor]') : null;
        if (!button || event.defaultPrevented || button.disabled || button.getAttribute('commandfor') !== this.element.id) {
            return;
        }
        if (button.form && button.type !== 'button') {
            return;
        }
        const command = button.getAttribute('command') ?? '';
        if (!this.element.dispatchEvent(Object.assign(new Event('command', {cancelable: true}), {command, source: button}))) {
            return;
        }
        const commands = {
            'show-modal': () => this.open(),
            // The value of the button becomes the returnValue of the dialog, as natively.
            close: () => this.element.open && this.element.close(button.hasAttribute('value') ? button.value : undefined),
            'request-close': () => this.dismiss(),
        };
        commands[command]?.();
    }
}
