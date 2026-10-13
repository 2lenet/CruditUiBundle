import { Controller } from '@hotwired/stimulus';

/*
 * Theme switch: light, dark or system. The options (targets "option") are buttons, radio buttons or a select, whose
 * value is the theme; each one calls the "choose" action. The choice goes in the data-theme attribute of <html> and
 * in a cookie, which the layout reads on the next pages (ui_theme()). "system" removes both: the CSS then follows
 * the preference of the system. Dispatches "change" (detail: {theme}) for what draws with the colours (charts).
 */
const COOKIE = 'crudit_ui_theme';
const THEMES = ['light', 'dark'];
const YEAR = 365 * 24 * 60 * 60;

export default class extends Controller {
    static targets = ['option'];

    connect() {
        this.reflect(this.current());
    }

    choose(event) {
        const theme = THEMES.includes(event.currentTarget.value) ? event.currentTarget.value : 'system';
        const root = document.documentElement;

        if (theme === 'system') {
            delete root.dataset.theme;
            document.cookie = `${COOKIE}=; path=/; max-age=0; SameSite=Lax`;
        } else {
            root.dataset.theme = theme;
            document.cookie = `${COOKIE}=${theme}; path=/; max-age=${YEAR}; SameSite=Lax`;
        }

        this.reflect(theme);
        this.dispatch('change', { detail: { theme } });
    }

    current() {
        const theme = document.documentElement.dataset.theme;

        return THEMES.includes(theme) ? theme : 'system';
    }

    reflect(theme) {
        for (const option of this.optionTargets) {
            if (option instanceof HTMLSelectElement) {
                option.value = theme;
            } else if (option instanceof HTMLInputElement) {
                option.checked = option.value === theme;
            } else {
                option.setAttribute('aria-pressed', String(option.value === theme));
            }
        }
    }
}
