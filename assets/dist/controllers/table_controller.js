import { Controller } from '@hotwired/stimulus';

/*
 * Table (component Table): what the page needs of a table, which the CSS cannot do.
 *
 * - Sticky header. position: sticky is not enough: the container that scrolls horizontally becomes the reference of the
 *   sticking, not the page. The CSS shifts the header row with an animation linked to the scroll of the page (timeline
 *   --crudit-ui-table-wrap), computed by the browser in the same frame: no shaking. Without that CSS function (Firefox)
 *   the header scrolls with the table: a script would follow the page one frame late and shake. Only the heights come
 *   from here, measured again when a size changes: --crudit-ui-table-start (scroll where the top of the table reaches
 *   the header of the page: the first `header` element, or the selector of the value headerSelector) and
 *   --crudit-ui-table-max (the largest shift, not to go past the last row).
 * - Second scrollbar above the table, shown when it overflows, kept in step with the one below. The scroll that the
 *   script gives to the other bar (the echo) is recognised by its position and ignored: Firefox scrolls asynchronously
 *   and would give the position back, rounded, to the bar the user is moving.
 * - A click on a row goes to its address (data-url), unless it hits a link, a button, a field or a text selection;
 *   Ctrl or Cmd opens it in a new tab. By keyboard, the link of the main cell does the job.
 * - Groups: the button of a group folds its rows (data-group-body) and its subtotals.
 * - Selection: the box of the page, indeterminate when some rows are selected; the row and its card (same data-id)
 *   stay in step. Dispatches "select" ({ids}) when it changes.
 */
export default class extends Controller {
    static targets = ['wrap', 'table', 'topBar', 'spacer', 'selectAll', 'selectAllCards', 'rowCheck'];
    static values = {headerSelector: {type: String, default: 'header'}};

    connect() {
        this.echo = null;
        this.onResize = () => this.measure();
        this.observer = new ResizeObserver(this.onResize);
        [this.hasWrapTarget && this.wrapTarget, this.hasTableTarget && this.tableTarget]
            .filter(Boolean)
            .forEach(element => this.observer.observe(element));
        window.addEventListener('resize', this.onResize);
        this.measure();
        this.refreshSelection();
    }

    disconnect() {
        this.observer?.disconnect();
        window.removeEventListener('resize', this.onResize);
    }

    measure() {
        if (!this.hasWrapTarget || !this.hasTableTarget) {
            return;
        }

        const wrap = this.wrapTarget;
        const table = this.tableTarget;
        const overflowing = wrap.scrollWidth > wrap.clientWidth;
        if (this.hasTopBarTarget) {
            this.topBarTarget.hidden = !overflowing;
            this.spacerTarget.style.width = `${wrap.scrollWidth}px`;
        }

        const head = table.tHead;
        if (head) {
            // Exact heights (offsetHeight rounds: 59 px for 58.75). The header of the table goes 1 px under the one
            // of the page, otherwise the rows show between the two.
            const top = document.querySelector(this.headerSelectorValue)?.getBoundingClientRect().height ?? 0;
            const start = document.documentElement.clientHeight - top + wrap.clientTop + 1;
            wrap.style.setProperty('--crudit-ui-table-start', `${start}px`);
            wrap.style.setProperty('--crudit-ui-table-max', `${Math.max(table.offsetHeight - head.offsetHeight, 0)}px`);
        }
    }

    syncFromWrap() {
        this.sync(this.wrapTarget, this.hasTopBarTarget ? this.topBarTarget : null);
    }

    syncFromTop() {
        this.sync(this.topBarTarget, this.wrapTarget);
    }

    sync(source, target) {
        if (!target) {
            return;
        }
        if (this.echo?.element === source && Math.abs(source.scrollLeft - this.echo.value) < 1) {
            return;
        }
        if (Math.abs(target.scrollLeft - source.scrollLeft) >= 1) {
            target.scrollLeft = source.scrollLeft;
            this.echo = {element: target, value: target.scrollLeft};
        }
    }

    rowClick(event) {
        if (event.target.closest('a, button, input, label, select, textarea, [role="menu"], .crudit-ui-dropdown__panel, .crudit-ui-tooltip')) {
            return;
        }
        if (window.getSelection()?.toString()) {
            return;
        }

        const url = event.currentTarget.dataset.url;
        if (event.ctrlKey || event.metaKey) {
            window.open(url, '_blank', 'noopener');
        } else if (window.Turbo) {
            window.Turbo.visit(url);
        } else {
            window.location.assign(url);
        }
    }

    toggleGroup(event) {
        const button = event.currentTarget;
        const open = button.getAttribute('aria-expanded') !== 'true';
        button.setAttribute('aria-expanded', String(open));
        button.closest('tbody').querySelectorAll('[data-group-body]').forEach(row => {
            row.hidden = !open;
        });
    }

    toggleRow(event) {
        this.setRow(event.currentTarget.dataset.id, event.currentTarget.checked);
        this.refreshSelection();
        this.dispatchSelection();
    }

    togglePage(event) {
        const checked = event.currentTarget.checked;
        this.rowCheckTargets.forEach(box => this.setRow(box.dataset.id, checked));
        this.refreshSelection();
        this.dispatchSelection();
    }

    setRow(id, checked) {
        this.rowCheckTargets.filter(box => box.dataset.id === id).forEach(box => {
            box.checked = checked;
            box.closest('tr')?.classList.toggle('crudit-ui-table__row--selected', checked);
            box.closest('li')?.classList.toggle('crudit-ui-table__card--selected', checked);
        });
    }

    refreshSelection() {
        const ids = this.selectedIds();
        const all = new Set(this.rowCheckTargets.map(box => box.dataset.id));
        const state = {checked: all.size > 0 && ids.length === all.size, indeterminate: ids.length > 0 && ids.length < all.size};
        [...(this.hasSelectAllTarget ? [this.selectAllTarget] : []), ...(this.hasSelectAllCardsTarget ? [this.selectAllCardsTarget] : [])]
            .forEach(box => Object.assign(box, state));
    }

    selectedIds() {
        return [...new Set(this.rowCheckTargets.filter(box => box.checked).map(box => box.dataset.id))];
    }

    dispatchSelection() {
        this.dispatch('select', {detail: {ids: this.selectedIds()}});
    }
}
