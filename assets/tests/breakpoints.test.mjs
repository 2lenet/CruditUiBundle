import test from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import {compileString} from 'sass';

const sources = readFileSync(new URL('../styles/settings/_breakpoints.scss', import.meta.url), 'utf8');
const compile = (css) => compileString(css, {loadPaths: ['styles'], style: 'compressed'}).css;

test('the compiled stylesheet exposes every breakpoint as a CSS variable', () => {
    const css = readFileSync(new URL('../dist/ui.css', import.meta.url), 'utf8');
    for (const [, name, value] of sources.matchAll(/^\s+(\w+): (\w+),$/gm)) {
        assert.match(css, new RegExp(`--breakpoint-${name}: ${value}[;}]`));
    }
});

test('the media mixin uses non-overlapping ranges', () => {
    const css = compile(`@use 'tools/responsive' as *; @include media('<md') { a { b: c } } @include media('>md') { d { e: f } }`);
    assert.match(css, /@media ?\(max-width: ?767\.98px\)/);
    assert.match(css, /@media ?\(min-width: ?768px\)/);
});

test('the media mixin refuses an unknown breakpoint', () => {
    assert.throws(() => compile(`@use 'tools/responsive' as *; @include media('>huge') { a { b: c } }`), /not a breakpoint/);
});
