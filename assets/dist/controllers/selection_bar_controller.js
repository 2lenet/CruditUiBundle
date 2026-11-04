import { Controller } from '@hotwired/stimulus';

/*
 * SelectionBar (component SelectionBar): the bar of the actions on the rows checked in a Table.
 *
 * - Listens to the event "select" of the table (detail: {ids, count, pageSelected, all, exitedAll}), shows the bar when
 *   a row is checked and says in a live region how many (the screen reader hears "3 rows selected", then "Selection
 *   cleared."). It keeps no state of its own: the Table owns the selection, the mode "all the results" and what the
 *   pager carries from one page to the other.
 * - When the whole page is checked and the list has more results (value total), offers to select them all: the Table
 *   checks every row, the actions receive all_page=1. Unchecking a row leaves the mode, and says so ("The selection
 *   of all the results ended: 5 rows still selected").
 * - Gives the selection to the actions:
 *   - a link marked data-action="…selection-bar#fill" (or data-selection-link in a dialog) always has ids and all_page
 *     in its address, kept up to date as the selection changes (a middle click, "open in a new tab" and "copy the
 *     link" work). A long selection makes a long address: send it by a form (POST) instead;
 *   - a form marked the same way on its submit event fills the fields data-selection-field="ids" and "all_page";
 *   - a button that opens a dialog (commandfor) marked data-action="…selection-bar#fillDialog" feeds the dialog when
 *     it opens: the fields, links and forms it contains marked data-selection-field / data-selection-link, and
 *     data-ids and data-all-page on the dialog for a script of the project (event crudit-ui:selection).
 * - clear() unchecks the rows (the Table puts the focus back on its box of the page).
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
        allEndedOne: String,
        allEndedMany: String,
        cleared: String,
    };

    connect() {
        this.ids = [];
        this.pageCount = 0;
        this.pageSelected = 0;
        this.all = false;
        this.exitedAll = false;
        this.render();
    }

    update(event) {
        if (event.detail?.table && event.detail.table !== this.tableValue) {
            return;
        }
        this.ids = event.detail?.ids ?? [];
        this.pageCount = event.detail?.count ?? 0;
        this.pageSelected = event.detail?.pageSelected ?? this.ids.length;
        this.all = event.detail?.all === true;
        this.exitedAll = event.detail?.exitedAll === true;
        this.render();
    }

    selectAll() {
        this.tableController?.selectAll();
        this.clearButtonTarget.focus();
    }

    clear() {
        this.tableController?.clearSelection();
    }

    get tableController() {
        const table = document.getElementById(this.tableValue);

        return table && this.application.getControllerForElementAndIdentifier(table, '2lenet--crudit-ui-bundle--table');
    }

    get allPage() {
        return this.all ? '1' : '0';
    }

    fillDialog(event) {
        const dialog = document.getElementById(event.currentTarget.getAttribute('commandfor'));
        if (!dialog) {
            return;
        }
        dialog.dataset.ids = this.ids.join(',');
        dialog.dataset.allPage = this.allPage;
        this.fillFields(dialog);
        this.syncLinks(dialog);
        dialog.dispatchEvent(new CustomEvent('crudit-ui:selection', {bubbles: true, detail: {ids: [...this.ids], allPage: this.all}}));
    }

    // The click on a link, or the submit of a form
    fill(event) {
        const target = event.currentTarget;
        if (target.matches('a[href]')) {
            this.syncLink(target);

            return;
        }
        this.fillFields(target);
    }

    fillFields(root) {
        root.querySelectorAll('[data-selection-field]').forEach(field => {
            if (field.dataset.selectionField === 'ids') {
                field.value = this.ids.join(',');
            } else if (field.dataset.selectionField === 'all_page') {
                field.value = this.allPage;
            }
        });
    }

    // The links of the actions carry the selection in their address as soon as it changes
    syncLinks(root) {
        root.querySelectorAll('a[href][data-selection-link], a[href][data-action]')
            .forEach(link => {
                // data-action ends with "selection-bar#fill" (not #fillDialog)
                if (link.hasAttribute('data-selection-link') || link.dataset.action.split(/\s+/).some(action => action.endsWith('selection-bar#fill'))) {
                    this.syncLink(link);
                }
            });
    }

    syncLink(link) {
        const url = new URL(link.href, window.location.href);
        url.searchParams.set('ids', this.ids.join(','));
        url.searchParams.set('all_page', this.allPage);
        link.href = url.toString();
    }

    render() {
        const has = this.ids.length > 0;
        this.barTarget.hidden = !has;
        this.syncLinks(this.element);
        if (!has) {
            this.announce(this.hadSelection ? this.clearedValue : '');
            this.hadSelection = false;

            return;
        }
        this.hadSelection = true;
        const total = String(this.totalValue);
        const text = this.all
            ? this.allSelectedValue
            : (this.ids.length === 1 ? this.oneValue : this.manyValue).replace('%count%', String(this.ids.length));
        this.countTarget.textContent = text.replace('%total%', total);
        const offer = !this.all && this.pageCount > 0 && this.pageSelected === this.pageCount && this.totalValue > this.pageCount;
        this.selectAllTarget.hidden = !offer;
        this.selectAllTarget.textContent = this.selectAllLabelValue.replace('%total%', total);
        this.announce(this.exitedAll
            ? (this.ids.length === 1 ? this.allEndedOneValue : this.allEndedManyValue).replace('%count%', String(this.ids.length))
            : text.replace('%total%', total));
    }

    announce(message) {
        this.statusTarget.textContent = message;
    }
}
