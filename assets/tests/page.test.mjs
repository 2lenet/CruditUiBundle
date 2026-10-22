import test from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';

const css = readFileSync(new URL('../dist/ui.css', import.meta.url), 'utf8');

// The rules of a media query (all its blocks: Sass may split them), to check each rule apart, in any order
const media = (query) => [...css.matchAll(new RegExp(`@media\\(${query}\\)\\{((?:[^{}]*\\{[^}]*\\})*)\\}`, 'g'))].map((m) => m[1]).join('');
const rule = (selector, declarations) => new RegExp(`(^|[}])${selector.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}\\{[^}]*${declarations}`);
const phone = media('max-width: 767\\.98px');
const small = media('max-width: 575\\.98px');

test('below 768 px, the breadcrumb is only the link "← parent": the arrow instead of the separator', () => {
    assert.match(phone, rule('.crudit-ui-breadcrumb__item', 'display:none'));
    assert.match(phone, rule('.crudit-ui-breadcrumb__item--back', 'display:inline-flex'));
    assert.match(phone, rule('.crudit-ui-breadcrumb__item--back .crudit-ui-breadcrumb__separator', 'display:none'));
    assert.match(phone, rule('.crudit-ui-breadcrumb__item--back .crudit-ui-breadcrumb__back', 'display:inline-flex'));
    assert.match(css, rule('.crudit-ui-breadcrumb__back', 'display:none'));
});

test('the crumbs wrap, they never scroll nor are cut', () => {
    assert.match(css, rule('.crudit-ui-breadcrumb__list', 'flex-wrap:wrap'));
    assert.doesNotMatch(css, /\.crudit-ui-breadcrumb[^{]*\{[^}]*(overflow|text-overflow)/);
});

test('without a level to go back to, the whole breadcrumb is hidden below 768 px', () => {
    assert.match(phone, rule('.crudit-ui-breadcrumb--no-back', 'display:none'));
});

test('the zones of the page header without an element are hidden, even with spaces in a block', () => {
    assert.match(css, /\.crudit-ui-page-header__top:not\(:has\(>\*\)\)[^{]*\{display:none\}/);
    assert.match(css, /\.crudit-ui-page-header__actions:not\(:has\(>\*\)\)[^{]*\{display:none\}/);
});

test('below 768 px, the zone of a breadcrumb hidden for lack of a level to go back to is hidden too', () => {
    assert.match(phone, rule('.crudit-ui-page-header__top:not(:has(>:not(.crudit-ui-breadcrumb--no-back)))', 'display:none'));
});

test('below 576 px, the actions go under the title', () => {
    assert.match(small, rule('.crudit-ui-page-header__actions', 'width:100%'));
});

test('the title of the page header has no ring when focused, other h1 keep theirs', () => {
    assert.match(css, rule('.crudit-ui-page-header__title:focus', 'outline:none'));
    assert.doesNotMatch(css, /h1\[tabindex/);
});

test('the title takes its letter spacing from the tokens', () => {
    assert.match(css, rule('.crudit-ui-page-header__title', 'letter-spacing:var\\(--letter-spacing-tight\\)'));
    assert.ok(css.includes('--letter-spacing-tight: -0.015em'));
});
