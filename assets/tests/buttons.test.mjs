import test from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';

const css = readFileSync(new URL('../dist/ui.css', import.meta.url), 'utf8');

// The hover of a button that is not disabled, and the forced hover of the design kit.
const hover = (block) => `${block}:hover:not(:disabled):not([aria-disabled=true]),${block}.crudit-ui-is-hover`;

test('every variant of the button has its hover, also forced for the design kit, never on a disabled button', () => {
    for (const variant of ['primary', 'secondary', 'ghost', 'warning', 'danger', 'danger-outline', 'link']) {
        assert.ok(css.includes(`${hover(`.crudit-ui-btn--${variant}`)}{`), variant);
    }
    assert.ok(css.includes(`${hover('.crudit-ui-icon-btn')},.crudit-ui-icon-btn[aria-expanded=true]{`));
    assert.doesNotMatch(css, /\.crudit-ui-(icon-)?btn(--[a-z-]+)?:hover[,{]/, 'a hover that a disabled button would take');
});

test('a link stays a link in the small size', () => {
    assert.ok(css.includes('.crudit-ui-btn--link.crudit-ui-btn--small{min-height:auto;padding:0}'));
});

test('a button disabled with aria-disabled looks disabled, and a loading one shows it', () => {
    assert.match(css, /\.crudit-ui-btn:disabled,\.crudit-ui-btn\[aria-disabled=["']?true["']?\],\.crudit-ui-icon-btn:disabled,\.crudit-ui-icon-btn\[aria-disabled=["']?true["']?\]\{cursor:not-allowed;opacity:\.5\}/);
    assert.match(css, /\.crudit-ui-btn\[aria-busy=["']?true["']?\],\.crudit-ui-icon-btn\[aria-busy=["']?true["']?\]\{cursor:progress\}/);
});

test('the sizes follow the height of the controls, larger on touch screens', () => {
    assert.match(css, /\.crudit-ui-btn\{display:inline-flex;min-height:var\(--control-h\)/);
    assert.match(css, /\.crudit-ui-btn--small\{min-height:var\(--control-h-small\)/);
    assert.match(css, /\.crudit-ui-icon-btn--small\{width:var\(--control-h-small\);height:var\(--control-h-small\)\}/);
});
