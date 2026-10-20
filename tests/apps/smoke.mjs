// Smoke test of a test application: node smoke.mjs <url> [chrome path]
// The page must show the dropdown controller of the bundle and load its stylesheet; the design kit page must have no
// accessibility violation (axe-core), and its menus, tooltips, dialogs and toasts must work.
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
    // A navigation answered with 204 (the demos of the kit that send a form) is aborted by Chrome: the page stays.
    page.on('requestfailed', (request) => !request.url().includes('/crudit-ui/kit-sent') && errors.push(`request failed: ${request.url()}`));
    page.on('response', (response) => response.status() >= 400 && !response.url().endsWith('/favicon.ico') && errors.push(`${response.status()}: ${response.url()}`));

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

    // A link item is followed.
    await page.click('#kit-menu > button');
    await page.click('#kit-menu-panel a.crudit-ui-menu__item');
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
    // the page.
    const sendBusy = () => page.evaluate(() => document.querySelector('#kit-menu-submit > button').getAttribute('aria-busy'));
    await page.click('#kit-menu-submit > button');
    await page.click('#kit-menu-submit-panel button[type="submit"]');
    assert.equal(await sendBusy(), 'true', 'the demo of the kit is not in loading');
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
    assert.deepEqual(await regionOf({message: null}), ['kit-toasts-first: Information:'], 'the first region of the page does not take the event, or a message null is shown');
    await openDialog('kit-dialog-confirm-open', 'kit-dialog-confirm');
    assert.deepEqual(await regionOf({message: 'In the dialog.', persistent: true}), ['kit-toasts-first: Information:', 'kit-toasts-first: Information: In the dialog.'], 'the region of the page does not take the event while a dialog is open');
    await page.waitForFunction(() => document.getElementById('kit-dialog-confirm').contains(document.getElementById('kit-toasts-first')), {timeout: 1000});
    await page.keyboard.press('Escape');
    await page.evaluate(() => document.querySelectorAll('.crudit-ui-toast').forEach((toast) => toast.closest('.crudit-ui-toasts') && toast.remove()));
    assert.deepEqual(await regionOf({message: 'Again.', persistent: true}), ['kit-toasts-first: Information: Again.'], 'the region that takes the event changes once a dialog has opened');
    await page.evaluate(() => document.getElementById('kit-toasts-first').remove());

    // Right to left: on the top left; on a phone: the whole width, less the margins.
    await page.goto(new URL('crudit-ui/kit/ar?toast=success', url).href, {waitUntil: 'networkidle0'});
    assert.deepEqual(await place(), {top: true, end: true, width: 448}, 'the toasts are not on the inline end side in right to left');
    await page.setViewport({width: 360, height: 740, isMobile: true, hasTouch: true});
    await page.goto(new URL('crudit-ui/kit?toast=success', url).href, {waitUntil: 'networkidle0'});
    assert.deepEqual(await place(), {top: true, end: true, width: 328}, 'the toasts do not take the width of a phone');

    assert.deepEqual(errors, [], 'errors while loading the page');
    console.log(`OK ${url}`);
} finally {
    await browser.close();
}
