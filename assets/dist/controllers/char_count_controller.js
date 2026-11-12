import { Controller } from '@hotwired/stimulus';

/*
 * Counter of the characters of a text or a long text with a maximum length (form theme, block crudit_ui_counter), on
 * the input. The counter `<id>_count` (attached to the input by aria-describedby) shows "27 / 80 characters" while
 * typing. Near the limit (the last tenth, 5 characters at least, or the value threshold), the characters
 * left are told to assistive technologies by a live region `<id>_count_live` next to the counter (not inside it: the
 * description of the input stays the count), once the typing pauses: not at each key, and not when the
 * page is loaded (a value already near the limit is only shown). A reset of the form shows the value put back. A value
 * changed by a script is shown after an input event.
 * The texts come from the theme (data-2lenet--crudit-ui-bundle--char-count-*-value), translated by the server. The
 * plural (left_one for the category one, left_many for the others) follows the locale of the request (value locale),
 * else the lang of the page.
 */
const PAUSE = 600;

export default class extends Controller {
    static values = {
        one: String,
        many: String,
        leftOne: String,
        leftMany: String,
        full: String,
        threshold: Number,
        locale: String,
    };

    connect() {
        const counter = document.getElementById(`${this.element.id}_count`);
        this.text = counter?.querySelector('[data-crudit-ui-count]');
        this.live = document.getElementById(`${this.element.id}_count_live`);
        this.max = this.element.maxLength;
        if (!this.text || this.max <= 0) {
            return;
        }
        this.plural = new Intl.PluralRules(this.localeValue || document.documentElement.lang || undefined);
        this.onInput = () => {
            this.render();
            this.schedule();
        };
        this.element.addEventListener('input', this.onInput);
        // The browser puts the values back after the event reset
        this.form = this.element.form;
        this.onReset = () => setTimeout(() => {
            clearTimeout(this.timer);
            this.render();
            if (this.live) {
                this.live.textContent = '';
            }
        });
        this.form?.addEventListener('reset', this.onReset);
        this.render();
    }

    disconnect() {
        clearTimeout(this.timer);
        if (this.onInput) {
            this.element.removeEventListener('input', this.onInput);
        }
        if (this.onReset) {
            this.form?.removeEventListener('reset', this.onReset);
        }
    }

    // The text "27 / 80 characters"
    render() {
        this.text.textContent = (this.max > 1 ? this.manyValue : this.oneValue).replace('%length%', String(this.element.value.length));
    }

    // Near the limit, the characters left told once the typing pauses
    schedule() {
        clearTimeout(this.timer);
        const left = Math.max(0, this.max - this.element.value.length);
        const threshold = this.hasThresholdValue ? this.thresholdValue : Math.max(5, Math.ceil(this.max / 10));
        if (left > threshold) {
            // Far from the limit: nothing to tell, and the next message near the limit will be told again
            if (this.live) {
                this.live.textContent = '';
            }

            return;
        }
        this.timer = setTimeout(() => this.tell(left), PAUSE);
    }

    tell(left) {
        if (!this.live) {
            return;
        }
        const message = left === 0
            ? this.fullValue
            : (this.plural.select(left) === 'one' ? this.leftOneValue : this.leftManyValue).replace('%count%', String(left));
        if (this.live.textContent !== message) {
            this.live.textContent = message;
        }
    }
}
