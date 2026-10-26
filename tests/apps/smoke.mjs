// Smoke test of a test application: node smoke.mjs <url> [chrome path]
// The page must show the dropdown controller of the bundle and load its stylesheet; the design kit page must have no
// accessibility violation (axe-core), and its components work in a real browser (buttons, alerts, menus, tooltips,
// dialogs, toasts, collapsibles, tabs).
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
    page.on('console', (message) => message.type() === 'error' && !message.location().url?.endsWith('/favicon.ico') && !message.location().url?.includes('/crudit-ui/kit-table-sort') && errors.push(`console: ${message.text()}`));
    // A navigation answered with 204 (the demos of the kit that send a form) is aborted by Chrome: the page stays. A
    // search of the command palette is cancelled by the next one.
    page.on('requestfailed', (request) => !request.url().includes('/crudit-ui/kit-sent') && !request.url().includes('/crudit-ui/kit-search') && !request.url().includes('/crudit-ui/kit-table-sort') && errors.push(`request failed: ${request.url()}`));
    page.on('response', (response) => response.status() >= 400 && !response.url().endsWith('/favicon.ico') && !response.url().includes('/crudit-ui/kit-table-sort') && errors.push(`${response.status()}: ${response.url()}`));

    await page.goto(url, {waitUntil: 'networkidle0'});

    // The stylesheet of the bundle is loaded, and the breakpoints are exposed to the JavaScript.
    const md = await page.evaluate(() => getComputedStyle(document.documentElement).getPropertyValue('--breakpoint-md').trim());
    assert.equal(md, '768px', 'the stylesheet of the bundle is not loaded');

    // The project stylesheet wins over the layer of the bundle.
    const primary = await page.evaluate(() => getComputedStyle(document.documentElement).getPropertyValue('--brand').trim());
    assert.equal(primary, '#b3261e', 'the project must override the variables of the bundle');

    // The Stimulus controller of the bundle works.
    const button = await page.waitForSelector('.crudit-ui-dropdown .crudit-ui-btn');
    const expanded = () => page.evaluate((b) => b.getAttribute('aria-expanded'), button);
    assert.equal(await expanded(), 'false');
    await button.click();
    assert.equal(await expanded(), 'true', 'the dropdown does not open: is the controller registered?');
    await page.keyboard.press('Escape');
    assert.equal(await expanded(), 'false', 'Escape does not close the dropdown');

    // Bootstrap, in its layer between the two layers of the bundle (assets/styles/layers.css): the components of the
    // bundle win over its reboot, its classes win over the reset of the bundle.
    const style = (selector, property) => page.evaluate((s, p) => getComputedStyle(document.querySelector(s))[p], selector, property);
    assert.equal(await style('.crudit-ui-dropdown .crudit-ui-btn', 'paddingLeft'), '12px', 'the reboot of Bootstrap must not win over a component of the bundle');
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
    const rows = await page.$$eval('#kit-many-tabs [role="tab"]', (tabs) => new Set(tabs.map((tab) => tab.offsetTop)).size);
    assert.ok(rows > 1, 'the tabs do not wrap on a phone');

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

    // A disclosure opens on a click and keeps the focus on its button; the one inside a sub-menu does not close its
    // parent. Each change dispatches "toggle".
    await page.setViewport({width: 1280, height: 800});
    await page.goto(new URL('crudit-ui/kit', url).href, {waitUntil: 'networkidle0'});
    await page.evaluate(() => {
        window.toggles = [];
        document.addEventListener('2lenet--crudit-ui-bundle--disclosure:toggle', (event) => window.toggles.push([event.target.id, event.detail.open]));
    });
    await page.click('#kit-contacts .crudit-ui-disclosure__toggle');
    await page.click('#kit-planning > .crudit-ui-disclosure__toggle');
    await page.click('#kit-planning-settings .crudit-ui-disclosure__toggle');
    const disclosures = await page.evaluate(() => {
        const state = (id) => {
            const toggle = document.querySelector(`#${id} > .crudit-ui-disclosure__toggle, #${id} > .crudit-ui-disclosure__heading > .crudit-ui-disclosure__toggle`);
            const panel = document.getElementById(toggle.getAttribute('aria-controls'));

            return [toggle.getAttribute('aria-expanded'), getComputedStyle(panel).display !== 'none'];
        };

        return {
            contacts: state('kit-contacts'),
            menu: state('kit-planning'),
            submenu: state('kit-planning-settings'),
            focus: document.activeElement?.closest('.crudit-ui-disclosure')?.id,
            toggles: window.toggles,
        };
    });
    assert.deepEqual(disclosures, {
        contacts: ['true', true],
        menu: ['true', true],
        submenu: ['true', true],
        focus: 'kit-planning-settings',
        toggles: [['kit-contacts', true], ['kit-planning', true], ['kit-planning-settings', true]],
    }, 'disclosure: is the disclosure controller registered?');
    await page.click('#kit-planning-settings .crudit-ui-disclosure__toggle');
    assert.equal(await page.$eval('#kit-planning > .crudit-ui-disclosure__toggle', (toggle) => toggle.getAttribute('aria-expanded')), 'true', 'closing a sub-menu closes its parent');

    // Printed: every panel open, no chevron.
    await page.emulateMediaType('print');
    const printed = await page.evaluate(() => ({
        closed: [...document.querySelectorAll('.crudit-ui-disclosure__panel')].filter((panel) => getComputedStyle(panel).display === 'none').length,
        chevrons: [...document.querySelectorAll('.crudit-ui-disclosure__chevron')].filter((chevron) => getComputedStyle(chevron).display !== 'none').length,
    }));
    await page.emulateMediaType(null);
    assert.deepEqual(printed, {closed: 0, chevrons: 0}, 'printed: a panel stays closed, or a chevron is printed');

    // Tabs on the keyboard: Tab reaches the selected tab only, the arrows select at once and loop, Home and End.
    // The address follows the tab without a new entry in the history, and a reload opens the same tab.
    await page.evaluate(() => {
        window.changes = [];
        document.addEventListener('2lenet--crudit-ui-bundle--tabs:change', (event) => window.changes.push(event.detail.id));
    });
    const history = await page.evaluate(() => window.history.length);
    await page.focus('#kit-tabs-tab-general');
    const selected = async () => page.evaluate(() => ({
        tab: document.querySelector('#kit-tabs [aria-selected="true"]').id,
        focus: document.activeElement.id,
        panels: [...document.querySelectorAll('#kit-tabs [role="tabpanel"]:not([hidden])')].map((panel) => panel.id),
    }));
    const path = [];
    for (const key of ['ArrowRight', 'End', 'ArrowRight', 'ArrowLeft', 'Home']) {
        await page.keyboard.press(key);
        path.push((await selected()).tab);
    }
    assert.deepEqual(path, ['kit-tabs-tab-parts', 'kit-tabs-tab-history', 'kit-tabs-tab-general', 'kit-tabs-tab-history', 'kit-tabs-tab-general'], 'tabs on the keyboard: is the tabs controller registered?');
    await page.click('#kit-tabs-tab-parts');
    assert.deepEqual(await selected(), {tab: 'kit-tabs-tab-parts', focus: 'kit-tabs-tab-parts', panels: ['kit-tabs-panel-parts']});
    assert.deepEqual(await page.evaluate(() => window.changes), ['parts', 'history', 'general', 'history', 'general', 'parts'], 'tabs: "change" is dispatched once per change');
    assert.deepEqual(await page.evaluate(() => [new URL(window.location.href).searchParams.get('kit-tab'), window.history.length]), ['parts', history], 'tabs: the address does not follow the tab, or the history grows');
    await page.reload({waitUntil: 'networkidle0'});
    assert.equal((await selected()).tab, 'kit-tabs-tab-parts', 'tabs: a reload does not open the tab of the address');

    // Right to left, → goes to the previous tab, as on the screen.
    await page.goto(new URL('crudit-ui/kit/ar', url).href, {waitUntil: 'networkidle0'});
    await page.focus('#kit-tabs-tab-general');
    await page.keyboard.press('ArrowRight');
    assert.equal((await selected()).tab, 'kit-tabs-tab-history', 'tabs right to left: → must go to the previous tab');

    // Dropdowns of the design kit (controller dropdown): Escape, the choice of an item, a disabled item, a click
    // outside and the focus leaving close it or not, and give the focus back to the button.
    await page.setViewport({width: 1280, height: 800});
    await page.goto(new URL('crudit-ui/kit', url).href, {waitUntil: 'networkidle0'});
    const menu = (id) => page.evaluate((menuId) => ({
        expanded: document.querySelector(`#${menuId} > button`).getAttribute('aria-expanded'),
        hidden: document.getElementById(`${menuId}-panel`).hidden,
        focus: document.activeElement?.closest('.crudit-ui-dropdown__panel') ? 'panel' : document.activeElement === document.querySelector(`#${menuId} > button`) ? 'button' : document.activeElement?.tagName,
    }), id);
    await page.click('#kit-menu > button');
    assert.deepEqual(await menu('kit-menu'), {expanded: 'true', hidden: false, focus: 'button'}, 'the dropdown does not open');
    await page.keyboard.press('Tab');
    assert.equal((await menu('kit-menu')).focus, 'panel', 'Tab does not go into the panel');
    await page.keyboard.press('Escape');
    assert.deepEqual(await menu('kit-menu'), {expanded: 'false', hidden: true, focus: 'button'}, 'Escape does not close the dropdown, or does not give the focus back');

    await page.click('#kit-menu > button');
    await page.click('#kit-menu-panel [aria-disabled="true"]');
    assert.equal((await menu('kit-menu')).hidden, false, 'a disabled item closes the dropdown');
    await page.click('#kit-menu-panel button.crudit-ui-menu__item:not([aria-disabled])');
    assert.deepEqual(await menu('kit-menu'), {expanded: 'false', hidden: true, focus: 'button'}, 'the choice of an item does not close the dropdown');

    // A link item is followed (with Turbo, the address changes a little after the click).
    await page.click('#kit-menu > button');
    await page.click('#kit-menu-panel a.crudit-ui-menu__item');
    await page.waitForFunction(() => location.hash === '#kit-menus', {timeout: 2000}).catch(() => null);
    assert.deepEqual([await page.evaluate(() => location.hash), (await menu('kit-menu')).hidden], ['#kit-menus', true], 'a link of a menu is not followed');

    // The focus given by the action of an item (a dialog it opens) is not taken back by the dropdown.
    await page.click('#kit-menu > button');
    const focusKept = await page.evaluate(() => {
        const field = document.body.appendChild(Object.assign(document.createElement('input'), {id: 'kit-menu-field', 'aria-label': 'Field'}));
        document.querySelectorAll('#kit-menu-panel button.crudit-ui-menu__item:not([aria-disabled])')[0].addEventListener('click', () => field.focus(), {once: true});
        document.querySelectorAll('#kit-menu-panel button.crudit-ui-menu__item:not([aria-disabled])')[0].click();
        const focus = document.activeElement === field;
        field.remove();

        return focus;
    });
    assert.equal(focusKept, true, 'the dropdown takes back the focus given by the action of an item');

    // Escape closes the dropdown, not the native dialog around it; the next one closes the dialog.
    const inDialog = await page.evaluate(async () => {
        const dialog = document.body.appendChild(Object.assign(document.createElement('dialog'), {id: 'kit-menu-dialog'}));
        dialog.setAttribute('aria-label', 'Dialog');
        dialog.append(document.getElementById('kit-menu-more'));
        await new Promise((resolve) => setTimeout(resolve));
        dialog.showModal();
        document.querySelector('#kit-menu-more > button').click();

        return document.getElementById('kit-menu-more-panel').hidden;
    });
    assert.equal(inDialog, false, 'the dropdown does not open in a dialog');
    await page.keyboard.press('Escape');
    assert.deepEqual(await page.evaluate(() => [document.getElementById('kit-menu-dialog').open, document.getElementById('kit-menu-more-panel').hidden]), [true, true], 'Escape in a dropdown closes the dialog around it');
    await page.keyboard.press('Escape');
    assert.equal(await page.evaluate(() => document.getElementById('kit-menu-dialog').open), false, 'Escape does not close the dialog');
    await page.reload({waitUntil: 'networkidle0'});

    // A dropdown in the panel of another one: the choice of one of its items closes it only, not the outer one.
    const nested = await page.evaluate(() => {
        document.getElementById('kit-menu-panel').append(document.getElementById('kit-menu-more'));

        return new Promise((resolve) => setTimeout(() => {
            document.querySelector('#kit-menu > button').click();
            document.querySelector('#kit-menu-more > button').click();
            document.querySelector('#kit-menu-more-panel .crudit-ui-menu__item').click();
            resolve({
                inner: document.getElementById('kit-menu-more-panel').hidden,
                outer: document.getElementById('kit-menu-panel').hidden,
                focus: document.activeElement === document.querySelector('#kit-menu-more > button'),
            });
        }));
    });
    assert.deepEqual(nested, {inner: true, outer: false, focus: true}, 'the choice in a dropdown inside another one closes the outer one');
    await page.reload({waitUntil: 'networkidle0'});

    // The markup of a project, without the class crudit-ui-dropdown: the choice of an item closes it all the same.
    const bare = await page.evaluate(() => {
        const root = document.getElementById('kit-menu-more');
        root.classList.remove('crudit-ui-dropdown');
        root.querySelector(':scope > button').click();
        root.querySelector('.crudit-ui-menu__item').click();

        return {closed: document.getElementById('kit-menu-more-panel').hidden, focus: document.activeElement === root.querySelector(':scope > button')};
    });
    assert.deepEqual(bare, {closed: true, focus: true}, 'the choice of an item does not close a dropdown without its class');
    await page.reload({waitUntil: 'networkidle0'});

    // An item that sends a form puts the button of the dropdown in loading, and back if the sending is cancelled.
    const submitted = await page.evaluate(async () => {
        const form = document.getElementById('kit-menu-form');
        const button = document.querySelector('#kit-menu-submit > button');
        form.addEventListener('submit', (event) => event.preventDefault(), {once: true});
        button.click();
        document.querySelector('#kit-menu-submit-panel button[type="submit"]').click();
        const busy = button.getAttribute('aria-busy');
        await new Promise((resolve) => setTimeout(resolve, 20));

        return {busy, after: button.getAttribute('aria-busy'), closed: document.getElementById('kit-menu-submit-panel').hidden};
    });
    assert.deepEqual(submitted, {busy: 'true', after: null, closed: true}, 'the button of the dropdown is not in loading while its item sends the form');

    // The demo of the kit: the form is sent to a route that answers 204, the button stays in loading; Reset reloads
    // the page. With Turbo, which sends the form, the button goes back to normal once the answer has come.
    const sendBusy = () => page.evaluate(() => document.querySelector('#kit-menu-submit > button').getAttribute('aria-busy'));
    const turbo = await page.evaluate(() => 'Turbo' in window);
    await page.evaluate(() => {
        window.demoStates = [];
        const button = document.querySelector('#kit-menu-submit > button');
        ['busy', 'idle'].forEach((state) => button.addEventListener(`2lenet--crudit-ui-bundle--button:${state}`, () => window.demoStates.push(state)));
    });
    await page.click('#kit-menu-submit > button');
    await page.click('#kit-menu-submit-panel button[type="submit"]');
    if (turbo) {
        await page.waitForFunction(() => window.demoStates.length === 2, {timeout: 5000}).catch(() => null);
        assert.deepEqual([await page.evaluate(() => window.demoStates), await sendBusy()], [['busy', 'idle'], null], 'the demo of the kit does not go back to normal once Turbo has sent the form');
    } else {
        assert.equal(await sendBusy(), 'true', 'the demo of the kit is not in loading');
    }
    await Promise.all([page.waitForNavigation({waitUntil: 'networkidle0'}), page.click('button[form="kit-menu-reset"]')]);
    assert.equal(await sendBusy(), null, 'Reset does not play the demo of the kit again');

    await page.click('#kit-menu > button');
    await page.mouse.click(5, 5);
    assert.equal((await menu('kit-menu')).hidden, true, 'a click outside does not close the dropdown');

    // The focus leaving the panel closes it: Tab from its last item. Opened with the keyboard, as a user who tabs.
    await page.focus('#kit-menu-more > button');
    await page.keyboard.press('Enter');
    assert.equal((await menu('kit-menu-more')).hidden, false, 'Enter does not open the dropdown');
    await page.focus('#kit-menu-more-panel li:last-child .crudit-ui-menu__item');
    await page.keyboard.press('Tab');
    assert.equal((await menu('kit-menu-more')).hidden, true, 'the focus leaving the dropdown does not close it');

    // Floating, in a container that scrolls: in position fixed, whole on the screen, not cut by its container.
    // Its button is brought on the screen first, as a user scrolls before clicking: page.click() scrolls and clicks
    // in the same frame, and the event "scroll", which closes a floating dropdown, would come after the opening (with
    // Turbo, Reset does not keep the anchor: the page is back at the top).
    await page.$eval('#kit-menu-floating-1 > button', (button) => button.scrollIntoView({block: 'center'}));
    await page.evaluate(() => new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve))));
    await page.click('#kit-menu-floating-1 > button');
    const floating = await page.evaluate(() => {
        const panel = document.getElementById('kit-menu-floating-1-panel');
        const rect = panel.getBoundingClientRect();
        const scroller = panel.closest('.crudit-ui-kit__scroller').getBoundingClientRect();

        return {position: getComputedStyle(panel).position, inside: rect.left >= 0 && rect.right <= innerWidth, outOfScroller: rect.bottom > scroller.bottom || rect.right > scroller.right || rect.left < scroller.left};
    });
    assert.deepEqual(floating, {position: 'fixed', inside: true, outOfScroller: true}, 'the floating dropdown is not placed');
    // A resize of the window (the virtual keyboard of a phone) keeps it open, next to its button.
    await page.setViewport({width: 1200, height: 700});
    await new Promise((resolve) => setTimeout(resolve, 100));
    const followed = await page.evaluate(() => {
        const button = document.querySelector('#kit-menu-floating-1 > button').getBoundingClientRect();
        const panel = document.getElementById('kit-menu-floating-1-panel');

        const rect = panel.getBoundingClientRect();

        // Below its button, or above when there is more room there.
        return {open: !panel.hidden, near: Math.abs(rect.top - button.bottom) < 10 || Math.abs(rect.bottom - button.top) < 10};
    });
    assert.deepEqual(followed, {open: true, near: true}, 'a resize of the window closes the floating dropdown, or leaves it away from its button');
    await page.keyboard.press('Escape');
    await page.setViewport({width: 1280, height: 800});

    // Followed while the focus is in it, the floating panel closes when its button leaves the screen, unless a field
    // of the panel has the focus (the virtual keyboard).
    const scrolledAway = (withField) => page.evaluate(async (field) => {
        const frame = () => new Promise((resolve) => requestAnimationFrame(() => setTimeout(resolve)));
        const root = document.getElementById('kit-menu-floating-1');
        root.scrollIntoView({block: 'center'});
        await frame();
        root.querySelector(':scope > button').click();
        const panel = document.getElementById('kit-menu-floating-1-panel');
        const input = field ? panel.appendChild(Object.assign(document.createElement('input'), {'aria-label': 'Search'})) : null;
        (input ?? panel.querySelector('.crudit-ui-menu__item')).focus();
        // Up: the button is near the end of the page.
        window.scrollBy(0, -window.innerHeight);
        await frame();
        await frame();
        const open = !panel.hidden;
        input?.remove();
        root.querySelector(':scope > button').focus();
        if (open) {
            root.querySelector(':scope > button').click();
        }

        return open;
    }, withField);
    assert.equal(await scrolledAway(false), false, 'a followed panel stays open when its button leaves the screen');
    assert.equal(await scrolledAway(true), true, 'a followed panel closes while a field of the panel has the focus');

    // Tooltip: shown on hover, hidden by Escape until the pointer moves (controller tooltip).
    const tooltip = () => page.evaluate(() => getComputedStyle(document.querySelector('.crudit-ui-kit__tooltips .crudit-ui-tooltip')).visibility);
    // visibility follows the transition of the opacity: waited for.
    const settled = (visibility) => page.waitForFunction((v) => getComputedStyle(document.querySelector('.crudit-ui-kit__tooltips .crudit-ui-tooltip')).visibility === v, {timeout: 1000}, visibility).then(() => visibility, () => tooltip());
    assert.equal(await tooltip(), 'hidden');
    await page.hover('.crudit-ui-kit__tooltips .crudit-ui-tooltip-host button');
    assert.equal(await settled('visible'), 'visible', 'the tooltip is not shown on hover');
    await page.keyboard.press('Escape');
    assert.equal(await settled('hidden'), 'hidden', 'Escape does not hide the tooltip');
    const box = await (await page.$('.crudit-ui-kit__tooltips .crudit-ui-tooltip-host button')).boundingBox();
    await page.mouse.move(box.x + 2, box.y + 2);
    assert.equal(await settled('visible'), 'visible', 'the tooltip does not come back when the pointer moves');

    // A floating tooltip follows its host when the page scrolls. Shown by the focus: the pointer, which does not move,
    // would leave the host as the page scrolls.
    await page.focus('.crudit-ui-kit__tooltips .crudit-ui-tooltip-host:has(.crudit-ui-tooltip--floating) button');
    const gap = () => page.evaluate(() => {
        const host = document.querySelector('.crudit-ui-kit__tooltips .crudit-ui-tooltip-host:has(.crudit-ui-tooltip--floating)');

        return Math.round(host.querySelector('.crudit-ui-tooltip').getBoundingClientRect().top - host.getBoundingClientRect().top);
    });
    const before = await gap();
    const scrolled = await page.evaluate(() => {
        const top = window.scrollY;
        window.scrollBy(0, 40);

        return window.scrollY - top;
    });
    assert.equal(scrolled, 40, 'the page does not scroll: the test of the floating tooltip checks nothing');
    await new Promise((resolve) => setTimeout(resolve, 100));
    assert.equal(await gap(), before, 'a floating tooltip does not follow its host when the page scrolls');

    // Dialogs of the design kit (controller dialog): opened modal by a button (commandfor), the focus on the element
    // with autofocus, kept in the dialog, given back to the button by Escape, the close button and the backdrop.
    await page.goto(new URL('crudit-ui/kit', url).href, {waitUntil: 'networkidle0'});
    const dialog = (id) => page.evaluate((dialogId) => {
        const element = document.getElementById(dialogId);

        return {
            open: element.open,
            modal: element.matches(':modal'),
            focus: element.contains(document.activeElement) ? document.activeElement.textContent.trim() || document.activeElement.id : document.activeElement?.id,
            locked: getComputedStyle(document.documentElement).overflow === 'hidden',
        };
    }, id);
    const openDialog = async (opener, id) => {
        await page.click(`#${opener}`);
        await page.waitForFunction((dialogId) => document.getElementById(dialogId).open, {timeout: 1000}, id);
    };
    await openDialog('kit-dialog-confirm-open', 'kit-dialog-confirm');
    assert.deepEqual(await dialog('kit-dialog-confirm'), {open: true, modal: true, focus: 'Cancel', locked: true}, 'the confirmation is not opened modal, with the focus on Cancel');
    // Tab never reaches the page under the dialog (the browser may give the focus to its own interface on the way).
    const tabbed = [];
    for (let index = 0; index < 5; index++) {
        await page.keyboard.press('Tab');
        tabbed.push(await page.evaluate(() => document.getElementById('kit-dialog-confirm').contains(document.activeElement) || document.activeElement === document.body));
    }
    assert.deepEqual(tabbed, [true, true, true, true, true], 'the focus leaves the dialog');
    await page.keyboard.press('Escape');
    assert.deepEqual(await dialog('kit-dialog-confirm'), {open: false, modal: false, focus: 'kit-dialog-confirm-open', locked: false}, 'Escape does not close the dialog, or does not give the focus back');

    // The buttons of the footer close it (command="close"), with their value; the close button and the backdrop too.
    await openDialog('kit-dialog-confirm-open', 'kit-dialog-confirm');
    await page.click('#kit-dialog-confirm .crudit-ui-dialog__footer .crudit-ui-btn--danger');
    assert.deepEqual([await page.evaluate(() => document.getElementById('kit-dialog-confirm').returnValue), (await dialog('kit-dialog-confirm')).focus], ['delete', 'kit-dialog-confirm-open'], 'a button of the footer does not close the dialog');
    await openDialog('kit-dialog-confirm-open', 'kit-dialog-confirm');
    await page.click('#kit-dialog-confirm .crudit-ui-dialog__close');
    assert.deepEqual(await dialog('kit-dialog-confirm'), {open: false, modal: false, focus: 'kit-dialog-confirm-open', locked: false}, 'the close button does not close the dialog');
    await openDialog('kit-dialog-confirm-open', 'kit-dialog-confirm');
    await page.mouse.click(5, 5);
    assert.equal((await dialog('kit-dialog-confirm')).open, false, 'a click on the backdrop does not close the dialog');
    // A click in the dialog, or a press in it released on the backdrop (a selection of text), keeps it open.
    await openDialog('kit-dialog-confirm-open', 'kit-dialog-confirm');
    const titleBox = await (await page.$('#kit-dialog-confirm .crudit-ui-dialog__title')).boundingBox();
    await page.mouse.click(titleBox.x + 2, titleBox.y + 2);
    await page.mouse.move(titleBox.x + 2, titleBox.y + 2);
    await page.mouse.down();
    await page.mouse.move(5, 5);
    await page.mouse.up();
    assert.equal((await dialog('kit-dialog-confirm')).open, true, 'a click in the dialog, or a selection that ends on the backdrop, closes it');

    // A project keeps it open by cancelling "cancel" (a form with changes): close button and backdrop. Real clicks:
    // Chrome makes "cancel" of requestClose() cancelable only after an action of the user.
    await page.evaluate(() => {
        window.kitKeep = (event) => event.preventDefault();
        document.getElementById('kit-dialog-confirm').addEventListener('cancel', window.kitKeep);
    });
    await page.click('#kit-dialog-confirm .crudit-ui-dialog__close');
    const keptByButton = (await dialog('kit-dialog-confirm')).open;
    await page.mouse.click(5, 5);
    const keptByBackdrop = (await dialog('kit-dialog-confirm')).open;
    await page.evaluate(() => document.getElementById('kit-dialog-confirm').removeEventListener('cancel', window.kitKeep));
    await page.click('#kit-dialog-confirm .crudit-ui-dialog__close');
    assert.deepEqual([keptByButton, keptByBackdrop, (await dialog('kit-dialog-confirm')).open], [true, true, false], 'the close button or the backdrop does not fire a cancelable "cancel"');

    // Events of the controller, and the focus put on <main> when the button that opened the dialog is gone.
    const lost = await page.evaluate(async () => {
        const events = [];
        const element = document.getElementById('kit-dialog-confirm');
        element.addEventListener('2lenet--crudit-ui-bundle--dialog:open', () => events.push('open'));
        element.addEventListener('2lenet--crudit-ui-bundle--dialog:close', () => events.push('close'));
        const main = document.body.appendChild(document.createElement('main'));
        const opener = main.appendChild(Object.assign(document.createElement('button'), {type: 'button', textContent: 'Delete'}));
        opener.focus();
        element.showModal();
        opener.remove();
        element.close();
        await new Promise((resolve) => setTimeout(resolve, 20));
        const focus = document.activeElement === main;
        const outline = getComputedStyle(main).outlineStyle;
        main.remove();

        return {events, focus, outline};
    });
    assert.deepEqual(lost, {events: ['open', 'close'], focus: true, outline: 'none'}, 'the events of the dialog, or the focus put on <main> (without a ring) when its button is gone');

    // A form method="dialog" closes it; its submit button does not stay in loading (nothing is sent).
    await openDialog('kit-dialog-form-open', 'kit-dialog-form');
    assert.equal((await dialog('kit-dialog-form')).focus, 'Close', 'without autofocus, the focus does not go to the first focusable element');
    await page.click('#kit-dialog-form .crudit-ui-btn--primary');
    assert.equal((await dialog('kit-dialog-form')).open, true, 'the dialog closes with a required field empty');
    await page.type('#kit-dialog-date', '12122026');
    await page.click('#kit-dialog-form .crudit-ui-btn--primary');
    const submittedForm = await page.evaluate(() => ({
        open: document.getElementById('kit-dialog-form').open,
        busy: document.querySelector('#kit-dialog-form .crudit-ui-btn--primary').getAttribute('aria-busy'),
        focus: document.activeElement.id,
    }));
    assert.deepEqual(submittedForm, {open: false, busy: null, focus: 'kit-dialog-form-open'}, 'the form method="dialog" does not close the dialog, or leaves its button in loading');

    // The drawer: on the inline end side, the whole height; Escape closes its dropdown first, then the drawer.
    const drawer = async () => {
        await openDialog('kit-drawer-open', 'kit-drawer');
        await page.waitForFunction(() => getComputedStyle(document.getElementById('kit-drawer')).translate === 'none', {timeout: 1000});

        return page.evaluate(() => {
            const rect = document.getElementById('kit-drawer').getBoundingClientRect();

            return {end: document.dir === 'rtl' ? Math.round(rect.left) === 0 : Math.round(rect.right) === document.documentElement.clientWidth, height: Math.round(rect.height) === innerHeight};
        });
    };
    assert.deepEqual(await drawer(), {end: true, height: true}, 'the drawer is not on the inline end side, on the whole height');
    await page.click('#kit-drawer-saved > button');
    await page.keyboard.press('Escape');
    assert.deepEqual([(await dialog('kit-drawer')).open, await page.evaluate(() => document.getElementById('kit-drawer-saved-panel').hidden)], [true, true], 'Escape in a dropdown closes the drawer around it');
    await page.keyboard.press('Escape');
    assert.equal((await dialog('kit-drawer')).open, false, 'Escape does not close the drawer');

    // The value "open": shown modal when the dialog connects (a form sent back with its errors).
    const atLoad = await page.evaluate(async () => {
        const element = document.getElementById('kit-dialog-preview').cloneNode(true);
        element.id = 'kit-dialog-at-load';
        element.classList.remove('crudit-ui-kit__dialog');
        element.setAttribute('data-2lenet--crudit-ui-bundle--dialog-open-value', 'true');
        document.body.append(element);
        await new Promise((resolve) => setTimeout(resolve, 20));
        const state = {modal: element.matches(':modal'), value: element.hasAttribute('data-2lenet--crudit-ui-bundle--dialog-open-value')};
        // Set again on the element, connected (a morph of Turbo).
        element.close();
        element.setAttribute('data-2lenet--crudit-ui-bundle--dialog-open-value', 'true');
        await new Promise((resolve) => setTimeout(resolve, 20));
        state.morphed = element.matches(':modal');
        element.remove();

        return state;
    });
    assert.deepEqual(atLoad, {modal: true, value: false, morphed: true}, 'the value "open" does not show the dialog when it connects, or when it is set again');

    // Accessibility of the open dialogs, and the drawer in right to left.
    const auditOpen = async (path, opener, id) => {
        await page.goto(new URL(path, url).href, {waitUntil: 'networkidle0'});
        await openDialog(opener, id);
        // Once opened: during its animation, the colours are blended with the backdrop.
        await page.waitForFunction((dialogId) => document.getElementById(dialogId).getAnimations().length === 0, {timeout: 1000}, id);
        await page.addScriptTag({content: axeSource});
        const violations = await page.evaluate(async () => (await window.axe.run(document, {runOnly: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa']})).violations
            .map((violation) => `${violation.id} (${violation.nodes.map((node) => node.target.join(' ')).join(', ')}): ${violation.help}`));
        assert.deepEqual(violations, [], `accessibility of the dialog ${id} open`);
    };
    await auditOpen('crudit-ui/kit', 'kit-dialog-confirm-open', 'kit-dialog-confirm');
    await auditOpen('crudit-ui/kit', 'kit-drawer-open', 'kit-drawer');
    await page.goto(new URL('crudit-ui/kit/ar', url).href, {waitUntil: 'networkidle0'});
    assert.deepEqual(await drawer(), {end: true, height: true}, 'the drawer is not on the inline end side in right to left');

    // Toasts of the design kit (controller toasts). Those of the page (?toast=, as flash messages after a redirect),
    // announced a little after the load, by the polite or the assertive live region.
    await page.setViewport({width: 1280, height: 800});
    const toasts = () => page.evaluate(() => Array.from(document.querySelectorAll('#kit-toasts-region > .crudit-ui-toast'))
        .map((toast) => toast.querySelector('.crudit-ui-toast__message').textContent));
    const announced = (live) => page.evaluate((target) => document.querySelector(`#kit-toasts-region [data-2lenet--crudit-ui-bundle--toasts-target="${target}"]`).textContent, live);
    await page.goto(new URL('crudit-ui/kit?toast=success', url).href, {waitUntil: 'networkidle0'});
    assert.deepEqual(await toasts(), ['Success: The visit V-0412 is saved.']);
    await page.waitForFunction(() => document.querySelector('#kit-toasts-region [aria-live="polite"]').textContent !== '', {timeout: 2000});
    assert.deepEqual([await announced('polite'), await announced('assertive')], ['Success: The visit V-0412 is saved.', ''], 'a toast of the page is not announced');
    // On the top inline end side.
    const place = () => page.evaluate(() => {
        const rect = document.getElementById('kit-toasts-region').getBoundingClientRect();
        const width = document.documentElement.clientWidth;

        return {top: rect.top > 0 && rect.top < 120, end: document.dir === 'rtl' ? rect.left < 40 : width - rect.right < 40, width: Math.round(rect.width)};
    });
    assert.deepEqual(await place(), {top: true, end: true, width: 448}, 'the toasts are not on the top inline end side');

    // Five toasts: four kept, the oldest that is not an error goes; an error is announced at once (assertive).
    await page.goto(new URL('crudit-ui/kit?toast=all', url).href, {waitUntil: 'networkidle0'});
    assert.deepEqual(await toasts(), [
        'Error: The server refused the record. Try again later.',
        'Information: The export has started: you will be told when it is ready.',
        'Warning: The visit is saved, but its planned date is 5 days late.',
        'Success: The visit V-0412 is saved.',
    ], 'beyond four toasts, the oldest that is not an error does not go');
    await page.waitForFunction(() => document.querySelector('#kit-toasts-region [aria-live="assertive"]').textContent !== '', {timeout: 2000});
    assert.equal(await announced('assertive'), 'Error: The server refused the record. Try again later.', 'an error is not announced at once');
    await page.addScriptTag({content: axeSource});
    const toastViolations = await page.evaluate(async () => (await window.axe.run(document.getElementById('kit-toasts-region'), {runOnly: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa']})).violations.map((violation) => violation.id));
    assert.deepEqual(toastViolations, [], 'accessibility of the toasts');

    // The JavaScript of the page: the event crudit-ui:toast, a text never read as HTML. With a short duration: removed
    // after it, except an error, and not while the pointer is on the region.
    await page.goto(new URL('crudit-ui/kit', url).href, {waitUntil: 'networkidle0'});
    const show = (detail) => page.evaluate((toast) => document.dispatchEvent(new CustomEvent('crudit-ui:toast', {detail: toast})), detail);
    await page.evaluate(() => document.getElementById('kit-toasts-region').setAttribute('data-2lenet--crudit-ui-bundle--toasts-duration-value', '1500'));
    await show({message: '<b>Saved</b>', tone: 'success'});
    await show({message: 'Refused.', tone: 'danger'});
    assert.deepEqual(await toasts(), ['Success: <b>Saved</b>', 'Error: Refused.'], 'the event crudit-ui:toast does not show a toast');
    assert.equal(await page.$('#kit-toasts-region b'), null, 'the message of a toast is read as HTML');
    // Once they have slid in: the pointer on the middle of a toast, not in the gap between two.
    await page.waitForFunction(() => document.getElementById('kit-toasts-region').getAnimations({subtree: true}).length === 0, {timeout: 1000});
    const errorBox = await (await page.$('#kit-toasts-region .crudit-ui-toast--danger')).boundingBox();
    await page.mouse.move(errorBox.x + errorBox.width / 2, errorBox.y + errorBox.height / 2);
    await new Promise((resolve) => setTimeout(resolve, 2000));
    assert.equal((await toasts()).length, 2, 'a toast is removed while the pointer is on the region');
    await page.mouse.move(5, 5);
    await page.waitForFunction(() => document.querySelectorAll('#kit-toasts-region > .crudit-ui-toast').length === 1, {timeout: 3000});
    assert.deepEqual(await toasts(), ['Error: Refused.'], 'a toast is not removed after its duration, or an error is');

    // Closed by its button, from the keyboard: the focus goes to the next toast, then to the container.
    await show({message: 'Started.', tone: 'info', persistent: true});
    await page.focus('#kit-toasts-region .crudit-ui-toast--danger .crudit-ui-toast__close');
    await page.keyboard.press('Enter');
    const afterClose = await page.evaluate(() => [document.activeElement.closest('.crudit-ui-toast')?.querySelector('.crudit-ui-toast__message').textContent, document.querySelectorAll('#kit-toasts-region > .crudit-ui-toast').length]);
    assert.deepEqual(afterClose, ['Information: Started.', 1], 'the close button does not remove the toast, or the focus is lost');
    await page.keyboard.press('Enter');
    assert.deepEqual(await page.evaluate(() => [document.activeElement.className, document.querySelectorAll('#kit-toasts-region > .crudit-ui-toast').length]), ['crudit-ui-kit', 0], 'the focus is lost when the last toast is closed');

    // A toast added while a modal dialog is open is shown in it (the page under it is inert): seen, read and closed;
    // the region goes back to its place when the dialog closes. Sent during the opening of the dialog: moved once it
    // is over, not placed and cut by the dialog. The live regions are emptied when the region moves (read again).
    await show({message: 'Kept.', tone: 'info', persistent: true});
    await page.waitForFunction(() => document.querySelector('#kit-toasts-region [aria-live="polite"]').textContent.includes('Kept.'), {timeout: 2000});
    await openDialog('kit-dialog-confirm-open', 'kit-dialog-confirm');
    await show({message: 'Not deleted.', tone: 'danger'});
    await page.waitForFunction(() => document.querySelector('#kit-toasts-region [aria-live="assertive"]').textContent.includes('Not deleted.'), {timeout: 2000});
    assert.deepEqual([await announced('polite'), await announced('assertive')], ['', 'Error: Not deleted.'], 'the live regions are not emptied when the region moves');
    assert.deepEqual(await place(), {top: true, end: true, width: 448}, 'a toast sent during the opening of a dialog is not in its place');
    await page.evaluate(() => document.querySelector('#kit-toasts-region .crudit-ui-toast--info').remove());
    const toastInDialog = await page.evaluate(() => {
        const toast = document.querySelector('#kit-toasts-region .crudit-ui-toast');
        const rect = toast.getBoundingClientRect();

        return {
            moved: document.getElementById('kit-dialog-confirm').contains(toast),
            shown: toast.contains(document.elementFromPoint(rect.left + rect.width / 2, rect.top + rect.height / 2)),
        };
    });
    assert.deepEqual(toastInDialog, {moved: true, shown: true}, 'a toast added while a dialog is open is not shown in it');
    const tree = JSON.stringify(await page.accessibility.snapshot());
    assert.ok(tree.includes('Error: Not deleted.'), 'a toast in a dialog is not in the accessibility tree');
    await page.click('#kit-toasts-region .crudit-ui-toast__close');
    assert.equal((await dialog('kit-dialog-confirm')).open, true, 'closing a toast closes the dialog');
    await show({message: 'Saved.', tone: 'success', persistent: true});
    await page.keyboard.press('Escape');
    await page.waitForFunction(() => document.getElementById('kit-toasts-region').parentElement.closest('#kit-toasts'), {timeout: 1000});
    assert.deepEqual(await toasts(), ['Success: Saved.'], 'the region does not go back to its place with its toasts');

    // Removed with the dialog it is in (a row deleted with its dialog): back in its place. Emptied before Turbo
    // caches the page.
    await openDialog('kit-dialog-form-open', 'kit-dialog-form');
    await show({message: 'Postponed.', tone: 'success', persistent: true});
    await page.waitForFunction(() => document.getElementById('kit-dialog-form').contains(document.getElementById('kit-toasts-region')), {timeout: 1000});
    await page.evaluate(() => document.getElementById('kit-dialog-form').remove());
    await page.waitForFunction(() => document.getElementById('kit-toasts-region')?.parentElement.closest('#kit-toasts'), {timeout: 1000});
    assert.deepEqual(await toasts(), ['Success: Saved.', 'Success: Postponed.'], 'the region is lost with the dialog it was in');
    await page.evaluate(() => document.dispatchEvent(new Event('turbo:before-cache')));
    assert.deepEqual(await toasts(), [], 'the toasts are not removed before Turbo caches the page');

    // Two regions (a page that adds its own to a layout that has one): the first one of the page takes the event,
    // the one in the open dialog while it is open, whatever the order of their listeners.
    await page.evaluate(() => {
        const first = document.getElementById('kit-toasts-region').cloneNode(true);
        first.id = 'kit-toasts-first';
        document.body.prepend(first);
    });
    const regionOf = async (detail) => {
        await show(detail);

        return page.evaluate(() => Array.from(document.querySelectorAll('.crudit-ui-toasts > .crudit-ui-toast')).map((toast) => `${toast.parentElement.id}: ${toast.querySelector(".crudit-ui-toast__message").textContent.trim()}`));
    };
    assert.deepEqual(await regionOf({message: null, persistent: true}), ['kit-toasts-first: Information:'], 'the first region of the page does not take the event, or a message null is shown');
    await openDialog('kit-dialog-confirm-open', 'kit-dialog-confirm');
    assert.deepEqual(await regionOf({message: 'In the dialog.', persistent: true}), ['kit-toasts-first: Information:', 'kit-toasts-first: Information: In the dialog.'], 'the region of the page does not take the event while a dialog is open');
    await page.waitForFunction(() => document.getElementById('kit-dialog-confirm').contains(document.getElementById('kit-toasts-first')), {timeout: 1000});
    // A toast at once after the dialog closes (the success of a confirmation): the event "close" of the dialog, which
    // puts the region back, comes later. The region still takes it, and goes back to be shown.
    await page.evaluate(() => document.querySelectorAll('.crudit-ui-toasts > .crudit-ui-toast').forEach((toast) => toast.remove()));
    const atClose = await page.evaluate(() => {
        document.getElementById('kit-dialog-confirm').close();
        document.dispatchEvent(new CustomEvent('crudit-ui:toast', {detail: {message: 'Again.', persistent: true}}));

        return Array.from(document.querySelectorAll('.crudit-ui-toasts > .crudit-ui-toast')).map((toast) => toast.parentElement.id);
    });
    assert.deepEqual(atClose, ['kit-toasts-first'], 'the region that takes the event changes once a dialog has opened, or when it closes');
    await page.waitForFunction(() => !document.getElementById('kit-toasts-first').closest('dialog'), {timeout: 1000});
    assert.deepEqual(await page.evaluate(() => document.body.firstElementChild.id), 'kit-toasts-first', 'the region does not go back to its place');
    await page.evaluate(() => document.getElementById('kit-toasts-first').remove());

    // Right to left: on the top left; on a phone: the whole width, less the margins.
    await page.goto(new URL('crudit-ui/kit/ar?toast=success', url).href, {waitUntil: 'networkidle0'});
    assert.deepEqual(await place(), {top: true, end: true, width: 448}, 'the toasts are not on the inline end side in right to left');
    await page.setViewport({width: 360, height: 740, isMobile: true, hasTouch: true});
    await page.goto(new URL('crudit-ui/kit?toast=success', url).href, {waitUntil: 'networkidle0'});
    assert.deepEqual(await place(), {top: true, end: true, width: 328}, 'the toasts do not take the width of a phone');

    // Command palette (controller command-palette): opened by Ctrl K or by its button, results by groups from the server,
    // opened only once chosen.
    await page.setViewport({width: 1280, height: 800});
    await page.goto(new URL('crudit-ui/kit', url).href, {waitUntil: 'networkidle0'});
    const palette = () => page.evaluate(() => {
        const dialog = document.getElementById('kit-search-dialog');
        const input = dialog.querySelector('input');

        return {
            open: dialog.open,
            focus: document.activeElement === input,
            value: input.value,
            expanded: input.getAttribute('aria-expanded'),
            active: input.getAttribute('aria-activedescendant'),
            groups: Array.from(dialog.querySelectorAll('[role="group"]')).map((group) => group.querySelector('p').textContent),
            options: Array.from(dialog.querySelectorAll('[role="option"]')).map((option) => `${option.querySelector('.crudit-ui-palette__title').textContent}${option.getAttribute('aria-selected') === 'true' ? '*' : ''}`),
            marked: Array.from(dialog.querySelectorAll('mark')).map((mark) => mark.textContent),
            help: !dialog.querySelector('.crudit-ui-palette__help').hidden,
            message: dialog.querySelector('.crudit-ui-palette__message').hidden ? null : dialog.querySelector('.crudit-ui-palette__message').textContent,
            status: dialog.querySelector('[role="status"]').textContent,
        };
    });
    const typed = async (text) => {
        await page.keyboard.type(text);
        await page.waitForFunction(() => document.querySelector('#kit-search-dialog [role="option"], #kit-search-dialog .crudit-ui-palette__message:not([hidden])'), {timeout: 3000});
    };
    assert.equal((await palette()).open, false);
    await page.keyboard.down('Control');
    await page.keyboard.press('k');
    await page.keyboard.up('Control');
    assert.deepEqual(
        (({open, focus, help}) => ({open, focus, help}))(await palette()),
        {open: true, focus: true, help: true},
        'Ctrl K does not open the palette, or the focus is not in the field',
    );
    // Under the minimum, nothing is asked.
    let asked = 0;
    const count = (request) => request.url().includes('/crudit-ui/kit-search') && asked++;
    page.on('request', count);
    await page.keyboard.type('m');
    await new Promise((resolve) => setTimeout(resolve, 500));
    assert.equal(asked, 0, 'a request is sent under the minimum of characters');
    await typed('ar');
    assert.deepEqual(
        (({groups, options, marked, expanded, help}) => ({groups, options, marked, expanded, help}))(await palette()),
        {groups: ['Clients'], options: ['Boulangerie Martin*'], marked: ['Mar'], expanded: 'true', help: false},
        'the results are not shown by groups, with the first one active and the words marked',
    );
    assert.equal(asked, 1, 'one request for the whole word');
    page.off('request', count);
    await page.waitForFunction(() => document.querySelector('#kit-search-dialog [role="status"]').textContent !== '', {timeout: 2000});
    assert.equal((await palette()).status, '1 result available. Up and down arrows to browse.', 'the count is not announced');

    // Several results, the arrows go round, nothing opens before Enter.
    await page.keyboard.down('Control');
    await page.keyboard.press('a');
    await page.keyboard.up('Control');
    await page.keyboard.press('Backspace');
    await typed('ar');
    await page.waitForFunction(() => document.querySelectorAll('#kit-search-dialog [role="option"]').length > 1, {timeout: 3000});
    const names = (await palette()).options.map((name) => name.replace('*', ''));
    assert.ok(names.length > 2, 'the results of "ar"');
    assert.equal((await palette()).options[0].endsWith('*'), true);
    await page.keyboard.press('ArrowUp');
    assert.equal((await palette()).options.at(-1).endsWith('*'), true, 'the up arrow does not go from the first option to the last');
    assert.equal((await palette()).active, await page.evaluate(() => document.querySelector('#kit-search-dialog [aria-selected="true"]').id));
    await page.keyboard.press('ArrowDown');
    assert.equal((await palette()).options[0].endsWith('*'), true, 'the down arrow does not go from the last option to the first');
    await page.keyboard.press('ArrowDown');
    assert.equal(page.url().endsWith('crudit-ui/kit'), true, 'a result is opened before it is chosen');
    // Enter opens the active option, and the dialog is closed.
    const second = (await palette()).options[1].replace('*', '');
    await Promise.all([page.waitForNavigation({waitUntil: 'networkidle0'}), page.keyboard.press('Enter')]);
    assert.match(page.url(), /crudit-ui\/kit\?opened=/, `Enter does not open ${second}`);
    assert.equal((await palette()).open, false);

    // The button opens it too; the field is empty when it opens again. A click on a result opens it.
    await page.click('[commandfor="kit-search-dialog"]');
    assert.deepEqual((({open, focus, value, help, options}) => ({open, focus, value, help, options}))(await palette()), {open: true, focus: true, value: '', help: true, options: []}, 'the palette does not open empty');
    await typed('v-04');
    assert.deepEqual((await palette()).groups, ['Visits']);
    await Promise.all([page.waitForNavigation({waitUntil: 'networkidle0'}), page.click('#kit-search-dialog [role="option"]:nth-of-type(2)')]);
    assert.match(page.url(), /opened=v-0?2/, 'a click on a result does not open it');

    // No result, then an error of the server: shown and announced, the field keeps the focus.
    await page.click('[commandfor="kit-search-dialog"]');
    await typed('none');
    assert.deepEqual(
        (({options, message, expanded}) => ({options, message, expanded}))(await palette()),
        {options: [], message: 'No result for “none”.', expanded: 'false'},
        'no result is not said',
    );
    await page.waitForFunction(() => document.querySelector('#kit-search-dialog [role="status"]').textContent.startsWith('No result'), {timeout: 2000});
    await page.setRequestInterception(true);
    const failing = (request) => request.url().includes('/crudit-ui/kit-search') ? request.respond({status: 500, body: 'x'}) : request.continue();
    page.on('request', failing);
    await page.keyboard.down('Control');
    await page.keyboard.press('a');
    await page.keyboard.up('Control');
    await page.keyboard.type('mar');
    await page.waitForFunction(() => document.querySelector('#kit-search-dialog .crudit-ui-palette__message[role="alert"]'), {timeout: 3000});
    assert.equal((await palette()).message, 'The search failed. Try again.');
    assert.equal((await palette()).focus, true);
    page.off('request', failing);
    await page.setRequestInterception(false);
    // The error of the server was wanted.
    errors.splice(0, errors.length, ...errors.filter((error) => !error.includes('/crudit-ui/kit-search') && !error.includes('status of 500')));

    // A slow answer to an old text never replaces the one of the new text.
    await page.setRequestInterception(true);
    const slow = async (request) => {
        if (request.url().includes('/crudit-ui/kit-search') && request.url().includes('q=gar')) {
            await new Promise((resolve) => setTimeout(resolve, 1200));
        }
        request.continue();
    };
    page.on('request', slow);
    await page.keyboard.down('Control');
    await page.keyboard.press('a');
    await page.keyboard.up('Control');
    await page.keyboard.type('gar');
    await new Promise((resolve) => setTimeout(resolve, 400));
    await page.keyboard.down('Control');
    await page.keyboard.press('a');
    await page.keyboard.up('Control');
    await page.keyboard.type('v-04');
    await new Promise((resolve) => setTimeout(resolve, 2000));
    assert.deepEqual((await palette()).groups, ['Visits'], 'the answer of an old text replaces the one of the new text');
    page.off('request', slow);

    // "mar", then "mart" (slow), then back to "mar" before the answer: the answer of "mart" never replaces the list, and
    // Enter does nothing while the results are not those of the field.
    await page.keyboard.down('Control');
    await page.keyboard.press('a');
    await page.keyboard.up('Control');
    const slowMart = async (request) => {
        if (request.url().includes('/crudit-ui/kit-search') && request.url().includes('q=mart')) {
            await new Promise((resolve) => setTimeout(resolve, 1200));
        }
        request.continue();
    };
    page.on('request', slowMart);
    await typed('mar');
    // The list must be the answer to "mar" (not the previous one) before "t" is typed: only then is it the last text.
    await page.waitForFunction(() => {
        const dialog = document.getElementById('kit-search-dialog');
        return !dialog.hasAttribute('aria-busy') && dialog.querySelector('[role="option"] mark')?.textContent === 'Mar';
    }, {timeout: 3000});
    await page.keyboard.type('t');
    await new Promise((resolve) => setTimeout(resolve, 400));
    await page.keyboard.press('Backspace');
    await new Promise((resolve) => setTimeout(resolve, 2000));
    assert.equal((await palette()).value, 'mar');
    // "mar" and "mart" find the same client: the marked text tells the two lists apart.
    assert.equal(await page.evaluate(() => document.querySelector('#kit-search-dialog [role="option"] mark')?.textContent), 'Mar', 'the answer of "mart" replaces the list of "mar"');
    // Enter before the debounce is over: nothing opens.
    await page.keyboard.type('x');
    await page.keyboard.press('Enter');
    // choose() closes the dialog at once; the navigation itself would come later.
    assert.equal((await palette()).open, true, 'Enter opens the result of an older text');
    page.off('request', slowMart);
    await page.setRequestInterception(false);

    // Escape closes it, the focus goes back to the button. Ctrl K does nothing over another dialog.
    await page.keyboard.press('Escape');
    assert.deepEqual(await page.evaluate(() => [document.getElementById('kit-search-dialog').open, document.activeElement.getAttribute('commandfor')]), [false, 'kit-search-dialog'], 'Escape does not close the palette, or the focus is not given back');
    await openDialog('kit-dialog-confirm-open', 'kit-dialog-confirm');
    await page.keyboard.down('Control');
    await page.keyboard.press('k');
    await page.keyboard.up('Control');
    assert.equal((await palette()).open, false, 'Ctrl K opens the palette over another dialog');
    await page.keyboard.press('Escape');

    // Not read as HTML: a title with tags is shown as it is.
    await page.setRequestInterception(true);
    const tagged = (request) => request.url().includes('/crudit-ui/kit-search')
        ? request.respond({status: 200, contentType: 'application/json', body: JSON.stringify({groups: [{id: 'x', label: '<i>Group</i>', results: [{id: 'x-1', title: '<b>Bold</b> zz', subtitle: '<u>Under</u>', url: '/crudit-ui/kit?opened=x-1'}, {id: 'x-2', title: 'zz script', url: 'javascript:alert(1)'}, {id: 'x-3', title: 'zz no address'}]}]})})
        : request.continue();
    page.on('request', tagged);
    await page.click('[commandfor="kit-search-dialog"]');
    await typed('zz');
    assert.deepEqual(
        await page.evaluate(() => [document.querySelectorAll('#kit-search-dialog b, #kit-search-dialog i, #kit-search-dialog u').length, document.querySelector('#kit-search-dialog [role="option"]').textContent.trim()]),
        [0, '<b>Bold</b> zzUnder'.replace('Under', '<u>Under</u>')],
        'the text of a result is read as HTML',
    );
    assert.equal(await page.evaluate(() => document.querySelectorAll('#kit-search-dialog [role="option"]').length), 1, 'a result without address, or with a javascript: one, is shown');
    page.off('request', tagged);
    await page.setRequestInterception(false);
    await page.keyboard.press('Escape');

    // Accessibility with the results shown, in light and dark; right to left; on a phone (the whole screen).
    await page.click('[commandfor="kit-search-dialog"]');
    await typed('ar');
    await page.waitForFunction(() => document.querySelectorAll('#kit-search-dialog [role="option"]').length > 1, {timeout: 3000});
    await page.addScriptTag({content: axeSource});
    const paletteViolations = () => page.evaluate(async () => (await window.axe.run(document.getElementById('kit-search-dialog'), {runOnly: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa']})).violations.map((violation) => `${violation.id} (${violation.nodes.map((node) => node.target.join(' ')).join(', ')})`));
    assert.deepEqual(await paletteViolations(), [], 'accessibility of the palette');
    await page.emulateMediaFeatures([{name: 'prefers-color-scheme', value: 'dark'}]);
    assert.deepEqual(await paletteViolations(), [], 'accessibility of the palette, dark');
    await page.emulateMediaFeatures([{name: 'prefers-color-scheme', value: 'light'}]);
    await page.keyboard.press('Escape');
    await page.goto(new URL('crudit-ui/kit/ar', url).href, {waitUntil: 'networkidle0'});
    await page.click('[commandfor="kit-search-dialog"]');
    assert.equal((await palette()).open, true, 'the palette does not open in right to left');
    assert.equal(await page.evaluate(() => getComputedStyle(document.querySelector('#kit-search-dialog .crudit-ui-palette__field')).direction), 'rtl');
    await page.keyboard.press('Escape');
    await page.setViewport({width: 360, height: 740, isMobile: true, hasTouch: true});
    await page.goto(new URL('crudit-ui/kit', url).href, {waitUntil: 'networkidle0'});
    await page.click('[commandfor="kit-search-dialog"]');
    assert.deepEqual(await page.evaluate(() => {
        const rect = document.getElementById('kit-search-dialog').getBoundingClientRect();

        return [Math.round(rect.width), Math.round(rect.height), getComputedStyle(document.querySelector('#kit-search-dialog .crudit-ui-palette__footer')).display];
    }), [360, 740, 'none'], 'the palette is not the whole screen on a phone, or the keys are shown');
    await page.keyboard.press('Escape');

    // Table: selection, groups, manual order (saved, refused by the server, disabled), sticky header, cards on a phone.
    await page.setViewport({width: 1280, height: 800});
    await page.goto(new URL('crudit-ui/kit', url).href, {waitUntil: 'networkidle0'});
    assert.deepEqual(await page.evaluate(() => {
        document.querySelector('#kit-table-default tbody tr[data-id="412"] input').click();
        const all = document.querySelector('#kit-table-default thead input');
        const card = document.querySelector('#kit-table-default .crudit-ui-table__cards input[data-id="412"]');

        return [all.indeterminate, all.checked, card.checked, document.querySelector('#kit-table-default tr[data-id="412"]').classList.contains('crudit-ui-table__row--selected')];
    }), [true, false, true, true], 'table: selecting a row does not update the box of the page and the card');
    assert.deepEqual(await page.evaluate(() => {
        document.querySelector('#kit-table-default thead input').click();
        const boxes = [...document.querySelectorAll('#kit-table-default tbody input')];
        const all = document.querySelector('#kit-table-default thead input');

        return [boxes.every((box) => box.checked), all.indeterminate];
    }), [true, false], 'table: the box of the page does not select every row');

    const toggle = await page.$('#kit-table-groups tbody[data-group="aug"] button');
    await toggle.click();
    assert.deepEqual(await page.$eval('#kit-table-groups tbody[data-group="aug"]', (body) => [body.querySelector('button').getAttribute('aria-expanded'), [...body.querySelectorAll('[data-group-body]')].every((row) => row.hidden)]), ['false', true], 'table: a group does not fold');
    await toggle.click();

    const order = (id) => page.$$eval(`#${id} tbody tr[data-id]`, (rows) => rows.map((row) => row.dataset.id));
    await page.focus('#kit-table-sort tbody tr[data-id="412"] .crudit-ui-table__handle');
    await page.keyboard.press('ArrowDown');
    await page.waitForFunction(() => document.querySelector('#kit-table-sort [role="status"]').textContent !== '', {timeout: 3000});
    assert.deepEqual(await order('kit-table-sort'), ['413', '412', '414', '415'], 'table: ↓ on the handle does not move the row');
    assert.equal(await page.evaluate(() => document.activeElement === document.querySelector('#kit-table-sort tr[data-id="412"] .crudit-ui-table__handle')), true, 'table: the handle loses the focus when its row moves');
    assert.equal(await page.$eval('#kit-table-sort [role="status"]', (status) => status.textContent), 'Moved to position 2 of 4');
    await page.keyboard.press('ArrowUp');
    await page.waitForFunction(() => document.querySelector('#kit-table-sort [role="status"]').textContent.includes('position 1'), {timeout: 3000});
    assert.deepEqual(await order('kit-table-sort'), ['412', '413', '414', '415']);

    // Inside a group: the row moves in its group only (the heading and the subtotals stay), the order sent is the whole one.
    await page.focus('#kit-table-groups tbody[data-group="aug"] tr[data-id="412"] .crudit-ui-table__handle');
    await page.keyboard.press('ArrowDown');
    await page.waitForFunction(() => document.querySelector('#kit-table-groups [role="status"]').textContent !== '', {timeout: 3000});
    assert.deepEqual(await page.$$eval('#kit-table-groups tbody[data-group="aug"] > tr', (rows) => rows.map((row) => row.dataset.id ?? row.className.replace('crudit-ui-table__', ''))), ['group-row', '413', '412', 'total crudit-ui-table__total--sub'], 'table: a row of a group does not move inside its group');
    await page.keyboard.press('ArrowDown');
    assert.deepEqual(await page.$$eval('#kit-table-groups tbody[data-group="aug"] tr[data-id]', (rows) => rows.map((row) => row.dataset.id)), ['413', '412'], 'table: a row leaves its group');

    // The server refuses: the rows go back to their place, and the error is announced.
    await page.setRequestInterception(true);
    const refuse = (request) => request.url().includes('/crudit-ui/kit-table-sort') ? request.respond({status: 500, body: ''}) : request.continue();
    page.on('request', refuse);
    await page.focus('#kit-table-sort tbody tr[data-id="412"] .crudit-ui-table__handle');
    await page.keyboard.press('ArrowDown');
    await page.waitForFunction(() => document.querySelector('#kit-table-sort [role="status"]').textContent.includes('could not be saved'), {timeout: 3000});
    assert.deepEqual(await order('kit-table-sort'), ['412', '413', '414', '415'], 'table: a refused order is not undone');
    page.off('request', refuse);

    // A refusal that comes after the user left the table does not take the focus back.
    let late;
    const held = (request) => request.url().includes('/crudit-ui/kit-table-sort') ? (late = request) : request.continue();
    page.on('request', held);
    await page.$eval('#kit-table-sort [role="status"]', (status) => { status.textContent = ''; });
    await page.focus('#kit-table-sort tbody tr[data-id="412"] .crudit-ui-table__handle');
    await page.keyboard.press('ArrowDown');
    for (let wait = 0; !late && wait < 150; wait++) {
        await new Promise((resolve) => setTimeout(resolve, 20));
    }
    assert.ok(late, 'table: the order is not sent');
    await page.focus('#kit-table-groups tbody tr[data-id] .crudit-ui-table__handle');
    late.respond({status: 500, body: ''});
    await page.waitForFunction(() => document.querySelector('#kit-table-sort [role="status"]').textContent.includes('could not be saved'), {timeout: 3000});
    assert.equal(await page.evaluate(() => document.activeElement.closest('#kit-table-groups') !== null), true, 'table: a late refusal takes the focus back');
    page.off('request', held);
    await page.setRequestInterception(false);

    // Disabled by a filter: nothing moves, the reason is announced.
    await page.focus('#kit-table-sort-off tbody tr[data-id="412"] .crudit-ui-table__handle');
    await page.keyboard.press('ArrowDown');
    assert.deepEqual(await order('kit-table-sort-off'), ['412', '413'], 'table: the order changes while a filter is active');
    assert.equal(await page.$eval('#kit-table-sort-off [role="status"]', (status) => status.textContent), 'The order cannot change while a filter is active.');

    // A click on a row goes to its address, not a click on its link, its box or its menu.
    await page.click('#kit-table-default tbody tr[data-id="413"] td:nth-child(5)');
    await page.waitForFunction(() => location.hash === '#kit-table', {timeout: 2000});
    await page.goto(new URL('crudit-ui/kit', url).href, {waitUntil: 'networkidle0'});
    await page.click('#kit-table-default tbody tr[data-id="412"] .crudit-ui-dropdown > button');
    assert.equal(await page.evaluate(() => location.hash), '', 'table: a click on the menu of a row opens the row');

    // Sticky header: the heading row follows the page under the header of the application (none in the kit: at the top).
    await page.evaluate(() => window.scrollTo(0, document.querySelector('#kit-table-groups .crudit-ui-table__wrap').getBoundingClientRect().top + window.scrollY + 80));
    await new Promise((resolve) => setTimeout(resolve, 200));
    assert.ok(await page.evaluate(() => Math.abs(document.querySelector('#kit-table-groups thead').getBoundingClientRect().top) <= 2), 'table: the heading does not follow the page');

    // On a phone: the cards, not the table, and no horizontal scroll of the page.
    await page.setViewport({width: 360, height: 740, isMobile: true, hasTouch: true});
    await page.goto(new URL('crudit-ui/kit', url).href, {waitUntil: 'networkidle0'});
    assert.deepEqual(await page.evaluate(() => [
        getComputedStyle(document.querySelector('#kit-table-default .crudit-ui-table__wrap')).display,
        getComputedStyle(document.querySelector('#kit-table-default .crudit-ui-table__cards')).display,
        document.documentElement.scrollWidth <= window.innerWidth,
    ]), ['none', 'block', true], 'table: cards on a phone');
    // The buttons of a card move its row, the cards follow, and the focus stays on a button of the card (moving a node blurs it).
    await page.click('#kit-table-sort .crudit-ui-table__card-order button[data-action$="#down"]');
    await page.waitForFunction(() => document.querySelector('#kit-table-sort [role="status"]').textContent !== '', {timeout: 3000});
    assert.deepEqual(await page.evaluate(() => [
        [...document.querySelectorAll('#kit-table-sort .crudit-ui-table__card-list > li')].map((card) => card.querySelector('[data-id]').dataset.id),
        document.activeElement?.closest('.crudit-ui-table__card-order') !== null,
    ]), [['413', '412', '414', '415'], true], 'table: a card does not move, or loses the focus');

    assert.deepEqual(errors, [], 'errors while loading the page');
    console.log(`OK ${url}`);
} finally {
    await browser.close();
}
