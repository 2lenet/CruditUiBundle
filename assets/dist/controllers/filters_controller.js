import { Controller } from '@hotwired/stimulus';
import { matchBreakpoint } from '../utils/breakpoints.js';

const SEARCH_DELAY = 400;
const FOCUS_MAX_AGE = 5000;
// A draft waits for the answer of the server, which may be slow.
const DRAFT_MAX_AGE = 30000;
const PERIOD = '[data-controller~="2lenet--crudit-ui-bundle--filter-period"]';
const DROPDOWN = '[data-controller~="2lenet--crudit-ui-bundle--dropdown"]';

/*
 * Filter:Bar: the GET form of the filters of a list.
 *
 * - The search and the main filters of the bar apply at once: the form is sent (after a short delay while one types)
 *   as soon as one of their fields changes. The dates of a period are sent when its dropdown closes (or at once by a
 *   ready-made period), not while the second one is still to be typed.
 * - What is in the panel is a draft: a filter of the panel changed since the page was drawn is left out of a send that
 *   does not come from the panel (search, bar, chip), so the server keeps its previous value; "Apply" (or Enter in a
 *   field of the panel) sends them all.
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
        this.leaving = false;
        this.periodWaiting = null;
        // Before Turbo caches the page the dialogs close by themselves: that is not a choice of the user. Capture: runs
        // before the close of the dropdowns and dialogs.
        this.onBeforeCache = () => {
            this.leaving = true;
            clearTimeout(this.timer);
        };
        document.addEventListener('turbo:before-cache', this.onBeforeCache, {capture: true});

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
        // After the controllers of the fields, which Stimulus connects after this one (the form comes first in the
        // document): the events of the restoration must reach their actions.
        this.draftTimer = setTimeout(() => this.restoreDraft(), 0);
    }

    disconnect() {
        this.leaving = true;
        clearTimeout(this.timer);
        clearTimeout(this.draftTimer);
        document.removeEventListener('turbo:before-cache', this.onBeforeCache, {capture: true});
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

    // Closed by the button of the dialog or Escape: a choice, remembered. Not when the page closes it (before the cache,
    // on disconnect), nor when the window crosses md (closed and shown again at once: the event comes after).
    panelClosed() {
        if (this.panelTarget.open) {
            return;
        }
        this.toggleTarget.setAttribute('aria-expanded', 'false');
        if (!this.leaving) {
            this.store('open', '0');
        }
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
    // The dates of a period wait for its dropdown to close (see closed()): "From" is typed before "To". A ready-made
    // period is complete, and sends at once.
    changed(event) {
        if (this.inPanel(event.target) || this.isTyped(event.target)) {
            return;
        }
        const period = event.target.closest?.(PERIOD);
        if (period && !event.detail?.chosen) {
            this.periodWaiting = period;
            return;
        }
        this.periodWaiting = null;
        this.schedule(0);
    }

    // The dropdown of a period of the bar closes after its dates were typed: sent.
    closed(event) {
        const period = this.periodWaiting;
        if (period && event.target.contains(period) && period.closest(DROPDOWN) === event.target && !this.leaving) {
            this.periodWaiting = null;
            this.schedule(0);
        }
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
            this.schedule(0, {apply: this.inPanel(event.target)});
        }
    }

    isTyped(element) {
        return element instanceof HTMLInputElement && ['text', 'search', 'number', 'email', 'tel', 'url'].includes(element.type);
    }

    schedule(delay, options = {}) {
        clearTimeout(this.timer);
        this.timer = setTimeout(() => this.send(options), delay);
    }

    // Sends the form. Without `apply`, the filters of the panel that changed since the page was drawn (the draft) are
    // disabled for the time of the send, so they are left out; `keep` is a filter that goes anyway (the one a chip
    // just emptied).
    //
    // The fields are disabled and enabled again in the same task: the browser and Turbo (FormSubmission) read the data of
    // the form synchronously, while the submit event runs. Do not move this into a promise or a timer, nor "simplify" it
    // to one that runs after requestSubmit(): a listener of the project that reads the form later sees them enabled.
    // The changes of the draft are kept for the page that replaces this one (see restoreDraft()).
    send({apply = false, keep = null} = {}) {
        clearTimeout(this.timer);
        const draft = apply ? [] : this.draftInputs(keep);
        if (draft.length) {
            this.saveDraft(draft);
        }
        draft.forEach((input) => {
            input.disabled = true;
        });
        try {
            this.element.requestSubmit(this.hasApplyTarget ? this.applyTarget : undefined);
        } finally {
            draft.forEach((input) => {
                input.disabled = false;
            });
        }
    }

    // The fields (all the inputs of a filter, its condition too: the server then keeps the filter as it was) of the
    // filters of the panel that have been changed.
    draftInputs(keep) {
        if (!this.hasPanelTarget) {
            return [];
        }
        const inputs = [];
        this.panelTarget.querySelectorAll('[data-filter-id]').forEach((field) => {
            if (field === keep) {
                return;
            }
            const own = Array.from(field.querySelectorAll('input, select, textarea')).filter((input) => !input.disabled);
            if (own.some((input) => this.modified(input))) {
                inputs.push(...own);
            }
        });

        return inputs;
    }

    // The changes left out of a send would vanish with the page: they are kept once for the next one, which puts them
    // back in the panel (still a draft: the server has not seen them).
    saveDraft(inputs) {
        const changes = inputs.filter((input) => input.name && this.modified(input)).map((input) => ({
            name: input.name,
            value: input.value,
            type: input instanceof HTMLSelectElement ? 'select' : input.type,
            checked: input.checked,
            values: input instanceof HTMLSelectElement ? Array.from(input.selectedOptions).map((option) => option.value) : null,
        }));
        this.store('draft', JSON.stringify({time: Date.now(), changes}));
    }

    restoreDraft() {
        let saved = null;
        try {
            saved = JSON.parse(this.stored('draft') ?? 'null');
        } catch {
            saved = null;
        }
        this.store('draft', null);
        if (!saved || !this.element.isConnected || Date.now() - saved.time > DRAFT_MAX_AGE) {
            return;
        }
        saved.changes.forEach(({name, value, type, checked, values}) => {
            // In the whole form: a main filter that was in the panel (narrow window) may be back in the bar.
            const inputs = Array.from(this.element.querySelectorAll('input, select, textarea')).filter((input) => input.name === name);
            const input = type === 'checkbox' || type === 'radio' ? inputs.find((candidate) => candidate.value === value) : inputs[0];
            if (!input) {
                return;
            }
            if (type === 'checkbox') {
                input.checked = checked;
            } else if (type === 'radio') {
                // The one that became checked is enough: checking it unchecks the others.
                if (!checked) {
                    return;
                }
                input.checked = true;
            } else if (type === 'select') {
                Array.from(input.options).forEach((option) => {
                    option.selected = values.includes(option.value);
                });
            } else {
                input.value = value;
            }
            // The scripts of the fields (condition, summary of a period) follow, as for a change by the user.
            input.dispatchEvent(new Event('input', {bubbles: true}));
        });
    }

    modified(input) {
        if (input instanceof HTMLSelectElement) {
            return Array.from(input.options).some((option) => option.selected !== option.defaultSelected);
        }
        if (input.type === 'checkbox' || input.type === 'radio') {
            return input.checked !== input.defaultChecked;
        }

        return input.value !== input.defaultValue;
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
        field.querySelectorAll('input, select, textarea').forEach((input) => {
            if (input instanceof HTMLSelectElement) {
                Array.from(input.options).forEach((option) => {
                    option.selected = false;
                });
                if (!input.multiple) {
                    input.value = '';
                }
            } else if (input.type === 'checkbox') {
                input.checked = false;
            } else if (input.type === 'radio') {
                // The conditions keep theirs (see below); the boolean goes back to "All".
                if (input.name.endsWith('_value')) {
                    input.checked = input.value === 'all';
                }
            } else if (input.type !== 'hidden') {
                input.value = '';
            }
            input.disabled = false;
        });
        // An "is empty" condition would go on filtering with its values emptied: the first condition that needs a value.
        const conditions = Array.from(field.querySelectorAll('input[type="radio"][name$="_op"]'));
        if (conditions.find((radio) => radio.checked)?.dataset.noValue === 'true') {
            const other = conditions.find((radio) => radio.dataset.noValue !== 'true');
            if (other) {
                other.checked = true;
            }
        }
        this.send({keep: field});
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
