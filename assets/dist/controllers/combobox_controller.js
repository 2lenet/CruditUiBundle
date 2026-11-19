import { Controller } from '@hotwired/stimulus';
import { fold, find, marked } from '../utils/text.js';
import { placeFloating, resetPosition } from '../utils/position.js';

const DEBOUNCE = 200;
// The count is announced once the typing has stopped: not at every key.
const ANNOUNCE = 600;
// Choices asked to the server at a time, and distance to the end of the list (px) from which the next ones are asked.
const PAGE = 20;
const NEAR_END = 48;
// Choices of the page shown at a time: a long list is narrowed by the search, not scrolled.
const SHOWN = 50;

/*
 * List with search (block crudit_ui_combobox_widget of the form theme, docs/forms.md): ARIA combobox pattern. The focus
 * stays in the input, the active option is given by aria-activedescendant; aria-selected says the choices made.
 *
 * - The choices come from the hidden <select> of the field (filtered in the page), or from the server (value url):
 *   GET <url>?q=<text>&offset=<n>&limit=20, answer {"items": [{"id", "text"}], "next_offset", "total_count"}. One
 *   request at a time: the one still running is cancelled by a newer one, its answer is never shown. The next page is
 *   asked when the list is scrolled to its end. The text of the choices is always set as text, never as HTML.
 *   The choices of the page: the first 50 that match, then a line that tells how many there are.
 * - The list is in position: fixed under the field (above it when there is no room below): a container that scrolls
 *   or hides its overflow (a dialog, a card) does not cut it. It follows the field when the page scrolls, and stays
 *   above the virtual keyboard of a phone (visualViewport).
 * - The list opens on focus, on a click and on the arrows. Up and down change the active option, from the last to the
 *   first and back; Enter chooses it (the form is not sent); Escape closes the list; Backspace in an empty input
 *   removes the last choice. A click outside, or Tab, closes it.
 * - multiple: a choice is added or removed, the list stays open; else a choice replaces the other one and closes the
 *   list. Each choice made is a token with a button that removes it; the focus then goes back to the input.
 * - The value is written in the hidden <select> (its options selected) or the hidden input (the values joined by the
 *   separator), which dispatches "change". The reset of the form gives back the first choices.
 * - Announced in a live region: the count of choices (after 600 ms), no choice, an error, a choice added or removed.
 *
 * Events: "change" ({values}) on the element, "change" (bubbles) on the hidden field.
 */
export default class extends Controller {
    static targets = ['input', 'tokens', 'listbox', 'status', 'icons', 'value'];
    static values = {
        url: String,
        multiple: Boolean,
        minLength: { type: Number, default: 0 },
        separator: { type: String, default: ',' },
        removeText: String,
        addedText: String,
        removedText: String,
        emptyText: String,
        loadingText: String,
        errorText: String,
        countOneText: String,
        countOtherText: String,
        moreText: String,
    };

    connect() {
        this.selected = this.readTokens();
        this.initial = [...this.selected];
        this.placeholder = this.inputTarget.getAttribute('placeholder') ?? '';
        this.choices = [];
        this.activeIndex = -1;
        this.isOpen = false;
        this.loaded = null;
        this.nextOffset = null;
        this.pending = false;
        this.loading = false;
        this.all = this.urlValue ? null : Array.from(this.valueTarget.options)
            .filter((option) => option.value !== '')
            .map((option) => {
                const label = option.textContent.trim();

                return { value: option.value, label, folded: fold(label) };
            });

        this.onFocusOut = (event) => {
            if (!this.element.contains(event.relatedTarget)) {
                this.close();
            }
        };
        // After the reset: the browser gives the hidden fields their first value at the same time.
        this.onReset = () => window.setTimeout(() => {
            this.selected = [...this.initial];
            this.inputTarget.value = '';
            this.renderTokens();
            this.sync(false);
        });
        this.onBeforeCache = () => this.close();
        // The page or a container scrolls (not the list): the list follows the field, once per frame.
        this.onScroll = (event) => {
            if (!(event.target instanceof Node && this.listboxTarget.contains(event.target))) {
                this.follow();
            }
        };
        this.onResize = () => this.follow();

        this.form = this.element.closest('form');
        this.form?.addEventListener('reset', this.onReset);
        this.element.addEventListener('focusout', this.onFocusOut);
        document.addEventListener('turbo:before-cache', this.onBeforeCache);
        this.renderTokens();
        this.close();
    }

    disconnect() {
        this.form?.removeEventListener('reset', this.onReset);
        this.element.removeEventListener('focusout', this.onFocusOut);
        document.removeEventListener('turbo:before-cache', this.onBeforeCache);
        window.clearTimeout(this.debounce);
        window.clearTimeout(this.announce);
        this.request?.abort();
        this.unfollow();
    }

