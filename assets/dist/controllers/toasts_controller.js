import { Controller } from '@hotwired/stimulus';

/*
 * Region of the toasts (component Toasts). A toast is any element of the region with the target "toast": rendered
 * by the server (flash messages), appended by a Turbo Stream, or shown by the JavaScript of the page:
 *
 *     document.dispatchEvent(new CustomEvent('crudit-ui:toast', {detail: {message: 'Saved.', tone: 'success'}}));
 *
 * (tone: info, success, warning, danger; persistent: true or false to change the default, an error only). The
 * message is text, never HTML. The first region of the page takes the event.
 *
 * - Announced: the toasts are not a live region, a toast moved in the page would be read again. Its text goes in one
 *   of the two live regions of the region, hidden, polite or assertive (an error), a little after it is added: a live
 *   region is not read in Chrome and Safari when it is filled at once (a page that loads, a region just moved).
 * - Removed after the value "duration" (ms), unless persistent; the time stops while the region is hovered or has the
 *   focus (WCAG 2.2.1), and while the page is hidden. "max" toasts at most: the oldest that is not persistent goes.
 * - In a modal dialog: the rest of the page is inert, a toast under it would be neither seen nor read nor closed. A
 *   toast added while a dialog is open moves the region into it (it is in position: fixed, the dialog does not cut
 *   it), until the dialog closes. Stimulus then disconnects and connects the controller again: the state of the
 *   toasts is kept by element, out of the controller.
 * - Closed by its button (action dismiss): the focus goes to the next toast, else to the close button of the dialog,
 *   to the closest focusable container or to <main> (as for an alert).
 * - Turbo: emptied before Turbo caches the page (a page restored by Back does not show the toasts again).
 */
const TICK = 250;
const ANNOUNCE_DELAY = 300;
const ANNOUNCE_KEEP = 10000;

// Time left of each toast (null: persistent). A toast known here is not announced again when the region moves.
const remaining = new WeakMap();
// Place of a region moved into a dialog: parent and next sibling.
const homes = new WeakMap();

// The modal dialog on top: the one that has the focus, else the last one of the page.
function topDialog() {
    const active = document.activeElement?.closest('dialog');
    if (active?.matches(':modal')) {
        return active;
    }

    return Array.from(document.querySelectorAll('dialog')).filter((dialog) => dialog.matches(':modal')).at(-1) ?? null;
}

// Puts a region back in its place, or into the dialog still open under the one that closed (unless intoDialog is
// false). Nothing when its place is gone: a page replaced by Turbo.
function goBack(region, intoDialog = true) {
    const home = homes.get(region);
    if (!home) {
        return;
    }
    if (!home.parent.isConnected) {
        homes.delete(region);

        return;
    }
    const dialog = intoDialog ? topDialog() : null;
    if (dialog) {
        moveInto(region, dialog);

        return;
    }
    homes.delete(region);
    home.parent.insertBefore(region, home.next?.parentNode === home.parent ? home.next : null);
}

function moveInto(region, dialog) {
    if (!homes.has(region)) {
        homes.set(region, {parent: region.parentNode, next: region.nextSibling});
    }
    dialog.append(region);
    dialog.addEventListener('close', () => region.parentNode === dialog && goBack(region), {once: true});
}

export default class extends Controller {
    static targets = ['toast', 'template', 'polite', 'assertive'];

    static values = {duration: {type: Number, default: 6000}, max: {type: Number, default: 4}};

    connect() {
        this.onToast = (event) => {
            event.stopImmediatePropagation();
            this.show(event.detail ?? {});
        };
        this.onBeforeCache = () => this.clear();
        document.addEventListener('crudit-ui:toast', this.onToast);
        document.addEventListener('turbo:before-cache', this.onBeforeCache);
        this.start();
    }

