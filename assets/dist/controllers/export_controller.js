import { Controller } from '@hotwired/stimulus';

/*
 * Export (component ExportDialog, on its form): follows an export that the browser downloads. The form is a plain GET
 * to the server, which makes the file before it answers, so nothing tells the page when the download begins. The
 * form sends a token (`download_token`), the server answers the file with a cookie `crudit_export=<token>`, and this
 * controller reads it every half second: the dialog closes and a toast says "Export done". If it does not come
 * within `timeout` ms, the dialog says it failed and the form can be sent again.
 *
 * - Busy: the submit button (outside the form: found by its attribute "form") is `aria-busy` and `aria-disabled` (not
 *   `disabled`: it keeps the focus), the status line (a polite
 *   live region) tells the file is being made, a second click does nothing.
 * - Error: its own element (role="alert", assertive), shown only then.
 * - The cookie is read by the script (not HttpOnly) and deleted when seen. It holds a random token, nothing else.
 * - Without JavaScript: the browser downloads the file, the dialog stays open, nothing else changes.
 */
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
    deadline = 0;

    start(event) {
        if (this.waiting) {
            event.preventDefault();

            return;
        }
        const token = crypto.randomUUID ? crypto.randomUUID() : String(Date.now()) + Math.random().toString(16).slice(2);
        this.tokenTarget.disabled = false;
        this.tokenTarget.value = token;
        this.waiting = true;
        this.deadline = Date.now() + this.timeoutValue;
        this.errorTarget.hidden = true;
        this.errorTarget.textContent = '';
        this.statusTarget.textContent = this.busyValue;
        for (const button of this.buttons) {
            button.setAttribute('aria-busy', 'true');
            button.setAttribute('aria-disabled', 'true');
        }
        this.timer = setInterval(() => this.check(token), 500);
    }

    check(token) {
        const cookies = document.cookie.split('; ');
        if (cookies.includes(`${this.cookieValue}=${token}`)) {
            document.cookie = `${this.cookieValue}=; Max-Age=0; path=/`;
            this.stop();
            this.element.closest('dialog')?.close();
            document.dispatchEvent(new CustomEvent('crudit-ui:toast', { detail: { message: this.doneValue, tone: 'success' } }));

            return;
        }
        if (Date.now() > this.deadline) {
            this.stop();
            this.errorTarget.textContent = this.failedValue;
            this.errorTarget.hidden = false;
        }
    }

    stop() {
        clearInterval(this.timer);
        this.timer = null;
        this.waiting = false;
        this.tokenTarget.disabled = true;
        this.statusTarget.textContent = '';
        for (const button of this.buttons) {
            button.removeAttribute('aria-busy');
            button.removeAttribute('aria-disabled');
        }
    }

    // The submit button is in the footer of the dialog, outside the form (so outside the targets of Stimulus): it
    // belongs to the form by its attribute "form".
    get buttons() {
        return document.querySelectorAll(`button[type="submit"][form="${CSS.escape(this.element.id)}"]`);
    }

    disconnect() {
        clearInterval(this.timer);
    }
}
