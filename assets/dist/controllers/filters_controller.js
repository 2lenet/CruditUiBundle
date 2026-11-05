import { Controller } from '@hotwired/stimulus';
import { matchBreakpoint } from '../utils/breakpoints.js';

const SEARCH_DELAY = 400;
const FOCUS_MAX_AGE = 5000;

/*
 * Filter:Bar: the GET form of the filters of a list.
 *
 * - The search and the main filters of the bar apply at once: the form is sent (after a short delay while one types)
 *   as soon as one of their fields changes. What is in the panel is a draft, sent by "Apply" (the same form: the fields
 *   of the panel go with it).
 * - The button "Filters" (target "toggle") shows the panel (target "panel", a Dialog): in the page from md, kept open
 *   after "Apply" (remembered for the tab); a side panel, modal, below md. aria-expanded follows.
 * - Below lg, the main filters (target "quick", each with a "slot" in its dropdown) move into the panel (target
 *   "grid"), and go back above: the same fields, so nothing is duplicated and nothing is lost.
 * - A chip removes its filter (action "clear", param "id"): the fields of that filter are emptied and the form sent.
 *   "<name>_op" is always sent (Filter:Field), so the server knows the filter was in the form and empty.
 * - Enter in a text field sends the form. The focus of the search is given back after the page is replaced.
 *
 * Event: "submit" is the native one of the form; the controller sends it with requestSubmit() (Turbo sees it).
 */
export default class extends Controller {
    static targets = ['search', 'toggle', 'panel', 'grid', 'apply', 'quick', 'slot'];

    static values = {id: String};

    connect() {
        this.timer = null;
        this.moved = new Map();

        this.wide = matchBreakpoint('>md');
        this.large = matchBreakpoint('>lg');
        this.onWide = () => this.resizePanel();
        this.onLarge = () => this.placeQuick();
        this.wide.addEventListener('change', this.onWide);
        this.large.addEventListener('change', this.onLarge);
        this.placeQuick();

        if (this.hasPanelTarget) {
            this.onPanelClose = () => this.panelClosed();
            this.panelTarget.addEventListener('close', this.onPanelClose);
            if (this.wide.matches && this.stored('open') === '1') {
                this.openPanel();
            }
        }
        this.restoreFocus();
    }

    disconnect() {
        clearTimeout(this.timer);
        this.wide?.removeEventListener('change', this.onWide);
        this.large?.removeEventListener('change', this.onLarge);
        if (this.hasPanelTarget) {
            this.panelTarget.removeEventListener('close', this.onPanelClose);
        }
        this.putQuickBack();
    }

    // --- Panel -----------------------------------------------------------------------------------------------

    // What the user chooses is remembered (open in the page from md), not what the page does by itself (a close when
    // the page unloads, or is cached by Turbo): the panel is then back as it was left.
    toggle() {
        if (this.panelTarget.open) {
            this.panelTarget.close();
            this.store('open', '0');
        } else {
            this.openPanel();
            this.store('open', this.wide.matches ? '1' : '0');
        }
    }

    openPanel() {
        if (this.panelTarget.open) {
            return;
        }
        // In the page from md (a dialog that is not modal), a side panel below.
        if (this.wide.matches) {
            this.panelTarget.show();
        } else {
            this.panelTarget.showModal();
        }
        this.toggleTarget.setAttribute('aria-expanded', 'true');
    }

    panelClosed() {
        this.toggleTarget.setAttribute('aria-expanded', 'false');
    }

    // The window crosses md with the panel open: shown again the way that suits the new width.
    resizePanel() {
        if (!this.hasPanelTarget || !this.panelTarget.open) {
            return;
        }
        this.panelTarget.close();
        this.openPanel();
    }

    // --- Main filters: in the bar above lg, in the panel below ---------------------------------------------------

