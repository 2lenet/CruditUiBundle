/*
 * Positions of the panels that open on a button: dropdown panels and tooltips. Same as UiPopover of the crudit-v2
 * mockup. The styles are set with the CSSOM (element.style), which a strict Content Security Policy allows.
 */

// The reference of each panel in position: fixed (an ancestor, or null for the screen), found when the panel is first
// placed and kept until resetPosition() or forgetReference(): it only depends on the ancestors. Its place on the screen is read again each
// time (it moves when the page scrolls), but the panel is no longer moved to 0, 0 and measured at each frame.
const references = new WeakMap();

// Space kept between a panel and the edge of the screen, and between a panel and its button (px).
const MARGIN = 8;
const GAP = 4;

/**
 * A panel in CSS position (under its button) that goes out of the screen (a button near the edge, on a phone) is
 * shifted horizontally to stay whole. Call it once the panel is shown; resetPosition() before hiding it.
 *
 * @param {HTMLElement} panel
 */
export function keepInViewport(panel) {
    panel.style.translate = '';
    const rect = panel.getBoundingClientRect();
    let shift = 0;
    if (rect.left < MARGIN) {
        shift = MARGIN - rect.left;
    } else if (rect.right > window.innerWidth - MARGIN) {
        shift = window.innerWidth - MARGIN - rect.right;
    }
    if (shift) {
        panel.style.translate = `${Math.round(shift)}px 0`;
    }
}

/**
 * Panel in `position: fixed`, placed next to its button: it is not cut by a container that scrolls or hides its
 * overflow (a table, the menu bar). Below the button, or above when there is more room there; its height is then
 * limited (it scrolls inside). Aligned on the edge `align` of the button, in the reading direction.
 *
 * @param {HTMLElement} anchor the button
 * @param {HTMLElement} panel shown, in position: fixed
 * @param {{align?: 'start'|'end', placement?: 'below'|'above'|'beside'}} options beside: on the inline end side,
 *     centred on the button (tooltip of the collapsed menu)
 */
export function placeFloating(anchor, panel, {align = 'end', placement = 'below'} = {}) {
    panel.style.maxHeight = '';
    const rect = anchor.getBoundingClientRect();
    const width = panel.offsetWidth;
    const height = panel.offsetHeight;
    const rtl = getComputedStyle(anchor).direction === 'rtl';
    if (!references.has(panel)) {
        references.set(panel, fixedReference(anchor, panel));
    }
    const origin = fixedOrigin(references.get(panel));
    const visible = visibleArea();
    const bounds = {
        top: Math.max(visible.top, origin.clip.top),
        bottom: Math.min(visible.bottom, origin.clip.bottom),
        left: Math.max(visible.left, origin.clip.left),
        right: Math.min(visible.right, origin.clip.right),
    };

    let top;
    let left;
    if (placement === 'beside') {
        left = rtl ? rect.left - GAP - width : rect.right + GAP;
        top = rect.top + (rect.height - height) / 2;
        top = Math.max(bounds.top + MARGIN, Math.min(top, bounds.bottom - height - MARGIN));
    } else {
        const below = bounds.bottom - rect.bottom - GAP - MARGIN;
        const above = rect.top - bounds.top - GAP - MARGIN;
        // Above when asked and there is room, or when the panel does not fit below and there is more room above.
        const openAbove = placement === 'above' ? height <= above || above > below : height > below && above > below;
        const maxHeight = openAbove ? above : below;
        top = openAbove ? rect.top - GAP - Math.min(height, maxHeight) : rect.bottom + GAP;
        left = (align === 'end') !== rtl ? rect.right - width : rect.left;
        if (height > maxHeight) {
            panel.style.maxHeight = `${Math.max(maxHeight, 0)}px`;
        }
    }
    left = Math.max(bounds.left + MARGIN, Math.min(left, bounds.right - width - MARGIN));

    panel.style.top = `${Math.round(top - origin.top)}px`;
    panel.style.left = `${Math.round(left - origin.left)}px`;
}

