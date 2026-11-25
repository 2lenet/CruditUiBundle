import { Controller } from '@hotwired/stimulus';

/*
 * Cron expression (templates/form/_cron.html.twig). The server renders the controls in the state read from the value;
 * this controller shows them (without JavaScript, only the text input), and on each change writes the expression in the
 * text input, which is what the form sends (required only in custom, when shown), then the sentence ("Every Monday and Thursday at 8:30 AM") and the
 * expression. When the page loads, the expression shown is the value of the text input, as it will be sent.
 *
 * Frequencies: none (empty value), daily ("m h * * *"), weekly ("m h * * 1,4"), monthly ("m h D * *", D from 1 to 28),
 * custom (the text input, the expression kept as it is). A week without a day, or an empty time: the sentence asks for
 * it and the expression stays incomplete (four fields), refused by the server. Leaving custom reads the expression
 * typed when it has one of the forms of the widget, else the controls keep their state. The names of the days, the list
 * and the time come from Intl, in the locale of the request.
 */
export default class extends Controller {
    static targets = ['widget', 'frequency', 'details', 'days', 'day', 'monthDay', 'monthDayInput', 'time', 'sentence', 'code', 'custom'];
    static values = {
        frequency: String,
        locale: String,
        dailyText: String,
        weeklyText: String,
        monthlyText: String,
        noDayText: String,
        noTimeText: String,
    };

    connect() {
        // Invalid as sent to the server: the controls keep their aria-invalid whatever is chosen.
        this.serverInvalid = this.element.classList.contains('crudit-ui-cron--invalid');
        // A required text input hidden would stop the form in the browser, with no message it can show (it cannot
        // focus it): required only when shown, the server refuses an empty value.
        this.required = this.field.required;
        if (this.required) {
            this.field.setAttribute('aria-required', 'true');
        }
        this.widgetTarget.hidden = false;
        this.show();
        this.writeSummary(this.field.value.trim());
        // Live once written: the sentence is not read when the page loads, only when it changes.
        this.sentenceTarget.setAttribute('aria-live', 'polite');
    }

    change(event) {
        const previous = this.frequencyValue;
        this.frequencyValue = this.frequencyTargets.find(radio => radio.checked)?.value ?? 'none';
        if (previous === 'custom' && this.frequencyValue !== 'custom' && event?.target?.type === 'radio') {
            this.read(this.field.value.trim());
        }
        this.show();
        if (this.frequencyValue !== 'custom') {
            this.field.value = this.expression();
            this.field.dispatchEvent(new Event('input', {bubbles: true}));
            this.field.dispatchEvent(new Event('change', {bubbles: true}));
        }
        this.writeSummary(this.frequencyValue === 'custom' ? '' : this.field.value);
    }

    show() {
        const frequency = this.frequencyValue;
        this.detailsTarget.hidden = frequency === 'none' || frequency === 'custom';
        this.daysTarget.hidden = frequency !== 'weekly';
        this.monthDayTarget.hidden = frequency !== 'monthly';
        this.customTarget.hidden = frequency !== 'custom';
        this.field.required = this.required && frequency === 'custom';
        // A week without a day: the days are what is missing.
        const noDay = frequency === 'weekly' && this.days.length === 0;
        this.dayTargets.forEach((day) => {
            if (this.serverInvalid || noDay) {
                day.setAttribute('aria-invalid', 'true');
            } else {
                day.removeAttribute('aria-invalid');
            }
        });
    }

    // The controls from an expression of one of the forms of the widget ("00 08 * * *" too); another expression leaves
    // them as they are.
    read(expression) {
        const match = /^([0-5]?\d) ([01]?\d|2[0-3]) (\*|0?[1-9]|1\d|2[0-8]) \* (\*|[0-6](?:,[0-6])*)$/.exec(expression);
        if (!match) {
            return;
        }
        const [, minute, hour, day, weekDays] = match;
        this.timeTarget.value = `${hour.padStart(2, '0')}:${minute.padStart(2, '0')}`;
        if (day !== '*' && weekDays === '*') {
            // "05" is the option 5
            this.monthDayInputTarget.value = String(Number(day));
        }
        if (day === '*' && weekDays !== '*') {
            const days = weekDays.split(',');
            this.dayTargets.forEach((box) => {
                box.checked = days.includes(box.value);
            });
        }
    }

    expression() {
        if (this.frequencyValue === 'none' || this.frequencyValue === 'custom') {
            return '';
        }
        // No time: no minute, an expression of four fields, refused by the server, as the sentence says.
        const time = this.time;
        const [hours, minutes] = time ? time.split(':').map(Number) : [null, null];
        const prefix = time ? `${minutes} ${hours}` : '*';

        switch (this.frequencyValue) {
            case 'daily':
                return `${prefix} * * *`;
            case 'weekly':
                // No day: an expression of four fields, refused by the server, as the sentence says.
                return this.days.length > 0 ? `${prefix} * * ${this.days.join(',')}` : `${prefix} * *`;
            default:
                return `${prefix} ${this.monthDayInputTarget.value} * *`;
        }
    }

    writeSummary(expression) {
        this.sentenceTarget.textContent = this.sentence();
        this.codeTarget.textContent = expression;
        this.codeTarget.hidden = expression === '';
    }

    sentence() {
        if (this.frequencyValue === 'none' || this.frequencyValue === 'custom') {
            return '';
        }
        if (this.frequencyValue === 'weekly' && this.days.length === 0) {
            return this.noDayTextValue;
        }
        if (!this.time) {
            return this.noTimeTextValue;
        }
        const time = this.formatTime(this.time);
        if (this.frequencyValue === 'daily') {
            return this.dailyTextValue.replace('%time%', time);
        }
        if (this.frequencyValue === 'weekly') {
            const names = this.dayTargets.filter(day => day.checked).map(day => this.dayName(day.value));

            return this.weeklyTextValue.replace('%days%', new Intl.ListFormat(this.locale, {type: 'conjunction'}).format(names)).replace('%time%', time);
        }

        return this.monthlyTextValue.replace('%day%', this.monthDayInputTarget.value).replace('%time%', time);
    }

    dayName(value) {
        // 2024-01-01 is a Monday; 0 is Sunday, the 7th.
        const date = new Date(Date.UTC(2024, 0, value === '0' ? 7 : Number(value), 12));

        return new Intl.DateTimeFormat(this.locale, {weekday: 'long', timeZone: 'UTC'}).format(date);
    }

    // The time in the format of the language: "08:30", "8:30 AM".
    formatTime(time) {
        const [hours, minutes] = time.split(':').map(Number);

        return new Intl.DateTimeFormat(this.locale, {hour: 'numeric', minute: '2-digit', timeZone: 'UTC'}).format(new Date(Date.UTC(2024, 0, 1, hours, minutes)));
    }

    // Days chosen, in the order of cron (0 Sunday first).
    get days() {
        return this.dayTargets.filter(day => day.checked).map(day => day.value).sort();
    }

    // "HH:MM", or null when the time is empty.
    get time() {
        return /^\d{1,2}:\d{2}/.test(this.timeTarget.value) ? this.timeTarget.value.slice(0, 5) : null;
    }

    get field() {
        return this.customTarget.querySelector('input');
    }

    get locale() {
        return this.localeValue || document.documentElement.lang || undefined;
    }
}
