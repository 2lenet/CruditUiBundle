import test from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';

const css = readFileSync(new URL('../dist/ui.css', import.meta.url), 'utf8');

test('below 768 px, the breadcrumb is only the link "← parent": the arrow instead of the separator', () => {
    assert.match(css, /@media\(max-width: 767\.98px\)\{[^@]*\.crudit-ui-breadcrumb__item\{display:none\}/);
    assert.ok(css.includes('.crudit-ui-breadcrumb__item{display:none}.crudit-ui-breadcrumb__item--back{display:inline-flex}.crudit-ui-breadcrumb__item--back .crudit-ui-breadcrumb__separator{display:none}.crudit-ui-breadcrumb__item--back .crudit-ui-breadcrumb__back{display:inline-flex;'));
    assert.ok(css.includes('.crudit-ui-breadcrumb__back{display:none}'));
});

test('the crumbs wrap, they never scroll nor are cut', () => {
    assert.match(css, /\.crudit-ui-breadcrumb__list\{display:flex;flex-wrap:wrap;/);
    assert.doesNotMatch(css, /\.crudit-ui-breadcrumb[^{]*\{[^}]*(overflow|text-overflow)/);
});

test('without a level to go back to, the whole breadcrumb is hidden below 768 px', () => {
    assert.ok(css.includes('@media(max-width: 767.98px){.crudit-ui-breadcrumb--no-back{display:none}'));
});

test('the zones of the page header left empty are hidden, even with spaces in a block', () => {
    assert.ok(css.includes('.crudit-ui-page-header__top:not(:has(>*)),.crudit-ui-page-header__actions:not(:has(>*)),.crudit-ui-page-header__meta:empty{display:none}'));
});

test('below 576 px, the actions go under the title', () => {
    assert.ok(css.includes('@media(max-width: 575.98px){.crudit-ui-page-header__actions{width:100%}}'));
});

test('the title focused when the page changes has no ring: it is not a control', () => {
    assert.ok(css.includes('h1[tabindex="-1"]:focus{outline:none}'));
});
