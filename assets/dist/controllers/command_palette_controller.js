import { Controller } from '@hotwired/stimulus';

const DEBOUNCE = 200;
// The count is announced once the typing has stopped: not at every key.
const ANNOUNCE = 600;

// "Café" and "cafe" are the same word: the letters without their accents, in lower case, one for one (the position of
// a match in the normalized text is the position in the text).
function fold(text) {
    return Array.from(text, (character) => Array.from(character.normalize('NFD').replace(/\p{M}/gu, '').toLowerCase())[0] ?? character);
}

// Index of the first place where `words` (folded characters) appear in `folded`, -1 without.
function find(folded, words) {
    for (let start = 0; start + words.length <= folded.length; start++) {
        if (words.every((character, offset) => folded[start + offset] === character)) {
            return start;
        }
    }

    return -1;
}

/*
 * Command palette (component CommandPalette): the field of a <dialog> asks `url` for results by groups
 * ({"groups": [{"id", "label", "results": [{"id", "title", "subtitle", "url"}]}]}), shows them in a listbox of the
 * ARIA combobox pattern (the focus stays in the field, aria-activedescendant gives the active option) and opens the one
 * that is chosen. The dialog is the one of the controller dialog (focus trapped, Escape, backdrop, focus given back).
 *
 * - One request at a time: the one still running is cancelled by a newer one, and its answer is never shown. A request
 *   starts 200 ms after the last key, from `minLength` characters.
 * - The text of the results is always set as text, never as HTML. The words typed are marked in the title (<mark>).
 * - Nothing opens before it is chosen: Enter on the active option, or a click. Up and down arrows (and Home, End)
 *   change the active option, from the last to the first and back.
 * - Ctrl K and Cmd K (value shortcut) open the dialog from any page, unless another dialog is open. The browser's own
 *   Ctrl K (the address bar of Firefox) is replaced.
 * - The count of results is announced in a live region, after 600 ms; an empty answer and an error are shown and
 *   announced. The field is emptied when the dialog closes (and before Turbo caches the page).
 * - The address is opened by Turbo Drive when the page has it, else by the browser.
 *
 * Events, on the dialog: "select" (result, cancelable: the project opens it itself).
 */
export default class extends Controller {
    static targets = ['input', 'results', 'message', 'help', 'status'];
    static values = {
        url: String,
        minLength: { type: Number, default: 2 },
        shortcut: { type: Boolean, default: true },
        emptyText: String,
        errorText: String,
        countOneText: String,
        countOtherText: String,
        loadingText: String,
    };

    connect() {
        this.options = [];
        this.activeIndex = -1;
        this.last = null;
        this.id = this.element.id;

        this.onShortcut = (event) => {
            if (!this.shortcutValue || event.defaultPrevented || event.altKey || event.shiftKey) {
                return;
            }
            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
                event.preventDefault();
                this.open();
            }
        };
        this.onClose = () => this.reset();
        this.onOpen = () => this.inputTarget.focus();

