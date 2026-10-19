import test from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';

const css = readFileSync(new URL('../dist/ui.css', import.meta.url), 'utf8');
const breakpoints = readFileSync(new URL('../styles/settings/_breakpoints.scss', import.meta.url), 'utf8');
const widths = Object.fromEntries([...breakpoints.matchAll(/^\s+(\w+): (\w+),$/gm)].map(([, name, value]) => [name, value]));

test('the grid has 12 columns, full width by default, and spans from every breakpoint', () => {
    assert.match(css, /\.crudit-ui-grid\{display:grid;grid-template-columns:repeat\(12, minmax\(0, 1fr\)\)/);
    assert.match(css, /\.crudit-ui-grid>\*\{grid-column:1\/-1/);
    for (let span = 1; span <= 12; span++) {
        assert.ok(css.includes(`.crudit-ui-grid>.crudit-ui-col-${span}{grid-column:span ${span}}`), `crudit-ui-col-${span}`);
    }
    for (const [name, width] of Object.entries(widths).filter(([, width]) => width !== '0')) {
        assert.match(css, new RegExp(`@media\\(min-width: ${width}\\)\\{\\.crudit-ui-grid>\\.crudit-ui-col-${name}-1\\{`), `crudit-ui-col-${name}-*`);
    }
});

test('the spacing utilities follow the tokens, in logical properties', () => {
    assert.ok(css.includes('.crudit-ui-m-0{margin:0}'));
    assert.ok(css.includes('.crudit-ui-mt-2{margin-block-start:var(--space-2)}'));
    assert.ok(css.includes('.crudit-ui-ps-4{padding-inline-start:var(--space-4)}'));
    assert.ok(css.includes('.crudit-ui-me-auto{margin-inline-end:auto}'));
    assert.ok(css.includes('.crudit-ui-py-8{padding-block:var(--space-8)}'));
    assert.ok(css.includes('.crudit-ui-gap-3{gap:var(--space-3)}'));
    assert.doesNotMatch(css, /margin-(left|right)|padding-(left|right)/, 'physical properties break the RTL');
});

test('a display utility of a breakpoint comes after the base one, and the utilities after the components', () => {
    const none = css.indexOf('.crudit-ui-d-none{display:none}');
    const mdFlex = css.indexOf('.crudit-ui-d-md-flex{display:flex}');
    assert.ok(none !== -1 && mdFlex > none);
    assert.ok(css.indexOf('.crudit-ui-dropdown') < none);
});

test('the visually hidden utility and the print rules are there', () => {
    assert.match(css, /\.crudit-ui-visually-hidden\{position:absolute;width:1px/);
    assert.match(css, /@media print\{\.crudit-ui-print-hidden\{display:none\}/);
});

test('the forced focus of the design kit draws the ring of :focus-visible', () => {
    assert.match(css, /\.crudit-ui-is-focus\{outline:var\(--focus-ring-width\) solid var\(--c-focus\);outline-offset:var\(--focus-ring-offset\)\}/);
});
