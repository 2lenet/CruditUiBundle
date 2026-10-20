/*
 * Positions of the panels that open on a button: dropdown panels and tooltips. Same as UiPopover of the crudit-v2
 * mockup. The styles are set with the CSSOM (element.style), which a strict Content Security Policy allows.
 */

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
    const origin = fixedOrigin(anchor);
    const bounds = {
        top: Math.max(0, origin.clip.top),
        bottom: Math.min(window.innerHeight, origin.clip.bottom),
        left: Math.max(0, origin.clip.left),
        right: Math.min(window.innerWidth, origin.clip.right),
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
 * Removes what keepInViewport() and placeFloating() set.
 *
 * @param {HTMLElement} panel
 */
export function resetPosition(panel) {
    for (const property of ['top', 'left', 'maxHeight', 'translate']) {
        panel.style[property] = '';
    }
}

/*
 * An ancestor with a transform (a sticky table header, a drawer that slides) becomes the reference of
 * `position: fixed`, and the panel is cut by the container that scrolls around it: it is kept inside its edges.
 */
function fixedOrigin(anchor) {
    const viewport = {top: 0, bottom: window.innerHeight, left: 0, right: window.innerWidth};
    for (let element = anchor.parentElement; element; element = element.parentElement) {
        if (containsFixed(getComputedStyle(element))) {
            const rect = element.getBoundingClientRect();

            return {top: rect.top + element.clientTop, left: rect.left + element.clientLeft, clip: clippingRect(element) ?? viewport};
        }
    }

    return {top: 0, left: 0, clip: viewport};
}

/*
 * The properties that make an element the containing block of its descendants in position: fixed.
 */
function containsFixed(style) {
    return ['transform', 'translate', 'rotate', 'scale', 'perspective', 'filter', 'backdropFilter', 'webkitBackdropFilter'].some((property) => (style[property] ?? 'none') !== 'none')
        || /transform|translate|rotate|scale|perspective|filter/.test(style.willChange)
        || /paint|layout|strict|content/.test(style.contain)
        // A size container has layout containment, which the computed value of contain does not show.
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
