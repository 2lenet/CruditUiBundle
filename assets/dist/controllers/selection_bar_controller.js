import { Controller } from '@hotwired/stimulus';

/*
 * SelectionBar (component SelectionBar): the bar of the actions on the rows checked in a Table.
 *
 * - Listens to the event "select" of the table (detail: {ids, count}), shows the bar when a row is checked and
 *   says in a live region how many (the screen reader hears "3 rows selected", then "Selection cleared").
 * - When the whole page is checked and the list has more results (value total), offers to select them all: the scope
 *   becomes "all" and the actions receive all_page=1. Unchecking a row brings the scope back to the page.
 * - Gives the selection to the actions: fill() on the click of a link (adds ids and all_page to its address) or on
 *   the submit of a form (fills the fields marked data-selection-field="ids" or "all_page").
 * - Fills the dialogs of the actions too: the button that opens a dialog (attribute commandfor) marked
 *   data-action="click->...selection-bar#fillDialog" gives the selection to the dialog, to the fields, links and forms
 *   it contains marked data-selection-field="ids" / "all_page" (or data-selection-link), and sets data-ids and
 *   data-all-page on the dialog for a script of the project. A dialog with its own content (a custom template) is
 *   thus fed like a form.
 * - The selection follows the pager only (see the Table): the ids, and here the scope "all results". A sort, a filter,
 *   a reload or an action start again from an empty selection. clear() unchecks everything.
 */
export default class extends Controller {
    static targets = ['bar', 'status', 'count', 'selectAll', 'clearButton'];
    static values = {
        table: String,
        total: {type: Number, default: 0},
        one: String,
        many: String,
        selectAllLabel: String,
        allSelected: String,
        cleared: String,
    };

    connect() {
        this.ids = [];
        this.pageCount = 0;
        this.pageSelected = 0;
        this.mode = this.storedMode();
        this.render();
    }

    update(event) {
        if (event.detail?.table && event.detail.table !== this.tableValue) {
            return;
        }
        const ids = event.detail?.ids ?? [];
        // Rows unchecked or checked since: the scope "all results" no longer holds, unless it is the table restoring itself
        const restoring = this.ids.length === 0 && this.mode === 'all' && ids.length > 0;
        this.ids = ids;
        this.pageCount = event.detail?.count ?? 0;
        this.pageSelected = event.detail?.pageSelected ?? ids.length;
        if (!restoring) {
            this.mode = 'page';
        }
        this.render();
    }

    selectAll() {
        this.mode = 'all';
        this.render();
        this.clearButtonTarget.focus();
    }

    clear() {
        const table = document.getElementById(this.tableValue);
        const controller = table && this.application.getControllerForElementAndIdentifier(table, '2lenet--crudit-ui-bundle--table');
        controller?.clearSelection();
    }

    fillDialog(event) {
        const dialog = document.getElementById(event.currentTarget.getAttribute('commandfor'));
        if (!dialog) {
            return;
        }
        const allPage = this.mode === 'all' ? '1' : '0';
        dialog.dataset.ids = this.ids.join(',');
        dialog.dataset.allPage = allPage;
        dialog.querySelectorAll('[data-selection-field]').forEach(field => {
            field.value = field.dataset.selectionField === 'ids' ? this.ids.join(',') : allPage;
        });
        dialog.querySelectorAll('a[data-selection-link]').forEach(link => this.addToAddress(link, allPage));
        dialog.dispatchEvent(new CustomEvent('crudit-ui:selection', {bubbles: true, detail: {ids: [...this.ids], allPage: this.mode === 'all'}}));
    }

    addToAddress(link, allPage) {
        const url = new URL(link.href, window.location.href);
        url.searchParams.set('ids', this.ids.join(','));
        url.searchParams.set('all_page', allPage);
        link.href = url.toString();
    }

    fill(event) {
        const target = event.currentTarget;
        const allPage = this.mode === 'all' ? '1' : '0';
        if (target.matches('a[href]')) {
            this.addToAddress(target, allPage);

            return;
        }
        target.querySelectorAll('[data-selection-field]').forEach(field => {
            field.value = field.dataset.selectionField === 'ids' ? this.ids.join(',') : allPage;
        });
    }

    // The pager keeps the scope "all results" like the ids (the Table announces it): read once by the next page
    keepForPager(event) {
        if (event.detail?.table !== this.tableValue || this.mode !== 'all') {
            return;
        }
        try {
            window.sessionStorage.setItem(this.storageKey, JSON.stringify({scope: event.detail.scope}));
        } catch (e) {
            // no storage
        }
    }

    storageKey() {
        return `crudit-ui:selection-mode:${this.tableValue}`;
    }

    storedMode() {
        try {
            const stored = JSON.parse(window.sessionStorage.getItem(this.storageKey()) ?? 'null');
            window.sessionStorage.removeItem(this.storageKey());
            const query = new URLSearchParams(window.location.search);
            [...query.keys()].filter(name => /^page$|_offset$/.test(name)).forEach(name => query.delete(name));

            return stored?.scope === `${window.location.pathname}?${query.toString()}` ? 'all' : 'page';
        } catch (e) {
            return 'page';
        }
    }

    get count() {
        return this.mode === 'all' ? this.totalValue : this.ids.length;
    }

    render() {
        const has = this.ids.length > 0;
        this.barTarget.hidden = !has;
        document.documentElement.classList.toggle('has-selection-bar', has);
        if (!has) {
            this.announce(this.hadSelection ? this.clearedValue : '');
            this.hadSelection = false;

            return;
        }
        this.hadSelection = true;
        const text = this.mode === 'all'
            ? this.allSelectedValue
            : (this.ids.length === 1 ? this.oneValue : this.manyValue).replace('%count%', String(this.ids.length));
        this.countTarget.textContent = text.replace('%total%', String(this.totalValue));
        const offer = this.mode === 'page' && this.pageCount > 0 && this.pageSelected === this.pageCount && this.totalValue > this.pageCount;
        this.selectAllTarget.hidden = !offer;
        this.selectAllTarget.textContent = this.selectAllLabelValue.replace('%total%', String(this.totalValue));
        this.announce(text.replace('%total%', String(this.totalValue)));
    }

    announce(message) {
        this.statusTarget.textContent = message;
    }

    disconnect() {
        document.documentElement.classList.remove('has-selection-bar');
    }
}
