import { Controller } from '@hotwired/stimulus';

/*
 * Tabs, ARIA pattern with automatic activation: a click or an arrow key selects the tab and shows its panel at once.
 * The arrows follow the screen: in a right-to-left page, → goes to the previous tab (direction read when the key is
 * pressed). They loop; Home and End go to the first and the last tab. Dispatches "change" ({id}).
 *
 * With the value urlParam, the address follows the tab (?<urlParam>=<id>, the other parameters kept) without a new
 * entry in the history: Back leaves the page, it does not go through the tabs. The server reads the parameter again
 * on a reload.
 */
export default class extends Controller {
    static targets = ['tab', 'panel'];
    static values = {urlParam: String};

    select(event) {
        this.show(this.tabTargets.indexOf(event.currentTarget));
        // Safari does not focus a button on a click: the arrows would not work after it.
        event.currentTarget.focus();
    }

    keydown(event) {
        const tabs = this.tabTargets;
        const current = tabs.indexOf(event.target);
        // Alt + arrow is Back or Forward in the browser.
        if (current === -1 || event.altKey || event.ctrlKey || event.metaKey) {
            return;
        }

        const rtl = getComputedStyle(this.element).direction === 'rtl';
        const next = (current + 1) % tabs.length;
        const previous = (current - 1 + tabs.length) % tabs.length;
        const moves = {
            ArrowRight: rtl ? previous : next,
            ArrowLeft: rtl ? next : previous,
            Home: 0,
            End: tabs.length - 1,
        };
        if (!(event.key in moves)) {
            return;
        }

        event.preventDefault();
        this.show(moves[event.key]);
        tabs[moves[event.key]].focus();
    }

    show(index) {
        const tab = this.tabTargets[index];
        if (!tab || tab.getAttribute('aria-selected') === 'true') {
            return;
        }

        this.tabTargets.forEach((other, i) => {
            other.setAttribute('aria-selected', String(i === index));
            other.tabIndex = i === index ? 0 : -1;
            // A panel missing from a markup written by hand: its tab is still selected.
            if (this.panelTargets[i]) {
                this.panelTargets[i].hidden = i !== index;
            }
        });

        const id = tab.getAttribute(`data-${this.identifier}-id-param`);
        if (this.hasUrlParamValue) {
            const url = new URL(window.location.href);
            url.searchParams.set(this.urlParamValue, id);
            window.history.replaceState(window.history.state, '', url);
        }
        this.dispatch('change', {detail: {id}});
    }
}
