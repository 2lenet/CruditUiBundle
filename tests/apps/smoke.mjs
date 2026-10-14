// Smoke test of a test application: node smoke.mjs <url> [chrome path]
// The page must show the dropdown controller of the bundle and load its stylesheet.
import assert from 'node:assert/strict';
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
            scale: getComputedStyle(document.getElementById('mirrored')).scale,
        }));
    };
    const latin = (text) => /^[\d\s.,/‏ ]+$/.test(text);
    const fr = await locale('fr');
    assert.deepEqual([fr.lang, fr.dir, fr.dark, fr.number, fr.date], ['fr', 'ltr', 'Sombre', '1 234,5', '13/10/2026']);
    assert.equal(fr.scale, 'none', 'an icon is mirrored in a left-to-right page');
    const arSa = await locale('ar_SA');
    assert.deepEqual([arSa.lang, arSa.dir, arSa.dark, arSa.number], ['ar-SA', 'rtl', 'داكن', '1,234.5'], 'ar_SA');
    assert.ok(latin(arSa.date), `the date is not in Latin digits: ${arSa.date}`);
    assert.equal(arSa.scale, '-1 1', 'a directional icon is not mirrored in a right-to-left page');
    const arMa = await locale('ar_MA');
    assert.equal(arMa.number, '1.234,5', 'ar_MA does not follow the separators of Morocco');
    const fa = await locale('fa');
    assert.deepEqual([fa.dir, fa.number, fa.date], ['rtl', '1,234.5', '2026/10/13'], 'fa: Gregorian calendar in Latin digits');

    assert.deepEqual(errors, [], 'errors while loading the page');
    console.log(`OK ${url}`);
} finally {
    await browser.close();
}
