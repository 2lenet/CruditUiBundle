import test from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';

const css = readFileSync(new URL('../dist/ui.css', import.meta.url), 'utf8');

test('an input has a visible focus, also on the inner calendar button of a date', () => {
    assert.match(css, /\.crudit-ui-input:focus-visible,\.crudit-ui-select:focus-visible\{border-color:var\(--c-focus\);outline:var\(--focus-ring-width\) solid var\(--c-focus\)/);
    assert.ok(css.includes(".crudit-ui-input[type=date]:focus-within"));
});

test('the focus of an input with a unit surrounds the input and the unit', () => {
    assert.ok(css.includes('.crudit-ui-input-group:has(.crudit-ui-input:focus-visible){outline:var(--focus-ring-width) solid var(--c-focus);outline-offset:0}'));
    assert.ok(css.includes('.crudit-ui-input-group:has(.crudit-ui-input:focus-visible) .crudit-ui-input{outline:none}'));
});

test('a field in error is not told by the colour only: its border, and a message with an icon', () => {
    assert.match(css, /\.crudit-ui-field--invalid \.crudit-ui-input,[^{]*\{border-color:var\(--c-danger-solid\)\}/);
    assert.match(css, /\.crudit-ui-error\{display:flex;/);
});

test('the switch moves in the direction of the text, writes its state and is visible in forced colours', () => {
    assert.ok(css.includes('.crudit-ui-switch__input:checked::before{transform:translateX(calc(1.125rem * var(--dir)))}'));
    assert.ok(css.includes('.crudit-ui-switch__input:checked~.crudit-ui-switch__state--off,.crudit-ui-switch__input:not(:checked)~.crudit-ui-switch__state--on{display:none}'));
    assert.match(css, /@media\(forced-colors: active\)\{\.crudit-ui-switch__input\{border-color:CanvasText\}/);
});

test('the panel of a toggletip is placed under its button only where anchor positioning exists', () => {
    assert.match(css, /@supports\(position-area: block-end\)\{\.crudit-ui-toggletip__panel\{inset:auto;position-area:block-end span-inline-end/);
});

test('the button of a toggletip stays active while its panel is open (popovertarget sets no aria-expanded in the DOM)', () => {
    // In :is(), forgiving: a browser without :has() or :popover-open keeps the hover.
    assert.match(css, /\.crudit-ui-toggletip:is\(:hover, ?:has\(\+ ?\.crudit-ui-toggletip__panel:popover-open\)\)\{/);
    assert.ok(!css.includes('.crudit-ui-toggletip[aria-expanded'));
});

test('a segmented control keeps the native radio, draws the focus on its label and tells the checked one by more than the colour', () => {
    assert.ok(css.includes('grid-template-columns:repeat(auto-fit, minmax(min(100%, 6rem), 1fr))'), 'the choices of a segmented control do not go on the next line when the room is short');
    assert.match(css, /\.crudit-ui-segmented__input\{position:absolute;inset:0;margin:0;opacity:0;/);
    assert.ok(css.includes('.crudit-ui-segmented__input:focus-visible+.crudit-ui-segmented__label{outline:var(--focus-ring-width) solid var(--c-focus)'));
    assert.match(css, /\.crudit-ui-segmented__input:checked\+\.crudit-ui-segmented__label\{border-color:var\(--c-border-strong\);[^}]*box-shadow:var\(--shadow-sm\);[^}]*font-weight:var\(--font-weight-semibold\)\}/);
    assert.match(css, /@media\(forced-colors: active\)\{\.crudit-ui-segmented__input:checked\+\.crudit-ui-segmented__label\{border-color:Highlight\}/);
});

test('the counter of characters keeps its width while the digits change, the lists of a date are on one line', () => {
    assert.match(css, /\.crudit-ui-field__count\{[^}]*font-variant-numeric:tabular-nums\}/);
    assert.match(css, /\.crudit-ui-date-lists\{display:flex;flex-wrap:wrap;/);
    assert.match(css, /\.crudit-ui-date-lists>\.crudit-ui-input\{width:calc\(2ch \+ 2\*var\(--space-3\) \+ 2px\)/, 'the inputs of a date (widget text) take one line each');
    assert.match(css, /\.crudit-ui-date-lists>\.crudit-ui-input--year\{width:calc\(4ch /);
});
