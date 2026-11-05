import { Controller } from '@hotwired/stimulus';

/*
 * Filter:Field. The conditions (radios of the Filter:Operator) are what the form sends, so the form works without
 * this script; the controller only keeps the display in step with the choice:
 * - the pictogram and the accessible name of the button of the condition (read on the radio chosen);
 * - an "is empty" condition (data-no-value="true") replaces the input by the label of the condition, and disables
 *   the input: the previous value is not sent, a disabled field being left out of a form.
 * The panel of the Operator is closed by the Dropdown (the choice does not close it, Tab goes on to the input).
 *
 * Event: "operator" (2lenet--crudit-ui-bundle--filter-field:operator), on the root, detail {value, noValue}.
 */
export default class extends Controller {
    static targets = ['operatorIcon', 'operatorName', 'noValue', 'input'];

    choose(event) {
        const radio = event.target;
        const noValue = radio.dataset.noValue === 'true';
        const icon = radio.parentElement.querySelector('svg');

        if (this.hasOperatorIconTarget) {
            // A condition without a pictogram empties the button of the previous one.
            this.operatorIconTarget.replaceChildren(...(icon ? [icon.cloneNode(true)] : []));
        }
        if (this.hasOperatorNameTarget) {
            this.operatorNameTarget.textContent = radio.dataset.name;
        }
        if (this.hasNoValueTarget) {
            this.noValueTarget.hidden = !noValue;
            this.noValueTarget.textContent = radio.parentElement.textContent.trim();
        }
        if (this.hasInputTarget) {
            this.inputTarget.hidden = noValue;
            this.inputTarget.querySelectorAll('input').forEach((input) => {
                input.disabled = noValue;
            });
        }
        this.element.dataset.noValue = String(noValue);
        this.dispatch('operator', {detail: {value: radio.value, noValue}});
    }
}
