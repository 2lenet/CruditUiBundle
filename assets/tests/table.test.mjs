import test from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';

const css = readFileSync(new URL('../dist/ui.css', import.meta.url), 'utf8');

// The cells of the table are aligned at the start by a rule at the specificity of a class, written before the
// alignment of a column: `.crudit-ui-table__table td` would win over `.crudit-ui-table__cell--end` (the amounts).
test('the alignment of a column wins over the one of the cells', () => {
    assert.ok(css.includes('.crudit-ui-table__table :where(th),.crudit-ui-table__table :where(td){text-align:start}'), 'the start of the cells is not in :where()');
    assert.doesNotMatch(css, /\.crudit-ui-table__table (th|td)[,{][^}]*text-align/, 'a rule on th or td of the table sets text-align');
    const start = css.indexOf(':where(td){text-align:start}');
    assert.ok(start < css.indexOf('.crudit-ui-table__cell--end{'), 'the start of the cells comes after the end of a column');
    assert.ok(start < css.indexOf('.crudit-ui-table__heading--end{'), 'the start of the headings comes after the end of a column');
});
