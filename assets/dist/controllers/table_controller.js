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
 *   stay in step. The rows already checked by the server (`selected`) are part of it. The selection follows the pager
 *   and nothing else: a click on a link that only changes the page (same address, same filters and sort) writes, in
 *   one go, {scope, ids, all, ts} in sessionStorage; the next page reads it once (if it is less than 15 s old and of the
 *   same list) and checks the rows again. A sort, a filter, a reload, a return to the list or an action start from an
 *   empty selection (acting on rows the user no longer sees is dangerous).
 *   Mode "all the results" (selectAll(), asked by the SelectionBar): every row of the page is checked, also on the
 *   next pages; unchecking one leaves the mode (the event says it: exitedAll) and only the rows checked are left.
 *   Dispatches "select" ({table, ids, count, pageSelected, all, exitedAll}: the id of the table, the ids checked on the
 *   pages visited with the pager, the number of rows of the page, how many of them are checked, the mode, the mode just
 *   left) when it changes, and once when the controller connects (the bar may be outside the element, in another
 *   Turbo Frame). clearSelection() forgets everything and puts the focus back on the box of the page.
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
        const header = document.querySelector(this.headerSelectorValue);
        if (header) {
            this.observer.observe(header);
        }
        window.addEventListener('resize', this.onResize);
        this.onLeave = event => this.keepForPager(event);
        document.addEventListener('click', this.onLeave);
        this.measure();
        this.restoreSelection();
        this.refreshSelection();
        // After the connection of the SelectionBar (after the table in the page), which listens to the event
        setTimeout(() => this.dispatchSelection());
    }

    disconnect() {
        this.observer?.disconnect();
        window.removeEventListener('resize', this.onResize);
        document.removeEventListener('click', this.onLeave);
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
        const wasAll = this.all;
        this.setRow(event.currentTarget.dataset.id, event.currentTarget.checked);
        if (wasAll && !event.currentTarget.checked) {
            this.all = false;
        }
        this.commitSelection(wasAll && !this.all);
    }

    togglePage(event) {
        const checked = event.currentTarget.checked;
        const wasAll = this.all;
        this.rowCheckTargets.forEach(box => this.setRow(box.dataset.id, checked));
        if (wasAll && !checked) {
            this.all = false;
        }
        this.commitSelection(wasAll && !this.all);
    }

    // All the results (the SelectionBar offers it when the whole page is checked)
    selectAll() {
        this.all = true;
        this.rowCheckTargets.forEach(box => this.setRow(box.dataset.id, true));
        this.commitSelection(false);
    }

    setRow(id, checked) {
        this.rowCheckTargets.filter(box => box.dataset.id === id).forEach(box => {
            box.checked = checked;
            box.closest('tr')?.classList.toggle('crudit-ui-table__row--selected', checked);
            box.closest('li')?.classList.toggle('crudit-ui-table__card--selected', checked);
        });
        if (checked) {
            this.selection.add(id);
        } else {
            this.selection.delete(id);
        }
    }

    commitSelection(exitedAll) {
        this.refreshSelection();
        this.dispatchSelection(exitedAll);
    }

    refreshSelection() {
        const all = this.pageIds();
        const ids = all.filter(id => this.selection.has(id));
        const state = {checked: all.length > 0 && ids.length === all.length, indeterminate: ids.length > 0 && ids.length < all.length};
        [...(this.hasSelectAllTarget ? [this.selectAllTarget] : []), ...(this.hasSelectAllCardsTarget ? [this.selectAllCardsTarget] : [])]
            .forEach(box => Object.assign(box, state));
    }

    pageIds() {
        return [...new Set(this.rowCheckTargets.map(box => box.dataset.id))];
    }

    dispatchSelection(exitedAll = false) {
        const pageIds = this.pageIds();
        this.dispatch('select', {detail: {
            table: this.element.id,
            ids: [...this.selection],
            count: pageIds.length,
            pageSelected: pageIds.filter(id => this.selection.has(id)).length,
            all: this.all,
            exitedAll,
        }});
    }

    clearSelection() {
        this.all = false;
        this.selection.clear();
        this.rowCheckTargets.forEach(box => this.setRow(box.dataset.id, false));
        this.commitSelection(false);
        // The button that was pressed goes away with the bar: the focus goes to the box of the page
        [this.hasSelectAllTarget && this.selectAllTarget, this.hasSelectAllCardsTarget && this.selectAllCardsTarget]
            .find(box => box && box.offsetParent !== null)?.focus();
    }

    get storageKey() {
        return `crudit-ui:selection:${this.element.id}`;
    }

    // The address without the page (`page`, or `<list>_offset` in Crudit), the parameters in a fixed order: the same
    // list, the same filters and sort
    scopeOf(url) {
        const query = new URLSearchParams(url.search);
        this.pageParams(url).forEach(([name]) => query.delete(name));
        query.sort();

        return `${url.pathname}?${query.toString()}`;
    }

    pageParams(url) {
        return [...url.searchParams.entries()].filter(([name]) => /^page$|_offset$/.test(name));
    }

    // A click on a link that only changes the page keeps the selection for the next one (read once by restoreSelection)
    keepForPager(event) {
        const link = event.target.closest?.('a[href]');
        if (!link || this.selection.size === 0 || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || link.target === '_blank') {
            return;
        }
        const url = new URL(link.href, window.location.href);
        const current = new URL(window.location.href);
        const sameList = url.origin === current.origin && this.scopeOf(url) === this.scopeOf(current)
            && JSON.stringify(this.pageParams(url)) !== JSON.stringify(this.pageParams(current));
        if (!sameList) {
            return;
        }
        try {
            window.sessionStorage.setItem(this.storageKey, JSON.stringify({scope: this.scopeOf(current), ids: [...this.selection], all: this.all, ts: Date.now()}));
        } catch (e) {
            // No storage: the selection ends with the page
        }
    }

    restoreSelection() {
        // The rows the server checked (`selected`) count, whatever else is restored
        this.selection = new Set(this.rowCheckTargets.filter(box => box.defaultChecked).map(box => box.dataset.id));
        this.all = false;
        // Turbo shows the cached copy of a page first (a preview), and connects the controllers on it: the real page,
        // which comes next, reads the entry
        if (document.documentElement.hasAttribute('data-turbo-preview')) {
            this.rowCheckTargets.forEach(box => this.setRow(box.dataset.id, box.checked));

            return;
        }
        try {
            const stored = JSON.parse(window.sessionStorage.getItem(this.storageKey) ?? 'null');
            window.sessionStorage.removeItem(this.storageKey);
            if (stored && Date.now() - stored.ts < 15000 && stored.scope === this.scopeOf(new URL(window.location.href))) {
                stored.ids.forEach(id => this.selection.add(String(id)));
                this.all = stored.all === true;
            }
        } catch (e) {
            // No storage (private window, blocked)
        }
        if (this.all) {
            this.pageIds().forEach(id => this.selection.add(id));
        }
        this.rowCheckTargets.forEach(box => {
            const checked = this.selection.has(box.dataset.id);
            box.checked = checked;
            box.closest('tr')?.classList.toggle('crudit-ui-table__row--selected', checked);
            box.closest('li')?.classList.toggle('crudit-ui-table__card--selected', checked);
        });
    }
}
