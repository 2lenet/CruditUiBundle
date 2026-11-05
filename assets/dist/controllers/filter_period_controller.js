import { Controller } from '@hotwired/stimulus';

/*
 * Filter:Period. The two dates (targets "start" and "end") are what the form sends, so the controller only helps:
 * - a ready-made period (action "choose", data-from / data-to in Y-m-d) fills the two dates;
 * - the summary (target "summary") follows the dates ("<from> → <to>", "From <date>", "Until <date>", "All dates");
 *   its texts come from Twig (value "texts", with %from%, %to% and %date%), the dates are written in the language of
 *   the page.
 * An "end" before the "start" is not corrected: the filter then finds nothing, which the summary shows.
 *
 * Event: "change" (2lenet--crudit-ui-bundle--filter-period:change), detail {start, end, chosen}, after each change;
 * `chosen` is true when a ready-made period was clicked (the period is then complete), false when a date was typed (the
 * other one may still come).
 */
export default class extends Controller {
    static targets = ['start', 'end', 'summary'];

    static values = {texts: Object};

    choose(event) {
        const {from, to} = event.currentTarget.dataset;
        this.startTarget.value = from;
        this.endTarget.value = to;
        this.update(true);
    }

    changed() {
        this.update(false);
    }

    update(chosen) {
        const start = this.startTarget.value;
        const end = this.endTarget.value;
        const texts = this.textsValue;
        let summary = texts.all;
        if (start && end) {
            summary = texts.range.replace('%from%', this.format(start)).replace('%to%', this.format(end));
        } else if (start) {
            summary = texts.since.replace('%date%', this.format(start));
        } else if (end) {
            summary = texts.until.replace('%date%', this.format(end));
        }
        if (this.hasSummaryTarget) {
            this.summaryTarget.textContent = summary;
        }
        this.dispatch('change', {detail: {start, end, chosen}});
    }

    format(iso) {
        const [year, month, day] = iso.split('-').map(Number);
        // Noon, UTC: no day is lost by a time zone.
        const date = new Date(Date.UTC(year, month - 1, day, 12));

        return new Intl.DateTimeFormat(document.documentElement.lang || undefined, {year: 'numeric', month: '2-digit', day: '2-digit', timeZone: 'UTC'}).format(date);
    }
}
