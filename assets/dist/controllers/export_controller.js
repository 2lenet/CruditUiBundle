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
 * token is still watched** (ten times the delay): a file that comes late closes the dialog and says it is done, so that
 * nobody exports twice because the server was slow. Each attempt has its own token: a late file of an older attempt only
 * shows the toast, the attempt in progress goes on.
 * Each attempt has its own frame, removed when its token is no longer watched (removing it would cancel the request).
 * - The file must come with `Content-Disposition: attachment`. Shown inline, the frame loads it (as a page): the cookie
 *   is there but nothing was downloaded, which is said (a message, and a warning in the console for the developer).
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
        inline: String,
        cookie: { type: String, default: 'crudit_export' },
        timeout: { type: Number, default: 60000 },
    };

    waiting = false;
    timer = null;
    // The tokens still watched: token => the time after which it is forgotten
    pending = new Map();
    deadline = 0;
    token = null;
    // One hidden frame per attempt, kept while its token is watched (a late file of an older attempt still has its
    // frame): token => frame
    frames = new Map();
    attempts = 0;

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
        for (const frame of this.frames.values()) {
            frame.remove();
        }
        this.frames.clear();
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

    // The form goes into a hidden frame: a page that loads there is an answer that is not a file. Each attempt has its
    // own frame, with its own name (`target` aims at the first frame of that name: a shared name would navigate the frame
    // of the older attempt, and remove() would cancel its request).
    openFrame() {
        const frame = document.createElement('iframe');
        frame.name = `${this.element.id}-frame-${++this.attempts}`;
        frame.hidden = true;
        frame.tabIndex = -1;
        frame.setAttribute('aria-hidden', 'true');
        this.element.append(frame);
        this.element.target = frame.name;
        const token = this.token;
        this.frames.set(token, frame);
        frame.addEventListener('load', () => {
            let address;
            try {
                address = frame.contentWindow.location.href;
            } catch {
                // another origin (a login page of an identity provider): a page, not a file
                address = 'page';
            }
            // the empty page the frame starts with
            if (address === 'about:blank') {
                return;
            }
            this.pending.delete(token);
            // The cookie came with this page: the server made the file but sent it to be shown (no
            // `Content-Disposition: attachment`), so the frame displayed it and nothing was downloaded.
            const inline = this.hasCookie(token);
            if (inline) {
                this.consume(token);
            }
            this.dropFrame(token);
            if (token === this.token && this.waiting) {
                this.fail(inline ? this.inlineValue : this.failedValue);
            }
        });
    }

    // The cookie of an inline answer is deleted and the developer is warned.
    consume(token) {
        document.cookie = `${this.cookieValue}=; Max-Age=0; path=/`;
        console.warn('crudit-ui export: the server answered the file without "Content-Disposition: attachment", the browser showed it in the hidden frame instead of downloading it.');
    }

    // The frame of an attempt is removed when its token is no longer watched.
    dropFrame(token) {
        this.frames.get(token)?.remove();
        this.frames.delete(token);
    }

    // The frame of this attempt is already navigating to the export. A download leaves it on about:blank; an answer shown
    // inline engages the navigation as soon as the headers are in, which is before `load` (the cookie comes with the
    // headers too, so check() can see it first). A frame that is gone (an older attempt) says nothing.
    inFrame(token) {
        const frame = this.frames.get(token);
        if (!frame) {
            return false;
        }
        try {
            return frame.contentWindow.location.href !== 'about:blank';
        } catch {
            // another origin: a page has been loaded
            return true;
        }
    }

    watch() {
        if (this.timer === null) {
            this.timer = setInterval(() => this.check(), 500);
        }
    }

    hasCookie(token) {
        return document.cookie.split('; ').includes(`${this.cookieValue}=${token}`);
    }

    check() {
        for (const [token, until] of this.pending) {
            if (this.hasCookie(token)) {
                this.pending.delete(token);
                if (this.inFrame(token)) {
                    // inline: not a download
                    this.consume(token);
                    this.dropFrame(token);
                    if (token === this.token && this.waiting) {
                        this.fail(this.inlineValue);
                    }
                    if (this.pending.size === 0 && !this.waiting) {
                        this.unwatch();
                    }

                    return;
                }
                document.cookie = `${this.cookieValue}=; Max-Age=0; path=/`;
                this.dropFrame(token);
                this.done(token === this.token);

                return;
            }
            if (Date.now() > until) {
                this.pending.delete(token);
                this.dropFrame(token);
            }
        }
        if (this.waiting && Date.now() > this.deadline) {
            this.fail(this.failedValue);
        }
        if (this.pending.size === 0 && !this.waiting) {
            this.unwatch();
        }
    }

    // A download begins. If it is the attempt in progress (or the last one, that had timed out): the dialog is over. If it
    // is an older one (A timed out, the user sent B, then A arrives): the toast only, B goes on.
    done(current) {
        if (current) {
            this.finish();
            this.clearError();
            this.dialog?.close();
        }
        if (this.pending.size === 0 && !this.waiting) {
            this.unwatch();
        }
        document.dispatchEvent(new CustomEvent('crudit-ui:toast', { detail: { message: this.doneValue, tone: 'success' } }));
    }

    // The attempt is over without a file: the error, and the form can be sent again.
    fail(message) {
        this.finish();
        this.errorTarget.textContent = message;
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
