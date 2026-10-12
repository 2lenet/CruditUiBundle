/*
 * The breakpoints, read from the CSS variables declared by the stylesheet (--breakpoint-md…):
 * the only set is in styles/settings/_breakpoints.scss.
 */

/**
 * @param {string} name xs, sm, md, lg, xl or xxl
 * @returns {number} the breakpoint, in px
 */
export function breakpoint(name) {
    const value = parseFloat(getComputedStyle(document.documentElement).getPropertyValue(`--breakpoint-${name}`));

    if (Number.isNaN(value)) {
        throw new Error(`Unknown breakpoint "${name}": is the stylesheet of the bundle loaded?`);
    }

    return value;
}

/**
 * @param {string} query '>md' (md and up) or '<md' (below md), as the `media` mixin of the stylesheet
 * @returns {MediaQueryList}
 */
export function matchBreakpoint(query) {
    const value = breakpoint(query.slice(1));

    return window.matchMedia(query[0] === '>' ? `(min-width: ${value}px)` : `(max-width: ${value - 0.02}px)`);
}
