// Smoke test of the components under Turbo Drive (test application tests/apps/turbo): node smoke-turbo.mjs <url> [chrome path]
// Real Turbo visits, not simulated events: the cache and Back, the controllers disconnected and connected again
// without leaving listeners behind, forms answered by a redirect (303), by their errors (422) or by a Turbo Stream, a
// Turbo Frame, a page refreshed by a morph, the language and the theme across visits, accessibility of the pages.
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import {createRequire} from 'node:module';
import puppeteer from 'puppeteer-core';

const url = process.argv[2];
const executablePath = process.argv[3] ?? process.env.CHROME_PATH ?? '/usr/bin/google-chrome';
assert.ok(url, 'Usage: node smoke-turbo.mjs <url> [chrome path]');

const browser = await puppeteer.launch({executablePath, headless: true, args: ['--no-sandbox']});
const errors = [];
const axeSource = readFileSync(createRequire(import.meta.url).resolve('axe-core/axe.min.js'), 'utf8');
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

try {
    const page = await browser.newPage();
    await page.setViewport({width: 1280, height: 800});
    page.on('pageerror', (error) => errors.push(`page error: ${error.message}`));
    // 422: a form sent back with its errors, on purpose.
    page.on('console', (message) => message.type() === 'error' && !message.location().url?.endsWith('/favicon.ico') && !message.text().includes('status of 422') && errors.push(`console: ${message.text()}`));
    page.on('response', (response) => response.status() >= 400 && response.status() !== 422 && !response.url().endsWith('/favicon.ico') && errors.push(`${response.status()}: ${response.url()}`));
    const posts = [];
    page.on('request', (request) => request.method() === 'POST' && posts.push(new URL(request.url()).pathname));

    // Waits for a Turbo event on the document after an action: turbo:load (a visit, Back), turbo:render (a form sent
    // back with its errors), turbo:frame-load (a frame). Then a frame, for Stimulus to connect the controllers.
    const after = async (event, action) => {
        await page.evaluate((name) => {
            window.turboEvent = new Promise((resolve) => document.addEventListener(name, () => resolve(), {once: true}));
        }, event);
        await action();
        await page.waitForFunction(() => window.turboEvent.then(() => true), {timeout: 10000});
        await page.evaluate(() => new Promise((resolve) => requestAnimationFrame(() => resolve())));
    };
    const visit = (selector) => after('turbo:load', () => page.click(selector));
    const back = () => after('turbo:load', () => page.evaluate(() => history.back()));
    const path = () => page.evaluate(() => location.pathname + location.search);

    // A page loaded once: the visits that follow keep this window (no full load).
    await page.goto(new URL('turbo', url).href, {waitUntil: 'networkidle0'});
    assert.equal(await page.evaluate(() => typeof window.Turbo?.visit), 'function', 'Turbo is not loaded');
    await page.evaluate(() => {
        window.marker = true;
    });
    const sameWindow = async (message) => assert.equal(await page.evaluate(() => window.marker), true, `${message}: the page was fully loaded, not visited by Turbo`);

    // State of the components of a page.
    const dropdown = (id) => page.evaluate((menuId) => ({
        expanded: document.querySelector(`#${menuId} > button`).getAttribute('aria-expanded'),
        hidden: document.getElementById(`${menuId}-panel`).hidden,
    }), id);
    const dialog = (id) => page.evaluate((dialogId) => {
        const element = document.getElementById(dialogId);

        return {open: element.open, modal: element.matches(':modal'), focusIn: element.contains(document.activeElement)};
    }, id);
    const locked = () => page.evaluate(() => getComputedStyle(document.documentElement).overflow === 'hidden');
    const toasts = () => page.$$eval('#crudit-ui-toasts .crudit-ui-toast', (elements) => elements.map((toast) => toast.querySelector('.crudit-ui-toast__message').textContent.trim()));
    const announced = (text) => page.waitForFunction((expected) => document.querySelector('#crudit-ui-toasts [aria-live="polite"]').textContent.includes(expected), {timeout: 3000}, text)
        .then(() => true, () => false);

    // Listeners on the document and the window, counted by the DevTools protocol, by type.
    const cdp = await page.createCDPSession();
    const listeners = async () => {
        const counts = {};
        for (const target of ['document', 'window']) {
            const {result} = await cdp.send('Runtime.evaluate', {expression: target});
            const {listeners: found} = await cdp.send('DOMDebugger.getEventListeners', {objectId: result.objectId});
            await cdp.send('Runtime.releaseObject', {objectId: result.objectId});
            found.forEach(({type}) => {
                counts[`${target} ${type}`] = (counts[`${target} ${type}`] ?? 0) + 1;
            });
        }

        return counts;
    };

    // Visits back and forth: the controllers of each page connect, those of the page left disconnect and remove
    // their listeners. Counted on the home page after a first round trip (Turbo adds its own listener of the clicks
    // on the first one), and after five more.
    await visit('#turbo-link-second');
    await visit('#turbo-link-home');
    const before = await listeners();
    for (let i = 0; i < 5; i++) {
        await visit('#turbo-link-second');
        assert.equal(await path(), '/turbo/second');
        await visit('#turbo-link-home');
    }
    await sameWindow('visits');
    assert.deepEqual(await listeners(), before, 'listeners left on the document or the window after Turbo visits');

    // One controller per element: a toast of the JavaScript is shown once, a dropdown opens with one click, a dialog
    // dispatches "open" once.
    await page.evaluate(() => document.dispatchEvent(new CustomEvent('crudit-ui:toast', {detail: {message: 'Only once.'}})));
    assert.deepEqual(await toasts(), ['Information: Only once.'], 'a toast of the JavaScript is shown several times after Turbo visits');
    await page.click('#turbo-menu > button');
    assert.deepEqual(await dropdown('turbo-menu'), {expanded: 'true', hidden: false}, 'the dropdown does not open after Turbo visits');
    await page.keyboard.press('Escape');
    await page.evaluate(() => {
        window.opened = 0;
        document.getElementById('turbo-dialog').addEventListener('2lenet--crudit-ui-bundle--dialog:open', () => window.opened++);
    });
    await page.click('#turbo-dialog-open');
    assert.deepEqual([await dialog('turbo-dialog'), await page.evaluate(() => window.opened)], [{open: true, modal: true, focusIn: true}, 1], 'the dialog does not open once after Turbo visits');
    await page.keyboard.press('Escape');
    await page.click('#crudit-ui-toasts .crudit-ui-toast__close');

    // Left with a dropdown open and a toast: the snapshot of Turbo has neither. Back shows the page as it was before,
    // and its components work.
    await page.click('#turbo-menu > button');
    await page.evaluate(() => document.dispatchEvent(new CustomEvent('crudit-ui:toast', {detail: {message: 'Saved.', tone: 'danger'}})));
    assert.equal((await toasts()).length, 1);
    await after('turbo:load', () => page.evaluate(() => window.Turbo.visit('/turbo/second')));
    await back();
    assert.equal(await path(), '/turbo');
    assert.deepEqual(await dropdown('turbo-menu'), {expanded: 'false', hidden: true}, 'a dropdown open when the page was left is restored open by Back');
    assert.deepEqual(await toasts(), [], 'the toasts of the page left are shown again by Back');
    await page.click('#turbo-menu > button');
    assert.deepEqual(await dropdown('turbo-menu'), {expanded: 'true', hidden: false}, 'the dropdown does not open after Back');
    await page.keyboard.press('Escape');
    await page.evaluate(() => document.dispatchEvent(new CustomEvent('crudit-ui:toast', {detail: {message: 'After Back.'}})));
    assert.deepEqual(await toasts(), ['Information: After Back.'], 'a toast is not shown after Back');
    await page.click('#crudit-ui-toasts .crudit-ui-toast__close');

    // A link of an open dialog: the next page is not locked (no scroll, inert), and Back does not show the dialog open.
    await page.click('#turbo-dialog-open');
    assert.equal(await locked(), true, 'the page scrolls under the dialog');
    await visit('#turbo-dialog-link');
    assert.equal(await path(), '/turbo/second');
    assert.deepEqual([await locked(), await page.evaluate(() => document.querySelector(':modal'))], [false, null], 'the page reached from a dialog is still locked');
    await page.click('#turbo-second-menu > button');
    assert.equal((await dropdown('turbo-second-menu')).expanded, 'true', 'the page reached from a dialog is inert');
    await page.keyboard.press('Escape');
    await back();
    assert.deepEqual([await dialog('turbo-dialog'), await locked()], [{open: false, modal: false, focusIn: false}, false], 'Back shows the dialog open, or the page locked');
    await page.click('#turbo-dialog-open');
    assert.deepEqual(await dialog('turbo-dialog'), {open: true, modal: true, focusIn: true}, 'the dialog does not open after Back');
    await page.keyboard.press('Escape');
    assert.deepEqual([(await dialog('turbo-dialog')).open, await page.evaluate(() => document.activeElement.id)], [false, 'turbo-dialog-open'], 'Escape does not close the dialog after Back, or the focus is lost');
    await sameWindow('Back');

    // The theme: <html> stays across the visits, the switch of each page shows the choice, even a page restored by
    // Back from a snapshot taken with another choice.
    const theme = () => page.evaluate(() => ({
        html: document.documentElement.dataset.theme ?? 'system',
        pressed: document.querySelector('.turbo-header__theme [aria-pressed="true"]')?.value,
    }));
    await page.click('.turbo-header__theme button[value="dark"]');
    await visit('#turbo-link-second');
    assert.deepEqual(await theme(), {html: 'dark', pressed: 'dark'}, 'the theme is lost by a Turbo visit');
    await page.click('.turbo-header__theme button[value="light"]');
    await back();
    assert.deepEqual(await theme(), {html: 'light', pressed: 'light'}, 'the theme switch restored by Back does not show the choice');
    await page.click('.turbo-header__theme button[value="system"]');

    // Tabs that follow the address: the state of Turbo in the history is kept, so Back after a visit restores the
    // page, on its tab, without a full load.
    await page.click('#turbo-tabs-tab-parts');
    assert.equal(await path(), '/turbo?tab=parts');
    assert.ok(await page.evaluate(() => history.state?.turbo), 'the tabs remove the state of Turbo from the history');
    await visit('#turbo-link-second');
    await back();
    assert.deepEqual([await path(), await page.$eval('#turbo-tabs [aria-selected="true"]', (tab) => tab.id)], ['/turbo?tab=parts', 'turbo-tabs-tab-parts'], 'Back does not come back on the tab');
    await sameWindow('tabs');
    await page.focus('#turbo-tabs-tab-parts');
    await page.keyboard.press('ArrowRight');
    assert.equal(await page.$eval('#turbo-tabs [aria-selected="true"]', (tab) => tab.id), 'turbo-tabs-tab-history', 'the tabs do not work after Back');

    // A form answered by a redirect (303): the next page shows the flash message as a toast, and announces it. Back:
    // the button of the form is not left in loading in the snapshot.
    await visit('#turbo-save');
    assert.equal(await path(), '/turbo/second');
    assert.deepEqual(await toasts(), ['Success: The visit V-0412 is saved.'], 'the flash message is not shown after a redirect of Turbo');
    assert.ok(await announced('The visit V-0412 is saved.'), 'the flash message is not announced after a redirect of Turbo');
    await back();
    assert.deepEqual(await page.$eval('#turbo-save', (button) => [button.getAttribute('aria-busy'), button.getAttribute('aria-disabled')]), [null, null], 'the button of a form sent by Turbo stays in loading in the snapshot');
    await sameWindow('redirect');

    // Turbo Stream: the toast appended to the region is shown and announced; the fragment replaced works; the page
    // stays.
    await page.click('#turbo-stream');
    await page.waitForFunction(() => document.getElementById('turbo-streams').textContent === '1', {timeout: 5000});
    assert.deepEqual(await toasts(), ['Success: Stream 1 received.'], 'the toast of a Turbo Stream is not shown');
    assert.ok(await announced('Stream 1 received.'), 'the toast of a Turbo Stream is not announced');
    assert.equal(await page.$eval('#turbo-stream', (button) => button.getAttribute('aria-busy')), null, 'the button stays in loading after a Turbo Stream');
    await page.click('#turbo-fragment-menu > button');
    assert.deepEqual(await dropdown('turbo-fragment-menu'), {expanded: 'true', hidden: false}, 'the dropdown of a fragment replaced by a Turbo Stream does not open');
    await page.keyboard.press('Escape');
    assert.equal(await path(), '/turbo?tab=history', 'a Turbo Stream changes the page');

    // A slow answer: the button is in loading while Turbo sends the form, a second click sends nothing, the button goes
    // back to normal with the answer.
    posts.length = 0;
    await page.click('#turbo-slow');
    await page.click('#turbo-slow');
    assert.equal(await page.$eval('#turbo-slow', (button) => button.getAttribute('aria-busy')), 'true', 'the button is not in loading while Turbo sends the form');
    await page.waitForFunction(() => document.getElementById('turbo-streams').textContent === '2', {timeout: 5000});
    await sleep(50);
    assert.deepEqual([posts, await page.$eval('#turbo-slow', (button) => button.getAttribute('aria-busy'))], [['/turbo/stream'], null], 'a second click sends the form again, or the button stays in loading');
    await page.$$eval('#crudit-ui-toasts .crudit-ui-toast__close', (buttons) => buttons.forEach((button) => button.click()));

    // A form in a dialog sent back with its errors (422): the page rendered again by Turbo opens the dialog (value
    // "open"), with the error, the focus in it. Sent again right: redirect, the toast, the page free.
    await page.click('#turbo-visit-open');
    await after('turbo:render', () => page.click('#turbo-visit-submit'));
    assert.deepEqual(await dialog('turbo-visit-dialog'), {open: true, modal: true, focusIn: true}, 'the dialog of a form sent back with its errors (422) is not open');
    assert.ok(await page.$('#turbo-visit-error'), 'the error of the form is not shown');
    await page.type('#turbo-visit-reason', 'Customer away');
    await visit('#turbo-visit-submit');
    assert.deepEqual([await path(), await toasts(), await locked(), (await dialog('turbo-visit-dialog')).open], ['/turbo', ['Success: The visit V-0412 is postponed.'], false, false], 'the form sent again does not lead to the home page, free');
    await page.click('#crudit-ui-toasts .crudit-ui-toast__close');

    // A page refreshed by a morph. A form in a dialog sent back with its errors (422): the dialog stays (the same
    // element), open and modal, without closing in between, the focus where it was, with the error. Sent again right:
    // the redirect to the same page morphs it, the dialog is closed and the page free (the morph removes the attribute
    // "open": without the controller, the dialog would be hidden but still modal), the flash message shown and
    // announced.
    await visit('#turbo-link-morph');
    await page.evaluate(() => {
        window.morphDialog = document.getElementById('turbo-morph-dialog');
        window.morphCloses = 0;
        window.morphDialog.addEventListener('close', () => window.morphCloses++);
    });
    await page.click('#turbo-morph-open');
    await page.focus('#turbo-morph-reason');
    await after('turbo:render', () => page.keyboard.press('Enter'));
    await sleep(50);
    assert.equal(await page.evaluate(() => window.morphDialog === document.getElementById('turbo-morph-dialog')), true, 'the page is not morphed (turbo-refresh-method)');
    assert.deepEqual(await dialog('turbo-morph-dialog'), {open: true, modal: true, focusIn: true}, 'the dialog of a form sent back with its errors is not modal after a morph');
    assert.deepEqual(await page.evaluate(() => [window.morphCloses, document.activeElement.id]), [0, 'turbo-morph-reason'], 'the dialog of a form sent back with its errors closes during the morph, or the focus moves');
    assert.ok(await page.$('#turbo-morph-error'), 'the error of the form is not shown after a morph');
    await page.type('#turbo-morph-reason', 'Customer away');
    await after('turbo:morph', () => page.click('#turbo-morph-submit'));
    await page.waitForFunction(() => document.getElementById('turbo-refreshes').textContent === '1', {timeout: 5000});
    assert.equal(await page.evaluate(() => window.morphDialog === document.getElementById('turbo-morph-dialog')), true, 'the redirect to the same page does not morph it');
    assert.deepEqual([await dialog('turbo-morph-dialog'), await locked(), await page.evaluate(() => document.querySelector(':modal'))], [{open: false, modal: false, focusIn: false}, false, null], 'the dialog stays open, or the page locked, after a morph');
    assert.deepEqual(await toasts(), ['Success: The visit V-0412 is postponed.'], 'the flash message is not shown after a morph');
    assert.ok(await announced('The visit V-0412 is postponed.'), 'the flash message is not announced after a morph');
    await page.click('#crudit-ui-toasts .crudit-ui-toast__close');

    // A dropdown open during a refresh by a morph: its state stays consistent, and it works.
    await page.click('#turbo-morph-menu > button');
    await after('turbo:morph', () => page.evaluate(() => window.Turbo.visit(location.href, {action: 'replace'})));
    const morphed = await dropdown('turbo-morph-menu');
    assert.equal(morphed.expanded === 'true', !morphed.hidden, 'the state of the dropdown is not consistent after a morph');
    await page.click('#turbo-morph-menu > button');
    assert.deepEqual(await dropdown('turbo-morph-menu'), morphed.hidden ? {expanded: 'true', hidden: false} : {expanded: 'false', hidden: true}, 'the dropdown does not work after a morph');
    await page.evaluate(() => document.activeElement.blur());
    await page.mouse.click(5, 790);
    await sameWindow('morph');

    // A Turbo Frame: loaded lazily, then navigated by its link. Its components work each time; the page stays.
    await visit('#turbo-link-frame');
    await page.waitForSelector('#turbo-frame-title');
    const frame = async (step) => {
        assert.equal(await page.$eval('#turbo-frame-title', (title) => title.textContent), `Step ${step}`);
        await page.click('#turbo-frame-menu > button');
        assert.deepEqual(await dropdown('turbo-frame-menu'), {expanded: 'true', hidden: false}, `the dropdown of the frame does not open (step ${step})`);
        await page.keyboard.press('Escape');
        await page.click('#turbo-frame-dialog-open');
        assert.deepEqual(await dialog('turbo-frame-dialog'), {open: true, modal: true, focusIn: true}, `the dialog of the frame does not open (step ${step})`);
        await page.keyboard.press('Escape');
        assert.equal((await dialog('turbo-frame-dialog')).open, false);
        await page.focus('#turbo-frame-tabs-tab-general');
        await page.keyboard.press('ArrowRight');
        assert.equal(await page.$eval('#turbo-frame-tabs [aria-selected="true"]', (tab) => tab.id), 'turbo-frame-tabs-tab-parts', `the tabs of the frame do not work (step ${step})`);
    };
    await frame(1);
    await after('turbo:frame-load', () => page.click('#turbo-frame-next'));
    await frame(2);
    assert.equal(await path(), '/turbo/frame', 'the link of the frame changes the page');
    await sameWindow('frame');

    // The language by a Turbo visit: lang and dir of <html> follow, and the components read the direction.
    await visit('#turbo-link-home');
    await visit('#turbo-locale-ar');
    assert.deepEqual(await page.evaluate(() => [document.documentElement.lang, document.documentElement.dir]), ['ar', 'rtl'], 'lang and dir do not follow a Turbo visit');
    await page.focus('#turbo-tabs-tab-general');
    await page.keyboard.press('ArrowRight');
    assert.equal(await page.$eval('#turbo-tabs [aria-selected="true"]', (tab) => tab.id), 'turbo-tabs-tab-history', 'tabs right to left after a Turbo visit: → must go to the previous tab');
    await visit('#turbo-locale-fr');
    assert.deepEqual(await page.evaluate(() => [document.documentElement.lang, document.documentElement.dir]), ['fr', 'ltr'], 'lang and dir do not follow a Turbo visit back to French');
    await sameWindow('language');

    // Accessibility of the pages reached by Turbo (WCAG 2.1 A and AA, axe-core), and of a dialog sent back with its
    // errors.
    await page.addScriptTag({content: axeSource});
    const audit = async (name) => {
        // The contrasts are measured once the opening of a dialog is over.
        await page.evaluate(() => Promise.allSettled(document.getAnimations().map((animation) => animation.finished)));
        const violations = await page.evaluate(async () => (await window.axe.run(document, {runOnly: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa']})).violations
            .map((violation) => `${violation.id} (${violation.nodes.map((node) => node.target.join(' ')).join(', ')}): ${violation.help}`));
        assert.deepEqual(violations, [], `accessibility of ${name}`);
    };
    await audit('the home page');
    await visit('#turbo-link-second');
    await audit('the second page');
    await visit('#turbo-link-frame');
    await page.waitForSelector('#turbo-frame-title');
    await audit('the frame page');
    await visit('#turbo-link-morph');
    await page.click('#turbo-morph-open');
    await after('turbo:render', () => page.click('#turbo-morph-submit'));
    await audit('the dialog sent back with its errors');

    assert.deepEqual(errors, [], 'errors in the page');
    console.log(`OK ${url} (Turbo)`);
} finally {
    await browser.close();
}
