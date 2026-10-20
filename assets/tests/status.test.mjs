import test from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';

const css = readFileSync(new URL('../dist/ui.css', import.meta.url), 'utf8');
const hues = [...readFileSync(new URL('../styles/settings/_hues.scss', import.meta.url), 'utf8')
    .split('$hues-dark')[0].matchAll(/^\s+'([a-z]+)': \(/gm)].map(([, name]) => name);
const tones = ['neutral', 'info', 'primary', 'success', 'warning', 'danger'];

test('every tone and every hue sets the two variables of a tone, read by the badge and the status', () => {
    assert.equal(hues.length, 11);
    for (const name of [...tones.filter((tone) => tone !== 'primary'), ...hues]) {
        assert.ok(css.includes(`.crudit-ui-tone--${name}{--crudit-ui-tone-bg: var(--c-${name}-bg);--crudit-ui-tone-text: var(--c-${name}-text)}`), name);
    }
    assert.ok(css.includes('.crudit-ui-tone--primary{--crudit-ui-tone-bg: var(--c-primary-subtle);--crudit-ui-tone-text: var(--c-primary-text)}'));
    assert.match(css, /\.crudit-ui-badge\{[^}]*background-color:var\(--crudit-ui-tone-bg, var\(--c-neutral-bg\)\);color:var\(--crudit-ui-tone-text, var\(--c-neutral-text\)\)/);
    assert.match(css, /\.crudit-ui-status\{[^}]*color:var\(--crudit-ui-tone-text, var\(--c-neutral-text\)\)/);
    // No colour per component: a colour of the project needs one rule only.
    assert.doesNotMatch(css, /\.crudit-ui-(badge|status)--(blue|danger)\{/);
});

test('the progress bar is styled in each engine, its colour from its tone', () => {
    for (const pseudo of ['::-webkit-progress-bar', '::-webkit-progress-value', '::-moz-progress-bar']) {
        assert.ok(css.includes(`.crudit-ui-progress__track${pseudo}{`), pseudo);
    }
    assert.ok(css.includes('.crudit-ui-progress--danger{--crudit-ui-progress-bar: var(--c-danger-solid)}'));
});

test('the alert has the four tones of a message', () => {
    for (const tone of ['info', 'success', 'warning', 'danger']) {
        assert.ok(css.includes(`.crudit-ui-alert--${tone}{border-color:var(--c-${tone}-border);background-color:var(--c-${tone}-bg);color:var(--c-${tone}-text)}`), tone);
    }
});

test('the skeleton is animated only when motion is not reduced', () => {
    assert.match(css, /@media\(prefers-reduced-motion: no-preference\)\{\.crudit-ui-skeleton\{animation:crudit-ui-skeleton-pulse/);
    assert.doesNotMatch(css.replace(/@media\(prefers-reduced-motion: no-preference\)\{\.crudit-ui-skeleton\{[^}]*\}\}/, ''), /animation:crudit-ui-skeleton-pulse/);
});

test('an empty zone of actions takes no room', () => {
    assert.ok(css.includes('.crudit-ui-alert__actions:empty{display:none}'));
    assert.ok(css.includes('.crudit-ui-empty-state__actions:empty{display:none}'));
});
