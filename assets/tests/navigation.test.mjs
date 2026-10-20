import test from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';

const css = readFileSync(new URL('../dist/ui.css', import.meta.url), 'utf8');

test('the chevron of a disclosure points down, and turns by 180° when open, in both directions of reading', () => {
    assert.ok(css.includes('.crudit-ui-disclosure__toggle[aria-expanded=true] .crudit-ui-disclosure__chevron{transform:rotate(180deg)}'));
    assert.doesNotMatch(css, /crudit-ui-disclosure[^{]*\{[^}]*--dir/);
});

test('a closed panel is hidden by its class, and printed open', () => {
    assert.ok(css.includes('.crudit-ui-disclosure__panel--closed{display:none}'));
    assert.match(css, /@media print\{\.crudit-ui-disclosure__panel--closed\{display:block\}/);
});

test('the chevron is not printed, whatever the order of the stylesheets', () => {
    // More specific than .crudit-ui-icon{display:inline-block}, loaded after.
    assert.match(css, /@media print\{[^@]*\.crudit-ui-disclosure__toggle \.crudit-ui-disclosure__chevron\{display:none\}/);
});

test('the tabs wrap on a phone, they never scroll', () => {
    assert.match(css, /\.crudit-ui-tabs__list\{display:flex;flex-wrap:wrap;/);
    assert.doesNotMatch(css, /\.crudit-ui-tabs__list\{[^}]*overflow/);
});

test('the selected tab has a line under it, not the colour alone', () => {
    assert.match(css, /\.crudit-ui-tabs__tab\[aria-selected=true\]::after\{[^}]*block-size:3px;[^}]*background-color:var\(--c-primary\);[^}]*content:""/);
});