        document.addEventListener('keydown', this.onShortcut);
        this.element.addEventListener('close', this.onClose);
        this.element.addEventListener('2lenet--crudit-ui-bundle--dialog:open', this.onOpen);
    }

    disconnect() {
        document.removeEventListener('keydown', this.onShortcut);
        this.element.removeEventListener('close', this.onClose);
        this.element.removeEventListener('2lenet--crudit-ui-bundle--dialog:open', this.onOpen);
        window.clearTimeout(this.debounce);
        window.clearTimeout(this.announce);
        this.request?.abort();
    }

    open() {
        // Another dialog of the page (a form, a confirmation) is on top: a palette over it would be lost.
        if (this.element.open || document.querySelector('dialog:modal')) {
            return;
        }
        this.element.showModal();
        this.inputTarget.focus();
    }

    search() {
        window.clearTimeout(this.debounce);
        const query = this.inputTarget.value.trim();

        if (Array.from(query).length < this.minLengthValue) {
            this.request?.abort();
            this.clear();
            return;
        }
        this.debounce = window.setTimeout(() => this.load(query), DEBOUNCE);
    }

    async load(query) {
        if (query === this.last && this.options.length > 0) {
            return;
        }
        this.request?.abort();
        const request = new AbortController();
        this.request = request;
        this.element.setAttribute('aria-busy', 'true');

        try {
            const url = new URL(this.urlValue, window.location.href);
            url.searchParams.set('q', query);
            const response = await fetch(url, { headers: { Accept: 'application/json' }, signal: request.signal, credentials: 'same-origin' });
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            const data = await response.json();
            if (request.signal.aborted) {
                return;
            }
            this.last = query;
            this.show(Array.isArray(data.groups) ? data.groups : [], query);
        } catch (error) {
            if (request.signal.aborted) {
                return;
            }
            this.last = null;
            this.showMessage(this.errorTextValue, 'alert');
            this.announceText(this.errorTextValue, 0);
        } finally {
            if (this.request === request) {
                this.element.removeAttribute('aria-busy');
            }
        }
    }

    show(groups, query) {
        this.resultsTarget.replaceChildren();
        this.options = [];
        const words = fold(query);

        for (const group of groups) {
            const results = Array.isArray(group.results) ? group.results.filter((result) => result && result.url) : [];
            if (results.length === 0) {
                continue;
            }
            const groupId = `${this.id}-group-${group.id}`;
            const section = document.createElement('div');
            section.className = 'crudit-ui-palette__group';
            section.setAttribute('role', 'group');
            section.setAttribute('aria-labelledby', groupId);

            const title = document.createElement('p');
            title.id = groupId;
            title.className = 'crudit-ui-palette__group-title';
            title.setAttribute('role', 'presentation');
            title.textContent = group.label ?? '';
            section.append(title);

            for (const result of results) {
                const option = this.option(result, words);
                option.id = `${this.id}-option-${this.options.length}`;
                section.append(option);
                this.options.push({ element: option, result });
            }
            this.resultsTarget.append(section);
        }

        const count = this.options.length;
        this.resultsTarget.hidden = count === 0;
        this.inputTarget.setAttribute('aria-expanded', count > 0 ? 'true' : 'false');
        this.helpTarget.hidden = true;

        if (count === 0) {
            const text = this.emptyTextValue.replace('%query%', query);
            this.showMessage(text, null);
            this.announceText(text, ANNOUNCE);
            this.activate(-1);
        } else {
            this.messageTarget.hidden = true;
            this.activate(0);
            this.announceText((count === 1 ? this.countOneTextValue : this.countOtherTextValue).replace('%count%', String(count)), ANNOUNCE);
        }
    }

    option(result, words) {
        const option = document.createElement('div');
        option.className = 'crudit-ui-palette__option';
        option.setAttribute('role', 'option');
        option.setAttribute('aria-selected', 'false');
        option.addEventListener('click', () => this.choose(result));
        option.addEventListener('mousemove', () => this.activate(this.options.findIndex((candidate) => candidate.element === option)));

        const title = document.createElement('span');
        title.className = 'crudit-ui-palette__title';
        title.append(...this.marked(String(result.title ?? ''), words));
        option.append(title);

        if (result.subtitle) {
            const subtitle = document.createElement('span');
            subtitle.className = 'crudit-ui-palette__subtitle';
            subtitle.textContent = String(result.subtitle);
            option.append(subtitle);
        }

        return option;
    }

    // The text with the words typed in <mark>: nodes, never a string of HTML.
    marked(text, words) {
        const folded = fold(text);
        const characters = Array.from(text);
        const start = words.length > 0 ? find(folded, words) : -1;
        if (start < 0) {
            return [document.createTextNode(text)];
        }
        const mark = document.createElement('mark');
        mark.textContent = characters.slice(start, start + words.length).join('');

        return [
            document.createTextNode(characters.slice(0, start).join('')),
            mark,
            document.createTextNode(characters.slice(start + words.length).join('')),
        ];
    }

    showMessage(text, role) {
        this.resultsTarget.replaceChildren();
        this.resultsTarget.hidden = true;
        this.options = [];
        this.activate(-1);
        this.inputTarget.setAttribute('aria-expanded', 'false');
        this.helpTarget.hidden = true;
        this.messageTarget.textContent = text;
        if (role) {
            this.messageTarget.setAttribute('role', role);
        } else {
            this.messageTarget.removeAttribute('role');
        }
        this.messageTarget.hidden = false;
    }

    // Back to the first state: the help, no result.
    clear() {
        window.clearTimeout(this.announce);
        this.last = null;
        this.options = [];
        this.resultsTarget.replaceChildren();
        this.resultsTarget.hidden = true;
        this.messageTarget.hidden = true;
        this.messageTarget.removeAttribute('role');
        this.helpTarget.hidden = false;
        this.statusTarget.textContent = '';
        this.inputTarget.setAttribute('aria-expanded', 'false');
        this.activate(-1);
    }

    reset() {
        window.clearTimeout(this.debounce);
        this.request?.abort();
        this.element.removeAttribute('aria-busy');
        this.inputTarget.value = '';
        this.clear();
    }

    announceText(text, delay) {
        window.clearTimeout(this.announce);
        this.announce = window.setTimeout(() => {
            this.statusTarget.textContent = text;
        }, delay);
    }

    keydown(event) {
        if (event.isComposing) {
            return;
        }
        const count = this.options.length;

        if (event.key === 'ArrowDown' && count) {
            event.preventDefault();
            this.activate((this.activeIndex + 1) % count, true);
        } else if (event.key === 'ArrowUp' && count) {
            event.preventDefault();
            this.activate((this.activeIndex - 1 + count) % count, true);
        } else if (event.key === 'Home' && count && event.ctrlKey) {
            event.preventDefault();
            this.activate(0, true);
        } else if (event.key === 'End' && count && event.ctrlKey) {
            event.preventDefault();
            this.activate(count - 1, true);
        } else if (event.key === 'Enter' && this.options[this.activeIndex]) {
            event.preventDefault();
            this.choose(this.options[this.activeIndex].result);
        }
    }

    activate(index, scroll = false) {
        if (index === this.activeIndex) {
            return;
        }
        this.options[this.activeIndex]?.element.setAttribute('aria-selected', 'false');
        this.activeIndex = index;
        const active = this.options[index]?.element;

        if (active) {
            active.setAttribute('aria-selected', 'true');
            this.inputTarget.setAttribute('aria-activedescendant', active.id);
            if (scroll) {
                active.scrollIntoView({ block: 'nearest' });
            }
        } else {
            this.activeIndex = -1;
            this.inputTarget.removeAttribute('aria-activedescendant');
        }
    }

    choose(result) {
        const event = this.dispatch('select', { detail: { result }, cancelable: true });
        if (event.defaultPrevented) {
            return;
        }
        this.element.close();
        if (window.Turbo && typeof window.Turbo.visit === 'function') {
            window.Turbo.visit(result.url);
        } else {
            window.location.assign(result.url);
        }
    }
}