    placeQuick() {
        if (!this.hasGridTarget) {
            return;
        }
        if (this.large.matches) {
            this.putQuickBack();
        } else {
            this.quickTargets.forEach((quick, index) => {
                const slot = quick.querySelector('[data-2lenet--crudit-ui-bundle--filters-target="slot"]');
                const field = slot?.firstElementChild;
                if (!field || this.moved.has(field)) {
                    return;
                }
                this.moved.set(field, slot);
                // First in the panel, in the order of the bar.
                this.gridTarget.insertBefore(field, this.gridTarget.children[index] ?? null);
            });
        }
    }

    putQuickBack() {
        this.moved.forEach((slot, field) => slot.appendChild(field));
        this.moved.clear();
    }

    // --- Sending ------------------------------------------------------------------------------------------------

    // A field of the bar changed (not one in the panel, which is a draft, nor the search, which waits for the delay).
    changed(event) {
        if (this.inPanel(event.target) || this.isTyped(event.target)) {
            return;
        }
        this.schedule(0);
    }

    // Typing in a field of the bar (the search, the text or number of a main filter) sends after a pause. The boxes,
    // the radios and the dates send when they change.
    typed(event) {
        if (!this.inPanel(event.target) && this.isTyped(event.target)) {
            this.schedule(SEARCH_DELAY);
        }
    }

    // Enter in a field sends the form at once, whether the field is in the bar or in the panel (the browser would
    // choose the first submit button of the form, which is hidden in a closed panel).
    keydown(event) {
        if (event.key === 'Enter' && this.isTyped(event.target)) {
            event.preventDefault();
            this.schedule(0);
        }
    }

    isTyped(element) {
        return element instanceof HTMLInputElement && ['text', 'search', 'number', 'email', 'tel', 'url'].includes(element.type);
    }

    schedule(delay) {
        clearTimeout(this.timer);
        this.timer = setTimeout(() => this.send(), delay);
    }

    send() {
        clearTimeout(this.timer);
        this.element.requestSubmit(this.hasApplyTarget ? this.applyTarget : undefined);
    }

    // The search keeps its focus (and its caret) when the page comes back.
    submitted() {
        const active = document.activeElement;
        if (active && active === this.searchField()) {
            this.store('focus', JSON.stringify({time: Date.now(), name: active.name, caret: active.selectionStart}));
        }
    }

    restoreFocus() {
        let saved = null;
        try {
            saved = JSON.parse(this.stored('focus') ?? 'null');
        } catch {
            saved = null;
        }
        this.store('focus', null);
        const field = this.searchField();
        if (saved && field && saved.name === field.name && Date.now() - saved.time < FOCUS_MAX_AGE) {
            field.focus({preventScroll: true});
            const caret = typeof saved.caret === 'number' ? saved.caret : field.value.length;
            field.setSelectionRange(caret, caret);
        }
    }

    // --- Chips ----------------------------------------------------------------------------------------------

    clear(event) {
        const field = this.element.querySelector(`[data-filter-id="${CSS.escape(event.params.id)}"]`);
        if (!field) {
            return;
        }
        field.querySelectorAll('input').forEach((input) => {
            if (input.type === 'checkbox') {
                input.checked = false;
            } else if (input.type === 'radio') {
                // The conditions keep theirs; the boolean goes back to "All".
                if (input.name.endsWith('_value')) {
                    input.checked = input.value === 'all';
                }
            } else if (input.type !== 'hidden') {
                input.value = '';
            }
            input.disabled = false;
        });
        this.send();
    }

    // --- Helpers ----------------------------------------------------------------------------------------------

    searchField() {
        return this.hasSearchTarget ? this.searchTarget : null;
    }

    inPanel(element) {
        return this.hasPanelTarget && this.panelTarget.contains(element);
    }

    stored(key) {
        try {
            return sessionStorage.getItem(`crudit-ui:filters:${this.idValue}:${key}`);
        } catch {
            return null;
        }
    }

    store(key, value) {
        try {
            const name = `crudit-ui:filters:${this.idValue}:${key}`;
            value === null ? sessionStorage.removeItem(name) : sessionStorage.setItem(name, value);
        } catch {
            // Storage refused: the panel and the focus are simply not remembered.
        }
    }
}