/**
 * Forgets the reference of the panel: placeFloating() measures it again. To call when an ancestor may have changed while
 * the panel is shown (a drawer that has finished sliding in no longer has a transform).
 *
 * @param {HTMLElement} panel
 */
export function forgetReference(panel) {
    references.delete(panel);
}

/**
 * Removes what keepInViewport() and placeFloating() set.
 *
 * @param {HTMLElement} panel
 */
export function resetPosition(panel) {
    forgetReference(panel);
    for (const property of ['top', 'left', 'maxHeight', 'translate']) {
        panel.style[property] = '';
    }
}

/*
 * An ancestor with a transform (a sticky table header, a drawer that slides) becomes the reference of
 * `position: fixed`, and the panel is cut by the container that scrolls around it: it is kept inside its edges.
 */
/**
 * The part of the page seen on the screen, in the coordinates of getBoundingClientRect(): without the virtual keyboard
 * of a phone (visualViewport; window.innerHeight still counts the height under the keyboard on iOS).
 *
 * @returns {{top: number, bottom: number, left: number, right: number}}
 */
export function visibleArea() {
    const visual = window.visualViewport;
    if (!visual) {
        return {top: 0, bottom: window.innerHeight, left: 0, right: window.innerWidth};
    }

    return {
        top: visual.offsetTop,
        bottom: visual.offsetTop + visual.height,
        left: visual.offsetLeft,
        right: visual.offsetLeft + visual.width,
    };
}

/*
 * The element that is the reference of the panel in position: fixed, or null for the screen.
 */
function fixedReference(anchor, panel) {
    // Measured first: the browsers do not agree on what makes the reference. A size container (container-type) no
    // longer has layout containment, so it is no longer the reference of position: fixed (CSSWG resolution,
    // https://github.com/w3c/csswg-drafts/issues/10544, test
    // https://github.com/web-platform-tests/wpt/blob/master/css/css-conditional/container-queries/no-layout-containment-fixedpos.html);
    // browsers released before it still make it one. At 0, 0 the panel is on the corner of the screen when the screen
    // is its reference.
    panel.style.top = '0px';
    panel.style.left = '0px';
    const corner = panel.getBoundingClientRect();
    const style = getComputedStyle(panel);
    if (Math.abs(corner.top - parseFloat(style.marginTop || '0')) < 1 && Math.abs(corner.left - parseFloat(style.marginLeft || '0')) < 1) {
        return null;
    }
    for (let element = anchor.parentElement; element; element = element.parentElement) {
        if (containsFixed(getComputedStyle(element))) {
            return element;
        }
    }

    return null;
}

/*
 * Where the reference is on the screen now, and the part of the screen that shows it.
 */
function fixedOrigin(reference) {
    const viewport = {top: 0, bottom: window.innerHeight, left: 0, right: window.innerWidth};
    if (!reference) {
        return {top: 0, left: 0, clip: viewport};
    }
    const rect = reference.getBoundingClientRect();

    return {top: rect.top + reference.clientTop, left: rect.left + reference.clientLeft, clip: clippingRect(reference) ?? viewport};
}

/*
 * The properties that make an element the containing block of its descendants in position: fixed.
 */
function containsFixed(style) {
    return ['transform', 'translate', 'rotate', 'scale', 'perspective', 'filter', 'backdropFilter', 'webkitBackdropFilter'].some((property) => (style[property] ?? 'none') !== 'none')
        || /transform|translate|rotate|scale|perspective|filter/.test(style.willChange)
        || /paint|layout|strict|content/.test(style.contain)
        // A size container had layout containment in the browsers released before the resolution above (not shown by
        // the computed value of contain): only reached when the measure did not find the screen.
        || (style.containerType ?? 'normal') !== 'normal';
}

function clippingRect(element) {
    for (let parent = element.parentElement; parent; parent = parent.parentElement) {
        if (getComputedStyle(parent).overflowX !== 'visible') {
            return parent.getBoundingClientRect();
        }
    }

    return null;
}
