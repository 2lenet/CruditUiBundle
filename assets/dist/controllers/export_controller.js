import { Controller } from '@hotwired/stimulus';

/*
 * Export (component ExportDialog, on its form): follows an export that the browser downloads. The form is a plain GET
 * to the server, which makes the file before it answers, so nothing tells the page when the download begins. Two signals:
 *
 * - The cookie. The form sends a token (`download_token`); the server answers the file with a cookie
 *   `crudit_export=<token>` (path `/`, not HttpOnly: see the helper of the bundle, or docs/components.md); this
 *   controller reads it every half second. Seen: the dialog closes and a toast says "Export done".
 * - The hidden frame. With JavaScript the form is sent into a hidden <iframe> (`target`), so the page never moves. A
 *   file does not fire `load` on the frame; a page does: an error (500, 403), a login page after the session ended, a
 *   redirect to the list with a message. That is an error, shown at once: the user is not left waiting. Without
 *   JavaScript there is no frame: the browser downloads the file, or shows the page the server answered.
 *
 * Not heard of within `timeout` ms (the server hangs): an error is shown and the form can be sent again, **but the
 * token is still watched** (ten times the delay): a file that comes late still closes the dialog and says it is done,
 * so that nobody exports twice because the server was slow.
 *
 * - Busy: the submit button (outside the form: found by its attribute "form") is put in loading by its own controller
 *   `button` (spinner, `aria-busy`, `aria-disabled`: it keeps the focus). The status line (a polite live region) says
 *   the file is being made; a second click does nothing.
 * - Error: its own element (role="alert", assertive), shown only then; it goes when the dialog opens again.
 * - The cookie is deleted when seen. It holds a random token, nothing else.
 */
const BUTTON = '2lenet--crudit-ui-bundle--button';

export default class extends Controller {
    static targets = ['token', 'status', 'error'];
    static values = {
        busy: String,
        done: String,
        failed: String,
        cookie: { type: String, default: 'crudit_export' },
        timeout: { type: Number, default: 60000 },
    };

    waiting = false;
    timer = null;
    // The tokens still watched: token => the time after which it is forgotten
    pending = new Map();
    deadline = 0;
    token = null;
    frame = null;

    connect() {
        this.dialog = this.element.closest('dialog');
        this.onOpen = (event) => {
            if (event.target === this.dialog) {
                this.clearError();
            }
        };
        this.dialog?.addEventListener('2lenet--crudit-ui-bundle--dialog:open', this.onOpen);
    }

    disconnect() {
        this.dialog?.removeEventListener('2lenet--crudit-ui-bundle--dialog:open', this.onOpen);
        clearInterval(this.timer);
        this.frame?.remove();
    }

    start(event) {
        if (this.waiting) {
            event.preventDefault();

            return;
        }
        this.token = crypto.randomUUID ? crypto.randomUUID() : String(Date.now()) + Math.random().toString(16).slice(2);
        this.tokenTarget.disabled = false;
        this.tokenTarget.value = this.token;
        this.waiting = true;
        this.deadline = Date.now() + this.timeoutValue;
        this.pending.set(this.token, Date.now() + this.timeoutValue * 10);
        this.clearError();
        this.statusTarget.textContent = this.busyValue;
        this.setBusy(true);
        this.openFrame();
        this.watch();
    }

    // The form goes into a hidden frame: a page that loads there is an answer that is not a file.
    openFrame() {
        this.frame?.remove();
        const frame = document.createElement('iframe');
        frame.name = `${this.element.id}-frame`;
        frame.hidden = true;
        frame.tabIndex = -1;
        frame.setAttribute('aria-hidden', 'true');
        this.element.append(frame);
        this.element.target = frame.name;
        this.frame = frame;
        const token = this.token;
        frame.addEventListener('load', () => {
            let address;
            try {
                address = frame.contentWindow.location.href;
            } catch {
                // another origin (a login page of an identity provider): a page, not a file
                address = 'page';
            }
            // the empty page the frame starts with
            if (address !== 'about:blank') {
                this.pending.delete(token);
                if (token === this.token && this.waiting) {
                    this.fail();
                }
            }
        });
    }

    watch() {
        if (this.timer === null) {
            this.timer = setInterval(() => this.check(), 500);
        }
    }

    check() {
        const cookies = document.cookie.split('; ');
        for (const [token, until] of this.pending) {
            if (cookies.includes(`${this.cookieValue}=${token}`)) {
                document.cookie = `${this.cookieValue}=; Max-Age=0; path=/`;
                this.done();

                return;
            }
            if (Date.now() > until) {
                this.pending.delete(token);
            }
        }
        if (this.waiting && Date.now() > this.deadline) {
            this.fail();
        }
        if (this.pending.size === 0 && !this.waiting) {
            this.unwatch();
        }
    }

    // The download begins: whatever attempt it is (a late one too).
    done() {
        this.pending.clear();
        this.unwatch();
        this.finish();
        this.clearError();
        this.dialog?.close();
        document.dispatchEvent(new CustomEvent('crudit-ui:toast', { detail: { message: this.doneValue, tone: 'success' } }));
    }

    // The attempt is over without a file: the error, and the form can be sent again.
    fail() {
        this.finish();
        this.errorTarget.textContent = this.failedValue;
        this.errorTarget.hidden = false;
    }

    finish() {
        this.waiting = false;
        this.tokenTarget.disabled = true;
        this.statusTarget.textContent = '';
        this.element.removeAttribute('target');
        this.setBusy(false);
    }

    unwatch() {
        clearInterval(this.timer);
        this.timer = null;
    }

    clearError() {
        this.errorTarget.hidden = true;
        this.errorTarget.textContent = '';
    }

    // The submit button is in the footer of the dialog, outside the form (so outside the targets of Stimulus): it belongs
    // to the form by its attribute "form". Its own controller shows the spinner.
    setBusy(on) {
        for (const button of document.querySelectorAll(`button[type="submit"][form="${CSS.escape(this.element.id)}"]`)) {
            const controller = this.application.getControllerForElementAndIdentifier(button, BUTTON);
            if (controller) {
                on ? controller.busy() : controller.restore();
            } else if (on) {
                button.setAttribute('aria-busy', 'true');
                button.setAttribute('aria-disabled', 'true');
            } else {
                button.removeAttribute('aria-busy');
                button.removeAttribute('aria-disabled');
            }
        }
    }
}