    // A click in the field, outside the input and the buttons: the input takes the focus.
    focus(event) {
        if (!event.target.closest('button, input')) {
            this.inputTarget.focus();
        }
    }

    open() {
        if (this.isOpen || this.inputTarget.disabled) {
            return;
        }
        this.isOpen = true;
        this.listboxTarget.hidden = false;
        this.inputTarget.setAttribute('aria-expanded', 'true');
        this.update(true);
        this.place();
        window.addEventListener('scroll', this.onScroll, { capture: true, passive: true });
        window.addEventListener('resize', this.onResize);
        // The virtual keyboard of a phone opens or the page zooms: the part of the page seen changes.
        window.visualViewport?.addEventListener('resize', this.onResize);
        window.visualViewport?.addEventListener('scroll', this.onResize);
    }

    close() {
        window.clearTimeout(this.debounce);
        window.clearTimeout(this.announce);
        this.request?.abort();
        this.pending = false;
        this.loading = false;
        this.loaded = null;
        this.isOpen = false;
        this.listboxTarget.hidden = true;
        this.listboxTarget.removeAttribute('aria-busy');
        this.inputTarget.setAttribute('aria-expanded', 'false');
        this.activate(-1);
        this.unfollow();
    }

    // Under the field, as wide as it.
    place() {
        if (!this.isOpen) {
            return;
        }
        const field = this.inputTarget.closest('.crudit-ui-combobox__field') ?? this.inputTarget;
        this.listboxTarget.style.width = `${field.getBoundingClientRect().width}px`;
        placeFloating(field, this.listboxTarget, { align: 'start' });
    }

    follow() {
        cancelAnimationFrame(this.frame);
        this.frame = requestAnimationFrame(() => this.place());
    }

    unfollow() {
        cancelAnimationFrame(this.frame);
        window.removeEventListener('scroll', this.onScroll, { capture: true });
        window.removeEventListener('resize', this.onResize);
        window.visualViewport?.removeEventListener('resize', this.onResize);
        window.visualViewport?.removeEventListener('scroll', this.onResize);
        resetPosition(this.listboxTarget);
        this.listboxTarget.style.width = '';
    }

    search() {
        if (!this.isOpen) {
            this.open();
            return;
        }
        this.update(false);
    }

    // The choices of the text in the input: at once in the page, after a pause from the server.
    update(now) {
        const query = this.inputTarget.value.trim();
        if (!this.urlValue) {
            this.filter(query);
            return;
        }
        window.clearTimeout(this.debounce);
        if (Array.from(query).length < this.minLengthValue) {
            this.request?.abort();
            this.pending = false;
            this.loaded = null;
            this.show([], query, false);
            return;
        }
        if (query === this.loaded) {
            // Back to the text whose choices are shown: the request of another text is cancelled, its "Loading" too.
            if (this.loading) {
                this.request?.abort();
                this.loading = false;
                this.listboxTarget.removeAttribute('aria-busy');
                this.message(this.choices.length === 0 ? this.emptyTextValue : null);
            }
            this.pending = false;
            return;
        }
        this.pending = true;
        if (now) {
            this.load(query, 0);
        } else {
            this.debounce = window.setTimeout(() => this.load(query, 0), DEBOUNCE);
        }
    }

    filter(query) {
        const words = fold(query);
        const choices = words.length === 0 ? this.all : this.all.filter((choice) => find(choice.folded, words) >= 0);
        this.show(choices.slice(0, SHOWN), query, false);
        if (choices.length > SHOWN) {
            this.message(this.moreTextValue.replace('%shown%', () => String(SHOWN)).replace('%count%', () => String(choices.length)));
        }
        this.announceCount(choices.length);
    }

    async load(query, offset) {
        this.request?.abort();
        const request = new AbortController();
        this.request = request;
        this.loading = true;
        this.listboxTarget.setAttribute('aria-busy', 'true');
        if (offset === 0) {
            this.message(this.loadingTextValue);
        }

        try {
            const url = new URL(this.urlValue, window.location.href);
            url.searchParams.set('q', query);
            url.searchParams.set('offset', String(offset));
            url.searchParams.set('limit', String(PAGE));
            const response = await fetch(url, { headers: { Accept: 'application/json' }, signal: request.signal, credentials: 'same-origin' });
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            const data = await response.json();
            if (request.signal.aborted) {
                return;
            }
            const items = (Array.isArray(data.items) ? data.items : [])
                .filter((item) => item && item.id !== undefined && item.id !== null)
                .map((item) => ({ value: String(item.id), label: String(item.text ?? item.id) }));
            const next = data.next_offset;
            const total = data.total_count;
            this.nextOffset = typeof next === 'number' && next > offset && items.length > 0 && (typeof total !== 'number' || next < total) ? next : null;
            this.loaded = query;
            this.show(items, query, offset > 0);
            if (offset === 0) {
                this.announceCount(typeof total === 'number' ? total : items.length);
            }
        } catch {
            if (request.signal.aborted) {
                return;
            }
            this.loaded = null;
            this.nextOffset = null;
            this.message(this.errorTextValue);
            this.announceText(this.errorTextValue, 0);
        } finally {
            if (this.request === request) {
                this.loading = false;
                this.listboxTarget.removeAttribute('aria-busy');
                // An answer for an older text (typed since, or a page of the list): the text in the input still waits.
                if (query === this.inputTarget.value.trim()) {
                    this.pending = false;
                }
            }
        }
    }

