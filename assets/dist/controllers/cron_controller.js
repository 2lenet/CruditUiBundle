import { Controller } from '@hotwired/stimulus';

/*
 * Cron expression (templates/form/_cron.html.twig). The server renders the controls in the state read from the value;
 * this controller shows them (without JavaScript, only the text input), and on each change writes the expression in the
 * text input, which is what the form sends, then the sentence ("Every Monday and Thursday at 08:00") and the expression.
 *
 * Frequencies: none (empty value), daily ("m h * * *"), weekly ("m h * * 1,4"; no day: the sentence asks for one and
 * the expression stays incomplete, refused by the server), monthly ("m h D * *", "m h L * *"), custom (the text input,
 * the expression kept as it is). The names of the days and the list come from Intl, in the locale of the request.
 */
export default class extends Controller {
    static targets = ['widget', 'frequency', 'details', 'days', 'day', 'monthDay', 'monthDayInput', 'time', 'summary', 'sentence', 'code', 'custom'];
    static values = {
        frequency: String,
        locale: String,
        dailyText: String,
        weeklyText: String,
        monthlyText: String,
        firstText: String,
        lastText: String,
        noDayText: String,
    };

    connect() {
        this.widgetTarget.hidden = false;
        this.show();
        this.writeSummary();
        // Live once written: the sentence is not read when the page loads, only when it changes.
        this.summaryTarget.setAttribute('aria-live', 'polite');
    }

    change() {
        this.frequencyValue = this.frequencyTargets.find(radio => radio.checked)?.value ?? 'none';
        this.show();
        if (this.frequencyValue !== 'custom') {
            this.field.value = this.expression();
            this.field.dispatchEvent(new Event('input', {bubbles: true}));
            this.field.dispatchEvent(new Event('change', {bubbles: true}));
        }
        this.writeSummary();
    }

    show() {
        const frequency = this.frequencyValue;
        this.detailsTarget.hidden = frequency === 'none' || frequency === 'custom';
        this.daysTarget.hidden = frequency !== 'weekly';
        this.monthDayTarget.hidden = frequency !== 'monthly';
        this.customTarget.hidden = frequency !== 'custom';
    }

    expression() {
        const [hours, minutes] = this.time.split(':').map(Number);

        switch (this.frequencyValue) {
            case 'daily':
                return `${minutes} ${hours} * * *`;
            case 'weekly':
                // No day: an expression of four fields, refused by the server, as the sentence says.
                return this.days.length > 0 ? `${minutes} ${hours} * * ${this.days.join(',')}` : `${minutes} ${hours} * *`;
            case 'monthly':
                return `${minutes} ${hours} ${this.monthDayInputTarget.value} * *`;
            default:
                return '';
        }
    }

    writeSummary() {
        this.sentenceTarget.textContent = this.sentence();
        this.codeTarget.textContent = this.frequencyValue === 'custom' ? '' : this.expression();
        this.codeTarget.hidden = this.codeTarget.textContent === '';
    }

    sentence() {
        const time = this.time;
        if (this.frequencyValue === 'daily') {
            return this.dailyTextValue.replace('%time%', time);
        }
        if (this.frequencyValue === 'weekly') {
            const names = this.dayTargets.filter(day => day.checked).map(day => this.dayName(day.value));
            if (names.length === 0) {
                return this.noDayTextValue;
            }

            return this.weeklyTextValue.replace('%days%', new Intl.ListFormat(this.locale, {type: 'conjunction'}).format(names)).replace('%time%', time);
        }
        if (this.frequencyValue === 'monthly') {
            const day = this.monthDayInputTarget.value;
            if (day === 'L') {
                return this.lastTextValue.replace('%time%', time);
            }

            return (day === '1' ? this.firstTextValue : this.monthlyTextValue).replace('%day%', day).replace('%time%', time);
        }

        return '';
    }

    dayName(value) {
        // 2024-01-01 is a Monday; 0 is Sunday, the 7th.
        const date = new Date(Date.UTC(2024, 0, value === '0' ? 7 : Number(value), 12));

        return new Intl.DateTimeFormat(this.locale, {weekday: 'long', timeZone: 'UTC'}).format(date);
    }

    // Days chosen, in the order of cron (0 Sunday first).
    get days() {
        return this.dayTargets.filter(day => day.checked).map(day => day.value).sort();
    }

    get time() {
        return /^\d{1,2}:\d{2}/.test(this.timeTarget.value) ? this.timeTarget.value.slice(0, 5) : '08:00';
    }

    get field() {
        return this.customTarget.querySelector('input');
    }

    get locale() {
        return this.localeValue || document.documentElement.lang || undefined;
    }
}
