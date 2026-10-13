import test from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import {compileString} from 'sass';

const css = readFileSync(new URL('../dist/ui.css', import.meta.url), 'utf8');
const selectors = [...css.matchAll(/([^{};]+)\{/g)].map(([, selector]) => selector.trim()).filter((selector) => !selector.startsWith('@'));
const compile = (scss) => compileString(scss, {loadPaths: ['styles'], style: 'compressed'}).css;

test('every class of the bundle has the crudit-ui- prefix', () => {
    const classes = new Set(selectors.flatMap((selector) => [...selector.matchAll(/\.(-?[a-z_][\w-]*)/gi)].map(([, name]) => name)));
    assert.ok(classes.size > 0);
    for (const name of classes) {
        assert.match(name, /^crudit-ui-/, `.${name} must take the crudit-ui- prefix`);
    }
});

test('the bundle styles no element by its id', () => {
    assert.deepEqual(selectors.filter((selector) => /#[a-z]/i.test(selector)), []);
});

// The content of a layer block of the compiled stylesheet.
function layer(name) {
    const start = css.indexOf(`@layer ${name}{`);
    assert.notEqual(start, -1, `no ${name} layer`);
    let depth = 0;
    for (let index = start; index < css.length; index++) {
        if (css[index] === '{') {
            depth++;
        } else if (css[index] === '}' && --depth === 0) {
            return css.slice(start + name.length + 8, index);
        }
    }
    throw new Error(`the ${name} layer is not closed`);
}

const selectorsOf = (block) => [...block.matchAll(/([^{};]+)\{/g)].map(([, selector]) => selector.trim()).filter((selector) => !selector.startsWith('@'));

test('the order of the layers is declared first: crudit-ui-base, then crudit-ui', () => {
    assert.ok(css.startsWith('@layer crudit-ui-base, crudit-ui;'));
});

test('crudit-ui-base styles the bare elements, never a class of the bundle', () => {
    const base = layer('crudit-ui-base');
    assert.ok(base.includes('[hidden]{display:none !important}'), 'the reset is missing');
    assert.ok(base.includes('body{min-height:100vh'), 'elements/base is missing');
    assert.deepEqual(selectorsOf(base).filter((selector) => selector.includes('.crudit-ui-')), []);
});

// A project that keeps Bootstrap puts it between the two layers: a rule on a bare element in crudit-ui would win
// over the classes of Bootstrap (`a { color }` over `.btn-primary`).
test('every rule of crudit-ui targets a class of the bundle', () => {
    const rules = selectorsOf(layer('crudit-ui')).filter((selector) => !/^(to|from|\d+%)$/.test(selector));
    assert.ok(rules.length > 0);
    for (const selector of rules) {
        for (const part of selector.split(',')) {
            assert.match(part, /\.crudit-ui-/, `"${part}" must target a class of the bundle, or go in crudit-ui-base`);
        }
    }
});

test('the a11y tools produce the focus ring and the visually hidden text', () => {
    const output = compile(`@use 'tools/a11y' as *; a { @include focus-ring; } b { @include sr-only; } i { @include transition(color, opacity); }`);
    assert.match(output, /a\{outline:var\(--focus-ring-width\) solid var\(--c-focus\);outline-offset:var\(--focus-ring-offset\)\}/);
    assert.match(output, /b\{position:absolute;width:1px;height:1px;.*clip:rect\(0, 0, 0, 0\)/);
    assert.match(output, /i\{transition-property:color,opacity;transition-duration:var\(--motion-base\)/);
});
