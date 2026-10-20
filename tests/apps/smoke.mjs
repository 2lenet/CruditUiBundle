// Smoke test of a test application: node smoke.mjs <url> [chrome path]
// The page must show the dropdown controller of the bundle and load its stylesheet; the design kit page must have no
// accessibility violation (axe-core).
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import {createRequire} from 'node:module';
import puppeteer from 'puppeteer-core';

const url = process.argv[2];
const executablePath = process.argv[3] ?? process.env.CHROME_PATH ?? '/usr/bin/google-chrome';
assert.ok(url, 'Usage: node smoke.mjs <url> [chrome path]');

const browser = await puppeteer.launch({executablePath, headless: true, args: ['--no-sandbox']});
const errors = [];

try {
    const page = await browser.newPage();
    page.on('pageerror', (error) => errors.push(`page error: ${error.message}`));
    page.on('console', (message) => message.type() === 'error' && !message.location().url?.endsWith('/favicon.ico') && errors.push(`console: ${message.text()}`));
    page.on('requestfailed', (request) => errors.push(`request failed: ${request.url()}`));
    page.on('response', (response) => response.status() >= 400 && !response.url().endsWith('/favicon.ico') && errors.push(`${response.status()}: ${response.url()}`));

    await page.goto(url, {waitUntil: 'networkidle0'});

    // The stylesheet of the bundle is loaded, and the breakpoints are exposed to the JavaScript.
    const md = await page.evaluate(() => getComputedStyle(document.documentElement).getPropertyValue('--breakpoint-md').trim());
    assert.equal(md, '768px', 'the stylesheet of the bundle is not loaded');

    // The project stylesheet wins over the layer of the bundle.
    const primary = await page.evaluate(() => getComputedStyle(document.documentElement).getPropertyValue('--brand').trim());
    assert.equal(primary, '#b3261e', 'the project must override the variables of the bundle');

    // The Stimulus controller of the bundle works.
    const button = await page.waitForSelector('.crudit-ui-dropdown__button');
    const expanded = () => page.evaluate((b) => b.getAttribute('aria-expanded'), button);
    assert.equal(await expanded(), 'false');
    await button.click();
    assert.equal(await expanded(), 'true', 'the dropdown does not open: is the controller registered?');
    await page.keyboard.press('Escape');
    assert.equal(await expanded(), 'false', 'Escape does not close the dropdown');

    // Bootstrap, in its layer between the two layers of the bundle (assets/styles/layers.css): the components of the
    // bundle win over its reboot, its classes win over the reset of the bundle.
    const style = (selector, property) => page.evaluate((s, p) => getComputedStyle(document.querySelector(s))[p], selector, property);
    assert.equal(await style('.crudit-ui-dropdown__button', 'paddingTop'), '6px', 'the reboot of Bootstrap wins over a component of the bundle');
    assert.equal(await style('a.btn', 'color'), 'rgb(255, 255, 255)', 'the reset of the bundle wins over a class of Bootstrap');

    // The theme switch: the choice goes on <html> and in a cookie, which the server puts back on the next page.
    const theme = () => page.evaluate(() => document.documentElement.dataset.theme ?? 'system');
    const background = () => page.evaluate(() => getComputedStyle(document.documentElement).getPropertyValue('--c-bg').trim());
    await page.emulateMediaFeatures([{name: 'prefers-color-scheme', value: 'light'}]);
    assert.equal(await theme(), 'system');
    await page.click('button[value="dark"]');
    assert.equal(await theme(), 'dark');
    assert.equal(await background(), '#0e131a', 'the dark colours are not applied');
    assert.equal(await page.evaluate(() => document.querySelector('button[value="dark"]').getAttribute('aria-pressed')), 'true');
    await page.reload({waitUntil: 'networkidle0'});
    assert.equal(await page.evaluate(() => document.documentElement.getAttribute('data-theme')), 'dark', 'ui_theme() does not read the cookie');
    await page.click('button[value="system"]');
    assert.equal(await theme(), 'system');
    assert.equal(await page.evaluate(() => document.cookie.includes('crudit_ui_theme')), false, 'the cookie is not removed');
    await page.emulateMediaFeatures([{name: 'prefers-color-scheme', value: 'dark'}]);
    assert.equal(await background(), '#0e131a', 'without a choice, the colours do not follow the system');

    // An icon of the catalogue, imported by the lock (the Finder of the bundle): Iconify is not called in production.
    assert.ok(await page.$('#locked svg'), 'the icon "delete" is missing: run bin/console ux:icons:lock');

    // Languages: lang and dir of <html>, translations of the bundle, numbers and dates in Latin digits, with the
    // separators of the country, and the directional icons mirrored in right-to-left.
    const locale = async (path) => {
        await page.goto(new URL(path, url).href, {waitUntil: 'networkidle0'});

        return page.evaluate(() => ({
            lang: document.documentElement.lang,
            dir: document.documentElement.dir,
            dark: document.querySelector('button[value="dark"]').textContent.trim(),
            number: document.getElementById('number').textContent.trim(),
            date: document.getElementById('date').textContent.trim(),
            scale: getComputedStyle(document.querySelector('#mirrored svg')).scale,
        }));
    };
    // The spaces and separators of the dates depend on the version of ICU: only their digits are checked.
    const latinDate = (text, numbers) => {
        assert.doesNotMatch(text, /[\u0660-\u0669\u06F0-\u06F9]/, `the date is not in Latin digits: ${text}`);
        assert.deepEqual(text.match(/\d+/g), numbers, `wrong date: ${text}`);
    };
    const fr = await locale('fr');
    assert.deepEqual([fr.lang, fr.dir, fr.dark, fr.number, fr.date], ['fr', 'ltr', 'Sombre', '1 234,5', '13/10/2026']);
    assert.equal(fr.scale, 'none', 'an icon is mirrored in a left-to-right page');
    const arSa = await locale('ar_SA');
    assert.deepEqual([arSa.lang, arSa.dir, arSa.dark, arSa.number], ['ar-SA', 'rtl', 'داكن', '1,234.5'], 'ar_SA');
    latinDate(arSa.date, ['13', '10', '2026']);
    assert.equal(arSa.scale, '-1 1', 'a directional icon is not mirrored in a right-to-left page');
    const arMa = await locale('ar_MA');
    assert.equal(arMa.number, '1.234,5', 'ar_MA does not follow the separators of Morocco');
    const fa = await locale('fa');
    assert.deepEqual([fa.dir, fa.number], ['rtl', '1,234.5'], 'fa: Latin digits');
    latinDate(fa.date, ['2026', '10', '13']);

    // Accessibility of the design kit page (WCAG 2.1 A and AA, axe-core), in light, in dark and right to left.
    const axeSource = readFileSync(createRequire(import.meta.url).resolve('axe-core/axe.min.js'), 'utf8');
    const audit = async (path, scheme, viewport = null) => {
        if (viewport) {
            await page.setViewport(viewport);
        }
        await page.emulateMediaFeatures([{name: 'prefers-color-scheme', value: scheme}]);
        await page.goto(new URL(path, url).href, {waitUntil: 'networkidle0'});
        assert.ok(await page.$('div.crudit-ui-kit'), `the design kit page is missing: ${path}`);
        await page.addScriptTag({content: axeSource});
        const violations = await page.evaluate(async () => (await window.axe.run(document, {runOnly: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa']})).violations
            .map((violation) => `${violation.id} (${violation.nodes.map((node) => node.target.join(' ')).join(', ')}): ${violation.help}`));
        assert.deepEqual(violations, [], `accessibility of ${path} in ${scheme}`);
    };
    await audit('crudit-ui/kit', 'light');
    await audit('crudit-ui/kit', 'dark');
    await audit('crudit-ui/kit/ar', 'light');

    // On a phone: without <meta name="viewport"> in base.html.twig, the page would be laid out on 980 px and shrunk.
    await audit('crudit-ui/kit', 'light', {width: 360, height: 740, isMobile: true, hasTouch: true});
    const widths = await page.evaluate(() => ({client: document.documentElement.clientWidth, scroll: document.documentElement.scrollWidth}));
    assert.equal(widths.client, 360, 'the page is not laid out on the width of the phone: <meta name="viewport"> missing?');
    assert.ok(widths.scroll <= widths.client, `the page scrolls horizontally on a phone (${widths.scroll} px)`);

    // The button controller ignores the click of a loading button (aria-disabled): no second sending.
    const clicked = await page.evaluate(() => {
        const reached = (button) => {
            let hit = false;
            button.addEventListener('click', () => { hit = true; });
            button.click();

            return hit;
        };

        return {
            loading: reached(document.querySelector('#kit-buttons button[aria-busy="true"]')),
            enabled: reached(document.querySelector('#kit-buttons table button:not([disabled]):not([aria-disabled])')),
        };
    });
    assert.deepEqual(clicked, {loading: false, enabled: true}, 'the click of a loading button is not ignored: is the button controller registered?');

    // The close button of an alert removes it, and the focus does not fall back to the top of the page.
    await page.focus('#kit-dismissible-alert .crudit-ui-alert__close');
    await page.keyboard.press('Enter');
    const closed = await page.evaluate(() => ({
        removed: !document.getElementById('kit-dismissible-alert'),
        focus: document.activeElement?.className,
    }));
    assert.deepEqual(closed, {removed: true, focus: 'crudit-ui-kit'}, 'the alert is not closed, or the focus is lost: is the alert controller registered?');

    // An alert closed while the user types in a field does not steal the focus.
    await page.reload({waitUntil: 'networkidle0'});
    const kept = await page.evaluate(() => {
        const field = document.body.appendChild(Object.assign(document.createElement('input'), {'aria-label': 'Field'}));
        field.focus();
        document.querySelector('#kit-dismissible-alert .crudit-ui-alert__close').click();

        return {removed: !document.getElementById('kit-dismissible-alert'), focus: document.activeElement === field};
    });
    assert.deepEqual(kept, {removed: true, focus: true}, 'closing an alert from elsewhere moves the focus');

    // A double click on a submit button sends its form once: the first sending puts it in loading.
    // The test cancels the sending to stay on the page: the button is in loading at once, before the second click,
    // and only goes back to normal once the event has gone through its listeners.
    const sent = await page.evaluate(() => {
        const form = document.getElementById('kit-submit-form');
        let count = 0;
        form.addEventListener('submit', (event) => { count++; event.preventDefault(); });
        const button = form.querySelector('button[type="submit"]');
        button.click();
        button.click();

        return {count, busy: button.getAttribute('aria-busy'), spinner: button.querySelector(':scope > svg')?.classList.contains('crudit-ui-icon--spin')};
    });
    assert.deepEqual(sent, {count: 1, busy: 'true', spinner: true}, 'a double click sends the form twice: busyOnSubmit');

    // The button goes back to normal when the sending is cancelled by a listener added later on the document, when
    // Turbo has sent the form, and it does not go in loading for a form sent to another window.
    await page.reload({waitUntil: 'networkidle0'});
    const states = await page.evaluate(async () => {
        const form = document.getElementById('kit-submit-form');
        const button = form.querySelector('button[type="submit"]');
        const tick = () => new Promise((resolve) => setTimeout(resolve, 20));
        const state = () => button.getAttribute('aria-busy');

        const cancel = (event) => event.preventDefault();
        document.addEventListener('submit', cancel);
        button.click();
        await tick();
        const cancelled = state();
        document.removeEventListener('submit', cancel);

        form.target = '_blank';
        form.addEventListener('submit', cancel);
        button.click();
        await tick();
        const blank = state();
        form.removeEventListener('submit', cancel);
        form.removeAttribute('target');

        const submission = {formSubmission: {submitter: button}};
        document.dispatchEvent(new CustomEvent('turbo:submit-start', {detail: submission}));
        const turboStart = state();
        document.dispatchEvent(new CustomEvent('turbo:submit-end', {detail: submission}));

        return {cancelled, blank, turboStart, turboEnd: state()};
    });
    assert.deepEqual(states, {cancelled: null, blank: null, turboStart: 'true', turboEnd: null}, 'busyOnSubmit: cancelled sending, other window or Turbo');

    assert.deepEqual(errors, [], 'errors while loading the page');
    console.log(`OK ${url}`);
} finally {
    await browser.close();
}