    // The end of the list is near: the next choices of the server.
    scroll() {
        const list = this.listboxTarget;
        if (this.nextOffset === null || this.loading || this.loaded === null) {
            return;
        }
        if (list.scrollTop + list.clientHeight >= list.scrollHeight - NEAR_END) {
            this.load(this.loaded, this.nextOffset);
        }
    }

    show(choices, query, append) {
        if (!append) {
            this.listboxTarget.replaceChildren();
            this.choices = [];
            this.activeIndex = -1;
        }
        this.listboxTarget.querySelector('[data-message]')?.remove();
        const words = fold(query);

        for (const choice of choices) {
            this.listboxTarget.append(this.option(choice, words, this.choices.length));
            this.choices.push(choice);
        }
        if (this.choices.length === 0 && (!this.urlValue || Array.from(query).length >= this.minLengthValue)) {
            this.message(this.emptyTextValue);
        }
        if (!append) {
            this.activate(this.choices.length > 0 ? 0 : -1);
            this.listboxTarget.scrollTop = 0;
        }
        this.place();
    }

    option(choice, words, index) {
        const option = document.createElement('li');
        option.id = `${this.inputTarget.id}_option_${index}`;
        option.className = 'crudit-ui-combobox__option';
        option.setAttribute('role', 'option');
        option.setAttribute('aria-selected', this.isSelected(choice.value) ? 'true' : 'false');
        // The option takes no focus: the input keeps it.
        option.addEventListener('mousedown', (event) => event.preventDefault());
        option.addEventListener('click', () => this.toggle(index));
        option.addEventListener('mousemove', () => this.activate(index));

        const check = document.createElement('span');
        check.className = 'crudit-ui-combobox__check';
        check.setAttribute('aria-hidden', 'true');
        const icon = this.icon('crudit-ui-combobox__check-icon');
        if (icon) {
            check.append(icon);
        }
        const label = document.createElement('span');
        label.className = 'crudit-ui-combobox__label';
        label.append(...marked(choice.label, words));
        option.append(check, label);

        return option;
    }

    message(text) {
        this.listboxTarget.querySelector('[data-message]')?.remove();
        if (!text) {
            return;
        }
        const item = document.createElement('li');
        item.className = 'crudit-ui-combobox__message';
        item.setAttribute('role', 'presentation');
        item.dataset.message = '';
        item.textContent = text;
        this.listboxTarget.append(item);
        this.place();
    }

