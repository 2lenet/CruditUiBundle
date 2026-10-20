import test from 'node:test';
import assert from 'node:assert/strict';
import {readdirSync, readFileSync} from 'node:fs';

const css = readFileSync(new URL('../dist/ui.css', import.meta.url), 'utf8');
const hues = readFileSync(new URL('../styles/settings/_hues.scss', import.meta.url), 'utf8');
const hueNames = [...new Set([...hues.matchAll(/^\s+'([a-z]+)': \(/gm)].map(([, name]) => name))];

// The top-level rules of the crudit-ui-base layer (the tokens), without the at-rules (@media print would put the light colours back).
function topLevelRules() {
    const layer = css.slice(css.indexOf('@layer crudit-ui-base{') + '@layer crudit-ui-base{'.length);
    const rules = [];
    let depth = 0;
    let start = 0;
    for (let index = 0; index < layer.length && depth >= 0; index++) {
        if (layer[index] === '{') {
            depth++;
        } else if (layer[index] === '}') {
            if (--depth === 0) {
                rules.push(layer.slice(start, index + 1));
                start = index + 1;
            }
        }
    }

    return rules.filter((rule) => !rule.startsWith('@'));
}

// The custom properties declared by the top-level rules whose selector is `selector`.
function variables(selector) {
    const bodies = topLevelRules().filter((rule) => rule.startsWith(`${selector}{`));
    assert.notEqual(bodies.length, 0, `no ${selector} rule in dist/ui.css`);

    return Object.fromEntries(bodies.flatMap((body) => [...body.matchAll(/(--[\w-]+):([^;}]+)/g)]).map(([, name, value]) => [name, value.trim()]));
}

// WCAG 2 relative luminance of a #rgb / #rrggbb colour.
function luminance(hex) {
    const digits = hex.slice(1).length === 3 ? [...hex.slice(1)].map((digit) => digit + digit).join('') : hex.slice(1);
    const [r, g, b] = [0, 2, 4].map((index) => {
        const channel = parseInt(digits.slice(index, index + 2), 16) / 255;

        return channel <= 0.04045 ? channel / 12.92 : ((channel + 0.055) / 1.055) ** 2.4;
    });

    return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}

function contrast(foreground, background) {
    const [light, dark] = [luminance(foreground), luminance(background)].sort((a, b) => b - a);

    return (light + 0.05) / (dark + 0.05);
}

// Text / background pairs that must reach 4.5:1 (WCAG 1.4.3). The colours derived from --brand depend on the
// project: they are not checked here.
const pairs = [
    ['--c-text', '--c-bg'],
    ['--c-text', '--c-surface'],
    ['--c-text', '--c-surface-sunken'],
    ['--c-text-muted', '--c-bg'],
    ['--c-text-muted', '--c-surface'],
    ['--c-success-text', '--c-success-bg'],
    ['--c-warning-text', '--c-warning-bg'],
    ['--c-danger-text', '--c-danger-bg'],
    ['--c-info-text', '--c-info-bg'],
    ['--c-neutral-text', '--c-neutral-bg'],
    ['--c-on-danger-solid', '--c-danger-solid'],
    ['--c-on-success-solid', '--c-success-solid'],
    ['--c-inverse-text', '--c-inverse-bg'],
    ...hueNames.map((name) => [`--c-${name}-text`, `--c-${name}-bg`]),
];

test('the palette has the 11 hues of the states', () => {
    assert.deepEqual(hueNames, ['blue', 'teal', 'green', 'lime', 'yellow', 'orange', 'red', 'pink', 'purple', 'indigo', 'gray']);
});

for (const [theme, selector] of [['light', ':root'], ['dark', ':root[data-theme=dark]']]) {
    test(`every text colour has a contrast of 4.5:1 on its background in the ${theme} theme`, () => {
        const colours = variables(selector);
        for (const [foreground, background] of pairs) {
            assert.ok(colours[foreground] && colours[background], `${foreground} or ${background} is missing (${theme})`);
            const ratio = contrast(colours[foreground], colours[background]);
            assert.ok(ratio >= 4.5, `${foreground} on ${background}: ${ratio.toFixed(2)}:1 (${theme})`);
        }
    });
}

// Components and charts against their background: 3:1 (WCAG 1.4.11, RGAA 3.3). --chart-1 follows the brand.
const componentPairs = [
    ['--c-border-strong', '--c-surface'],
    ['--c-border-strong', '--c-bg'],
    // Icon of a success toast
    ['--c-success-border', '--c-inverse-bg'],
    ...[2, 3, 4, 5, 6].map((series) => [`--chart-${series}`, '--c-surface']),
];

for (const [theme, selector] of [['light', ':root'], ['dark', ':root[data-theme=dark]']]) {
    test(`the borders of the controls and the chart series have a contrast of 3:1 in the ${theme} theme`, () => {
        const colours = variables(selector);
        for (const [foreground, background] of componentPairs) {
            const ratio = contrast(colours[foreground], colours[background]);
            assert.ok(ratio >= 3, `${foreground} on ${background}: ${ratio.toFixed(2)}:1 (${theme})`);
        }
    });
}

test('the brand is the only colour a project has to provide', () => {
    assert.equal(variables(':root')['--c-primary'], 'var(--brand)');
    assert.match(variables(':root')['--brand'], /^#[0-9a-f]{3,6}$/);
});

test('the components take their text sizes from the tokens', () => {
    const components = new URL('../styles/components/', import.meta.url);
    const fixed = readdirSync(components).flatMap((file) => [...readFileSync(new URL(file, components), 'utf8')
        .matchAll(/font-size:\s*[\d.]+(rem|px)/g)].map(([declaration]) => `${file}: ${declaration}`));
    // The initials of a small avatar follow the size of its circle (1.375rem), not the scale of the text.
    assert.deepEqual(fixed, ['_avatar.scss: font-size: 0.6875rem']);
    assert.ok(css.includes('--font-size-2xs: 0.75rem'));
});
