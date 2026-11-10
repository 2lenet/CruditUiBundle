import { Controller } from '@hotwired/stimulus';

/*
 * Counter of the characters of a text or a long text with a maximum length (form theme, block crudit_ui_counter), on
 * the input. The counter `<id>_count` (attached to the input by aria-describedby) shows "27 / 80 characters" while
 * typing. Near the limit (the last tenth, 5 characters at least, or the value threshold), the characters
 * left are told to assistive technologies by a live region, once the typing pauses: not at each key.
 * The texts come from the theme (data-2lenet--crudit-ui-bundle--char-count-*-value), translated by the server.
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
    };

    connect() {
        const counter = document.getElementById(`${this.element.id}_count`);
        this.text = counter?.querySelector('[data-crudit-ui-count]');
        this.live = counter?.querySelector('[data-crudit-ui-count-live]');
        this.max = this.element.maxLength;
        if (!this.text || this.max <= 0) {
            return;
        }
        this.plural = new Intl.PluralRules(document.documentElement.lang || undefined);
        this.onInput = () => this.update();
        this.element.addEventListener('input', this.onInput);
        this.update();
    }

    disconnect() {
        clearTimeout(this.timer);
        if (this.onInput) {
            this.element.removeEventListener('input', this.onInput);
        }
    }

    update() {
        const length = this.element.value.length;
        this.text.textContent = (this.max > 1 ? this.manyValue : this.oneValue).replace('%length%', String(length));

        clearTimeout(this.timer);
        const left = Math.max(0, this.max - length);
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