    keydown(event) {
        if (event.isComposing) {
            return;
        }
        const count = this.choices.length;

        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            if (!this.isOpen) {
                this.open();
                return;
            }
            if (count) {
                const step = event.key === 'ArrowDown' ? 1 : -1;
                this.activate(((this.activeIndex < 0 && step < 0 ? 0 : this.activeIndex) + step + count) % count, true);
            }
        } else if (event.key === 'Enter' && this.isOpen && (this.pending || this.choices[this.activeIndex])) {
            // The choices are those of an older text: nothing is chosen until the ones of the text in the input are there.
            event.preventDefault();
            if (!this.pending) {
                this.toggle(this.activeIndex);
            }
        } else if (event.key === 'Escape' && this.isOpen) {
            // The list only: a dialog around the field stays open.
            event.preventDefault();
            event.stopPropagation();
            this.close();
        } else if (event.key === 'Backspace' && this.inputTarget.value === '' && this.selected.length > 0) {
            this.unselect(this.selected[this.selected.length - 1].value);
        }
    }

    activate(index, scroll = false) {
        const options = this.listboxTarget.querySelectorAll('[role="option"]');
        options[this.activeIndex]?.classList.remove('crudit-ui-combobox__option--active');
        this.activeIndex = options[index] ? index : -1;
        const active = options[this.activeIndex];

        if (active) {
            active.classList.add('crudit-ui-combobox__option--active');
            this.inputTarget.setAttribute('aria-activedescendant', active.id);
            if (scroll) {
                active.scrollIntoView({ block: 'nearest' });
            }
        } else {
            this.inputTarget.removeAttribute('aria-activedescendant');
        }
    }

    toggle(index) {
        const choice = this.choices[index];
        if (!choice) {
            return;
        }
        if (this.isSelected(choice.value)) {
            if (this.multipleValue) {
                this.unselect(choice.value);
            } else {
                this.inputTarget.value = '';
                this.close();
            }
            return;
        }
        this.selected = this.multipleValue ? [...this.selected, choice] : [choice];
        this.renderTokens();
        this.markSelected();
        this.sync();
        this.announceText(this.addedTextValue.replace('%label%', () => choice.label), 0);
        if (!this.multipleValue) {
            this.inputTarget.value = '';
            this.close();
        }
    }

    // The button of a token.
    remove(event) {
        const token = event.currentTarget.closest('[data-value]');
        if (token) {
            this.unselect(token.dataset.value);
        }
        this.inputTarget.focus();
    }

    unselect(value) {
        const choice = this.selected.find((candidate) => candidate.value === value);
        if (!choice) {
            return;
        }
        this.selected = this.selected.filter((candidate) => candidate !== choice);
        this.renderTokens();
        this.markSelected();
        this.sync();
        this.announceText(this.removedTextValue.replace('%label%', () => choice.label), 0);
    }

    isSelected(value) {
        return this.selected.some((choice) => choice.value === value);
    }

    markSelected() {
        this.listboxTarget.querySelectorAll('[role="option"]').forEach((option, index) => {
            option.setAttribute('aria-selected', this.choices[index] && this.isSelected(this.choices[index].value) ? 'true' : 'false');
        });
    }

    readTokens() {
        return Array.from(this.tokensTarget.querySelectorAll('[data-value]'), (token) => ({
            value: token.dataset.value,
            label: (token.querySelector('.crudit-ui-combobox__token-label')?.textContent ?? '').trim(),
        }));
    }

    renderTokens() {
        const disabled = this.inputTarget.disabled;
        this.tokensTarget.replaceChildren(...this.selected.map((choice) => {
            const token = document.createElement('li');
            token.className = 'crudit-ui-combobox__token';
            token.dataset.value = choice.value;
            const label = document.createElement('span');
            label.className = 'crudit-ui-combobox__token-label';
            label.textContent = choice.label;
            token.append(label);
            if (!disabled) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'crudit-ui-combobox__remove';
                button.dataset.action = `${this.identifier}#remove`;
                const icon = this.icon('crudit-ui-combobox__remove-icon');
                if (icon) {
                    button.append(icon);
                }
                const text = document.createElement('span');
                text.className = 'crudit-ui-visually-hidden';
                text.textContent = this.removeTextValue.replace('%label%', () => choice.label);
                button.append(text);
                token.append(button);
            }

            return token;
        }));
        this.tokensTarget.hidden = this.selected.length === 0;
        if (this.placeholder) {
            this.inputTarget.setAttribute('placeholder', this.selected.length > 0 ? '' : this.placeholder);
        }
    }

    // The choices made, in the hidden field that the form sends.
    sync(notify = true) {
        const field = this.valueTarget;
        const values = this.selected.map((choice) => choice.value);

        if (field instanceof HTMLSelectElement) {
            for (const choice of this.selected) {
                if (!Array.from(field.options).some((option) => option.value === choice.value)) {
                    field.append(new Option(choice.label, choice.value));
                }
            }
            for (const option of field.options) {
                option.selected = values.includes(option.value);
            }
            if (!this.multipleValue && values.length === 0) {
                field.value = '';
            }
        } else {
            field.value = values.join(this.separatorValue);
        }
        if (notify) {
            field.dispatchEvent(new Event('change', { bubbles: true }));
            this.dispatch('change', { detail: { values } });
        }
    }

    icon(className) {
        const icon = this.hasIconsTarget ? this.iconsTarget.content.querySelector(`.${className}`) : null;

        return icon ? icon.cloneNode(true) : null;
    }

    announceCount(count) {
        if (count === 0) {
            this.announceText(this.emptyTextValue, ANNOUNCE);
            return;
        }
        this.announceText((count === 1 ? this.countOneTextValue : this.countOtherTextValue).replace('%count%', () => String(count)), ANNOUNCE);
    }

    announceText(text, delay) {
        window.clearTimeout(this.announce);
        const say = () => {
            this.statusTarget.textContent = text;
        };
        if (delay > 0) {
            this.announce = window.setTimeout(say, delay);
        } else {
            // Emptied first: the same text twice in a row (two choices removed) is read again.
            this.statusTarget.textContent = '';
            this.announce = window.setTimeout(say, 50);
        }
    }
}
