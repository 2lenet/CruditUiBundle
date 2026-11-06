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
