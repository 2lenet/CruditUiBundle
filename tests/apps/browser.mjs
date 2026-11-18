// Playwright browsers for the smoke tests, behind the few methods of the Puppeteer API the tests were written with
// (evaluate with several arguments, setViewport, setRequestInterception, waitForNavigation...), so that the same test
// runs on every project: node smoke.mjs <url> [project]
//
//   chromium  Chrome on a desktop
//   webkit    Safari on a desktop (WebKit)
//   iphone    Safari on an iPhone (WebKit, touch, mobile viewport)
//   pixel     Chrome on an Android phone (Chromium, touch, mobile viewport)
//
// CHROME_PATH uses an installed Chrome instead of the Chromium of Playwright.
import {chromium, devices, webkit} from 'playwright';

export const projects = {
    chromium: {type: chromium, context: {...devices['Desktop Chrome'], viewport: {width: 1280, height: 800}}},
    webkit: {type: webkit, context: {...devices['Desktop Safari'], viewport: {width: 1280, height: 800}}},
    iphone: {type: webkit, context: devices['iPhone 15']},
    pixel: {type: chromium, context: devices['Pixel 7']},
};

// The function is sent as a text: the arguments of Puppeteer (several, handles included) travel in one array.
const call = (fn) => `(${fn})`;
// Run in the page: nothing of this module is there, so every function is self-contained.
const run = ([source, args]) => (0, eval)(source)(...args);
const runOn = (first, [source, args]) => (0, eval)(source)(first, ...args);
const waitUntil = (options = {}) => ({...options, waitUntil: options.waitUntil?.startsWith('networkidle') ? 'networkidle' : options.waitUntil});

class Request {
    constructor(route) {
        this.route = route;
    }

    url() {
        return this.route.request().url();
    }

    method() {
        return this.route.request().method();
    }

    headers() {
        return this.route.request().headers();
    }

    postData() {
        return this.route.request().postData();
    }

    continue() {
        return this.route.continue();
    }

    respond({status = 200, body = '', ...options}) {
        return this.route.fulfill({status, body, ...options});
    }

    abort() {
        return this.route.abort();
    }
}

const adapt = (page) => {
    const interceptors = new Map();
    let intercepting = false;
    const own = {
        evaluate: (fn, ...args) => page.evaluate(run, [call(fn), args]),
        $eval: (selector, fn, ...args) => page.$eval(selector, runOn, [call(fn), args]),
        $$eval: (selector, fn, ...args) => page.$$eval(selector, runOn, [call(fn), args]),
        // Puppeteer: (function, options, ...arguments); Playwright: (function, argument, options).
        waitForFunction: (fn, options = {}, ...args) => page.waitForFunction(run, [call(fn), args], options),
        // Like Puppeteer, click even an element that Playwright finds not enabled (aria-disabled: the tests check that it does nothing).
        click: (selector, options) => page.click(selector, {force: true, ...options}),
        goto: (address, options) => page.goto(address, waitUntil(options)),
        reload: (options) => page.reload(waitUntil(options)),
        // WebKit reports a form answered by 204 (no navigation) as an aborted navigation: wait for the next one.
        async waitForNavigation(options) {
            for (;;) {
                try {
                    return await page.waitForNavigation(waitUntil(options));
                } catch (error) {
                    if (!error.message.includes('Aborted: 204')) {
                        throw error;
                    }
                }
            }
        },
        // Playwright keeps the type of device of the context: only the size changes.
        setViewport: ({width, height}) => page.setViewportSize({width, height}),
        emulateMediaFeatures: (features) => page.emulateMedia(Object.fromEntries(features.map(({name, value}) => [name === 'prefers-color-scheme' ? 'colorScheme' : name, value]))),
        emulateMediaType: (media) => page.emulateMedia({media}),
        accessibility: {snapshot: () => page.locator('body').ariaSnapshot()},
        async setRequestInterception(enabled) {
            intercepting = enabled;
            if (enabled) {
                await page.route('**/*', (route) => {
                    const request = new Request(route);
                    interceptors.size ? [...interceptors.values()].forEach((listener) => listener(request)) : route.continue();
                });
            } else {
                await page.unroute('**/*');
                interceptors.clear();
            }
        },
        // While the interception is on, a listener of 'request' can answer the request; otherwise it only watches.
        on(event, listener) {
            event === 'request' && intercepting ? interceptors.set(listener, listener) : page.on(event, listener);
            return proxy;
        },
        off(event, listener) {
            interceptors.delete(listener);
            page.off(event, listener);
            return proxy;
        },
    };
    const proxy = new Proxy(page, {
        get: (target, property) => property in own ? own[property] : typeof target[property] === 'function' ? target[property].bind(target) : target[property],
    });

    return proxy;
};

export async function launch(name = 'chromium') {
    const project = projects[name];
    if (!project) {
        throw new Error(`Unknown project "${name}": ${Object.keys(projects).join(', ')}`);
    }
    const executablePath = project.type === chromium ? process.env.CHROME_PATH : undefined;
    const browser = await project.type.launch({executablePath, args: project.type === chromium ? ['--no-sandbox'] : []});
    const context = await browser.newContext(project.context);

    return {
        name,
        // Same device for every page, like the browser of Puppeteer.
        newPage: async () => adapt(await context.newPage()),
        version: () => `${name}: ${project.type.name()} ${browser.version()}`,
        close: () => browser.close(),
    };
}
