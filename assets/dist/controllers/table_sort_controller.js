import { Controller } from '@hotwired/stimulus';

/*
 * Manual order of the rows of a Table (component Table with `sortable`). The handle of a row is a button: dragged with
 * the mouse (native drag and drop, armed by the press on the handle), moved with ↑ and ↓ from the keyboard, or by the
 * "Move up" and "Move down" buttons of the cards (touch). The new position is announced (target "status").
 * The order is sent as it is saved by CruditBundle: a POST of the JSON list of the ids, in the new order, to the value
 * url. When the server refuses or does not answer, the rows go back to their place and the error is announced.
 * With the value disabled (a filter is active: the order of a part of the list means nothing), nothing moves.
 * The texts are values passed by Twig (the controller has no catalogue): moved (%position%, %total%), failed,
 * refused (when disabled), position (the text of a card, %position%, %total%). A row moves inside its group (its
 * <tbody>), never into another one: the groups come from the data, not from the order. The order sent is the one of
 * the whole table.
 */
export default class extends Controller {
    static targets = ['status'];
    static values = {url: String, disabled: Boolean, moved: String, failed: String, refused: String, position: String};

    connect() {
        this.dragged = null;
        this.onDragStart = event => this.dragStart(event);
        this.onDragOver = event => this.dragOver(event);
        this.onDragEnd = () => this.dragEnd();
        this.onRelease = () => this.disarm();
        this.element.addEventListener('dragstart', this.onDragStart);
        this.element.addEventListener('dragover', this.onDragOver);
        this.element.addEventListener('dragend', this.onDragEnd);
        this.element.addEventListener('drop', this.onDragEnd);
        document.addEventListener('mouseup', this.onRelease);
    }

    disconnect() {
        this.element.removeEventListener('dragstart', this.onDragStart);
        this.element.removeEventListener('dragover', this.onDragOver);
        this.element.removeEventListener('dragend', this.onDragEnd);
        this.element.removeEventListener('drop', this.onDragEnd);
        document.removeEventListener('mouseup', this.onRelease);
    }

    arm(event) {
        if (this.disabledValue) {
            return;
        }
        event.currentTarget.closest('tr').draggable = true;
    }

    disarm() {
        this.element.querySelectorAll('tr[draggable="true"]').forEach(row => row.removeAttribute('draggable'));
    }

    dragStart(event) {
        const row = event.target.closest?.('tr[data-id]');
        if (!row || this.disabledValue) {
            return;
        }
        this.dragged = row;
        this.before = this.snapshot();
        row.classList.add('crudit-ui-table__row--dragging');
        event.dataTransfer.effectAllowed = 'move';
    }

    dragOver(event) {
        const target = event.target.closest?.('tr[data-id]');
        if (!this.dragged || !target || target === this.dragged || !this.sameRun(this.dragged, target)) {
            return;
        }
        event.preventDefault();
        const after = target.compareDocumentPosition(this.dragged) & Node.DOCUMENT_POSITION_PRECEDING;
        target.parentNode.insertBefore(this.dragged, after ? target.nextSibling : target);
    }

    dragEnd() {
        if (!this.dragged) {
            return;
        }
        const row = this.dragged;
        this.dragged = null;
        row.classList.remove('crudit-ui-table__row--dragging');
        this.disarm();
        if (this.order().join() !== this.before.order.join()) {
            this.save(this.before, row);
        }
    }

    key(event) {
        if (event.key !== 'ArrowUp' && event.key !== 'ArrowDown') {
            return;
        }
        event.preventDefault();
        const handle = event.currentTarget;
        this.move(handle.closest('tr'), event.key === 'ArrowUp' ? -1 : 1, () => handle.focus());
    }

    up(event) {
        this.moveCard(event.currentTarget, -1);
    }

    down(event) {
        this.moveCard(event.currentTarget, 1);
    }

    moveCard(button, delta) {
        const row = this.rows().find(candidate => candidate.dataset.id === button.dataset.id);
        if (row) {
            this.move(row, delta, () => (button.disabled ? button.closest('li').querySelector('.crudit-ui-table__card-order button:not(:disabled)') : button)?.focus());
        }
    }

    // Moves a row of the table; the cards follow in save(), which gives the focus back (moving a node blurs it).
    move(row, delta, refocus) {
        if (this.disabledValue || !row) {
            this.announce(this.refusedValue);
            return;
        }
        const target = delta < 0 ? row.previousElementSibling : row.nextElementSibling;
        if (!target?.matches('tr[data-id]')) {
            return;
        }

        const before = this.snapshot();
        row.parentNode.insertBefore(row, delta < 0 ? target : target.nextSibling);
        this.save(before, row, refocus);
    }

    // The cards follow the order of the rows: moved inside their list (one per group), with their position and their
    // buttons up to date.
    syncCards() {
        const ids = this.order();
        this.element.querySelectorAll('.crudit-ui-table__card-list').forEach(list => {
            const cards = [...list.children].sort((a, b) => ids.indexOf(a.querySelector('[data-id]')?.dataset.id) - ids.indexOf(b.querySelector('[data-id]')?.dataset.id));
            cards.forEach((card, index) => {
                list.appendChild(card);
                card.querySelector('.crudit-ui-table__card-position').textContent = this.positionValue.replace('%position%', index + 1).replace('%total%', cards.length);
                const [up, down] = card.querySelectorAll('.crudit-ui-table__card-order button');
                up.disabled = index === 0 || this.disabledValue;
                down.disabled = index === cards.length - 1 || this.disabledValue;
            });
        });
    }

    // Two rows of the same run: only rows between them, no heading of a group or of a subgroup.
    sameRun(a, b) {
        if (a.parentNode !== b.parentNode) {
            return false;
        }
        const [first, last] = a.compareDocumentPosition(b) & Node.DOCUMENT_POSITION_FOLLOWING ? [a, b] : [b, a];
        for (let node = first.nextElementSibling; node && node !== last; node = node.nextElementSibling) {
            if (!node.matches('tr[data-id]')) {
                return false;
            }
        }

        return true;
    }

    rows() {
        return [...this.element.querySelectorAll('tbody tr[data-id]')];
    }

    order() {
        return this.rows().map(row => row.dataset.id);
    }

    // The order, and the children of every body (the rows of a group are between its heading and its subtotals).
    snapshot() {
        return {
            order: this.order(),
            bodies: [...this.element.querySelectorAll('tbody')].map(body => [body, [...body.children]]),
        };
    }

    async save(before, row, refocus = null) {
        const ids = this.order();
        const total = ids.length;
        this.syncCards();
        refocus?.();
        try {
            const response = await fetch(this.urlValue, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(ids),
            });
            if (!response.ok) {
                throw new Error(String(response.status));
            }
            this.announce(this.movedValue.replace('%position%', ids.indexOf(row.dataset.id) + 1).replace('%total%', total));
            this.dispatch('sorted', {detail: {ids}});
        } catch (error) {
            // Restoring the rows blurs the focus: it comes back only if it was in the table (the user may have left).
            const focused = this.element.contains(document.activeElement);
            this.restore(before);
            this.syncCards();
            if (focused) {
                refocus?.();
            }
            this.announce(this.failedValue);
            this.dispatch('failed', {detail: {ids: before.order}});
        }
    }

    restore(before) {
        before.bodies.forEach(([body, children]) => children.forEach(child => body.appendChild(child)));
    }

    announce(text) {
        if (this.hasStatusTarget) {
            this.statusTarget.textContent = text;
        }
    }
}