    disconnect() {
        this.stop();
        document.removeEventListener('crudit-ui:toast', this.onToast);
        document.removeEventListener('turbo:before-cache', this.onBeforeCache);

        // Removed with the dialog it was moved into (a row deleted with its dialog): back in its place.
        if (!this.element.isConnected) {
            goBack(this.element);
        }
    }

    // Called before connect() for the toasts of the page, then for each toast added.
    toastTargetConnected(toast) {
        if (remaining.has(toast)) {
            return;
        }
        remaining.set(toast, toast.dataset.persistent === 'true' ? null : this.durationValue);
        this.trim(toast);
        const dialog = topDialog();
        if (dialog && !dialog.contains(this.element)) {
            moveInto(this.element, dialog);
        }
        setTimeout(() => this.announce(toast), ANNOUNCE_DELAY);
        this.start();
    }

    // A toast of the JavaScript, copy of the template of its tone.
    show({message = '', tone = 'info', persistent = null}) {
        const template = this.templateTargets.find((element) => element.dataset.tone === tone) ?? this.templateTargets.find((element) => element.dataset.tone === 'info');
        const toast = template?.content.firstElementChild?.cloneNode(true);
        if (!toast) {
            return;
        }
        toast.querySelector('.crudit-ui-toast__message').append(String(message));
        if (persistent === true) {
            toast.dataset.persistent = 'true';
        } else if (persistent === false) {
            delete toast.dataset.persistent;
        }
        this.element.append(toast);
    }

    dismiss(event) {
        const toast = event.target.closest('.crudit-ui-toast');
        if (!toast) {
            return;
        }
        const hadFocus = toast.contains(document.activeElement);
        const others = this.toastTargets;
        const index = others.indexOf(toast);
        const next = others[index + 1] ?? others[index - 1];
        toast.remove();

        if (hadFocus) {
            const dialog = this.element.closest('dialog');
            const container = dialog ? null : this.element.parentElement?.closest('[tabindex], main') ?? document.querySelector('main');
            const target = next?.querySelector('button') ?? dialog?.querySelector('.crudit-ui-dialog__close') ?? container;
            if (target === container && target && !target.hasAttribute('tabindex')) {
                target.setAttribute('tabindex', '-1');
            }
            target?.focus({preventScroll: true});
        }
    }

    // Removes every toast, and puts the region back in its place (the snapshot of Turbo).
    clear() {
        this.toastTargets.forEach((toast) => toast.remove());
        goBack(this.element, false);
    }

    // Beyond "max", the oldest toast that is not persistent goes, else the oldest; never the one just added.
    trim(added) {
        const others = this.toastTargets.filter((toast) => toast !== added);
        while (others.length > 0 && others.length >= this.maxValue) {
            const oldest = others.find((toast) => toast.dataset.persistent !== 'true') ?? others[0];
            others.splice(others.indexOf(oldest), 1);
            oldest.remove();
        }
    }

    announce(toast) {
        if (!toast.isConnected) {
            return;
        }
        const live = toast.dataset.assertive === 'true' ? this.assertiveTarget : this.politeTarget;
        const text = document.createElement('p');
        text.textContent = toast.querySelector('.crudit-ui-toast__message')?.textContent.trim() ?? '';
        live.append(text);
        setTimeout(() => text.remove(), ANNOUNCE_KEEP);
    }

    tick() {
        const timed = this.toastTargets.filter((toast) => remaining.get(toast) != null);
        if (timed.length === 0) {
            this.stop();

            return;
        }
        if (document.hidden || this.element.matches(':hover, :focus-within')) {
            return;
        }
        timed.forEach((toast) => {
            const left = remaining.get(toast) - TICK;
            remaining.set(toast, left);
            if (left <= 0) {
                toast.remove();
            }
        });
    }

    start() {
        if (!this.timer && this.toastTargets.some((toast) => remaining.get(toast) != null)) {
            this.timer = setInterval(() => this.tick(), TICK);
        }
    }

    stop() {
        clearInterval(this.timer);
        this.timer = null;
    }
}
