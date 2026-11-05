<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Filter:Field and Filter:Operator.
 */
class FilterTest extends TestCase
{
    use RendersTwig;

    private const OPERATORS = "[{value: 'contains', label: 'Contains', icon: 'operator-contains'}, {value: 'isnull', label: 'Is empty', icon: 'operator-empty', noValue: true}]";

    protected function tearDown(): void
    {
        (new Filesystem())->remove(sys_get_temp_dir() . '/lle_crudit_ui_bundle');
    }

    public function testATextFilterSendsItsConditionAndItsValue(): void
    {
        $field = $this->element('<twig:LleCruditUi:Filter:Field id="f" name="filter_title" label="Title" :operators="' . self::OPERATORS . '" operator="contains" value="boiler" />', '.crudit-ui-filter-field');

        self::assertSame('Title', $field->querySelector('label.crudit-ui-filter-field__label')?->textContent);
        self::assertSame('f-value', $field->querySelector('label.crudit-ui-filter-field__label')->getAttribute('for'));
        $input = $field->querySelector('input#f-value');
        self::assertSame('filter_title_value', $input?->getAttribute('name'));
        self::assertSame('boiler', $input->getAttribute('value'));
        self::assertFalse($input->hasAttribute('disabled'));

        $radios = $field->querySelectorAll('input[type=radio][name=filter_title_op]');
        self::assertCount(2, $radios);
        self::assertTrue($radios[0]?->hasAttribute('checked'));
        self::assertFalse($radios[1]?->hasAttribute('checked'));
        self::assertSame('false', $field->getAttribute('data-no-value'));
    }

    public function testTheConditionButtonIsNamed(): void
    {
        $field = $this->element('<twig:LleCruditUi:Filter:Field id="f" name="filter_title" label="Title" :operators="' . self::OPERATORS . '" />', '.crudit-ui-filter-field');

        self::assertStringContainsString('Condition for Title: Contains', (string) ($field->querySelector('button .crudit-ui-visually-hidden')?->textContent));
        self::assertSame('Condition for Title: Is empty', $field->querySelectorAll('input[type=radio]')[1]?->getAttribute('data-name'));
    }

    public function testIsEmptyReplacesTheInputByItsLabel(): void
    {
        $field = $this->element('<twig:LleCruditUi:Filter:Field id="f" name="filter_title" label="Title" :operators="' . self::OPERATORS . '" operator="isnull" value="old" />', '.crudit-ui-filter-field');

        self::assertSame('true', $field->getAttribute('data-no-value'));
        self::assertTrue($field->querySelector('input#f-value')?->hasAttribute('disabled'), 'the old value is not sent');
        self::assertTrue($field->querySelector('.crudit-ui-filter-field__input')?->hasAttribute('hidden'));
        $noValue = $field->querySelector('.crudit-ui-filter-field__no-value');
        self::assertFalse($noValue?->hasAttribute('hidden'));
        self::assertSame('Is empty', trim((string) $noValue->textContent));
    }

    public function testARangeHasTwoNamedFieldsInAFieldset(): void
    {
        $field = $this->element('<twig:LleCruditUi:Filter:Field id="f" name="filter_amount" label="Amount" type="range" value="100" to="500" />', '.crudit-ui-filter-field');

        self::assertNotNull($field->querySelector('fieldset > legend'));
        self::assertNull($field->querySelector('.crudit-ui-operator'), 'no condition, no button');
        self::assertSame('100', $field->querySelector('input[name=filter_amount_value]')?->getAttribute('value'));
        self::assertSame('500', $field->querySelector('input[name=filter_amount_to]')?->getAttribute('value'));
        self::assertSame('Amount, minimum', $field->querySelector('label[for=f-value]')?->textContent);
        self::assertSame('Amount, maximum', $field->querySelector('label[for=f-to]')?->textContent);
    }

    public function testABooleanSendsAllTrueOrFalse(): void
    {
        $field = $this->element('<twig:LleCruditUi:Filter:Field id="f" name="filter_urgent" label="Urgent" type="boolean" value="true" />', '.crudit-ui-filter-field');

        $values = [];
        foreach ($field->querySelectorAll('input[type=radio][name=filter_urgent_value]') as $radio) {
            $values[$radio->getAttribute('value') ?? ''] = $radio->hasAttribute('checked');
        }
        self::assertSame(['all' => false, 'true' => true, 'false' => false], $values);

        $default = $this->element('<twig:LleCruditUi:Filter:Field id="f" name="filter_urgent" label="Urgent" type="boolean" />', '.crudit-ui-filter-field');
        self::assertTrue($default->querySelector('input[value=all]')?->hasAttribute('checked'), 'All by default');
    }

    public function testTheTypeSetsTheInput(): void
    {
        $number = $this->element('<twig:LleCruditUi:Filter:Field id="f" name="n" label="Duration" type="number" unit="min" />', '.crudit-ui-filter-field');
        self::assertSame('number', $number->querySelector('input#f-value')?->getAttribute('type'));
        self::assertSame('min', $number->querySelector('.crudit-ui-filter-field__affix')?->textContent);

        $datetime = $this->element('<twig:LleCruditUi:Filter:Field id="f" name="n" label="Date" type="datetime" />', '.crudit-ui-filter-field');
        self::assertSame('datetime-local', $datetime->querySelector('input#f-value')?->getAttribute('type'));
    }

    public function testAChoiceIsAListOfCheckboxes(): void
    {
        $field = $this->element("<twig:LleCruditUi:Filter:Field id=\"f\" name=\"filter_status\" label=\"Status\" type=\"choice\" value=\"planned,done\" :choices=\"[{value: 'draft', label: 'Draft'}, {value: 'planned', label: 'Planned'}, {value: 'done', label: 'Done'}]\" />", '.crudit-ui-filter-field');

        self::assertNotNull($field->querySelector('fieldset > legend'));
        $checked = [];
        foreach ($field->querySelectorAll('input[type=checkbox][name="filter_status_value[]"]') as $box) {
            $checked[(string) $box->getAttribute('value')] = $box->hasAttribute('checked');
        }
        self::assertSame(['draft' => false, 'planned' => true, 'done' => true], $checked);

        $list = $this->element("<twig:LleCruditUi:Filter:Field id=\"f\" name=\"s\" label=\"Status\" type=\"choice\" :value=\"['draft']\" :choices=\"[{value: 'draft', label: 'Draft'}]\" />", '.crudit-ui-filter-field');
        self::assertTrue($list->querySelector('input[value=draft]')?->hasAttribute('checked'), 'a list of values works as well as a string');
    }

    public function testAChoiceIsDisabledByIsEmpty(): void
    {
        $field = $this->element("<twig:LleCruditUi:Filter:Field id=\"f\" name=\"s\" label=\"Status\" type=\"choice\" operator=\"isnull\" value=\"done\" :operators=\"" . self::OPERATORS . "\" :choices=\"[{value: 'done', label: 'Done'}]\" />", '.crudit-ui-filter-field');

        self::assertTrue($field->querySelector('input[type=checkbox]')?->hasAttribute('disabled'));
    }

    public function testADateTimeWithSecondsAllowsThem(): void
    {
        $minutes = $this->element('<twig:LleCruditUi:Filter:Field id="f" name="at" label="At" type="datetime" value="2026-11-05T10:30" />', '.crudit-ui-filter-field');
        self::assertFalse($minutes->querySelector('input[type=datetime-local]')?->hasAttribute('step'));

        $seconds = $this->element('<twig:LleCruditUi:Filter:Field id="f" name="at" label="At" type="datetime" value="2026-11-05T10:30:15" />', '.crudit-ui-filter-field');
        self::assertSame('1', $seconds->querySelector('input[type=datetime-local]')?->getAttribute('step'));
    }

    public function testAPeriodAndABooleanAreDisabledByIsEmpty(): void
    {
        $operators = ":operators=\"" . self::OPERATORS . "\" operator=\"isnull\"";

        $period = $this->element('<twig:LleCruditUi:Filter:Field id="f" name="d" label="Date" type="period" value="2026-10-01" to="2026-10-31" ' . $operators . ' />', '.crudit-ui-filter-field');
        foreach ($period->querySelectorAll('input[type=date]') as $date) {
            self::assertTrue($date->hasAttribute('disabled'), 'the dates of a period are not sent without the script');
        }

        $boolean = $this->element('<twig:LleCruditUi:Filter:Field id="f" name="b" label="Done" type="boolean" ' . $operators . ' />', '.crudit-ui-filter-field');
        foreach ($boolean->querySelectorAll('input[name=b_value]') as $radio) {
            self::assertTrue($radio->hasAttribute('disabled'));
        }

        $enabled = $this->element('<twig:LleCruditUi:Filter:Field id="f" name="d" label="Date" type="period" value="2026-10-01" />', '.crudit-ui-filter-field');
        self::assertFalse($enabled->querySelector('input[type=date]')?->hasAttribute('disabled'));
    }

    public function testTheActionsOfTheProjectAreAddedToThoseOfTheBar(): void
    {
        $form = $this->bar('data-action="submit->analytics#track"');

        $actions = (string) $form->getAttribute('data-action');
        self::assertStringContainsString('change->2lenet--crudit-ui-bundle--filters#changed', $actions);
        self::assertStringContainsString('submit->analytics#track', $actions);
    }

    public function testAnOperatorWithoutIconRendersWithoutIt(): void
    {
        $field = $this->element("<twig:LleCruditUi:Filter:Field id=\"f\" name=\"n\" label=\"Name\" :operators=\"[{value: 'a', label: 'A'}, {value: 'b', label: 'B'}]\" />", '.crudit-ui-filter-field');

        self::assertNull($field->querySelector('.crudit-ui-operator__option-icon'));
        self::assertCount(2, $field->querySelectorAll('input[type=radio]'));
    }

    public function testAMainPeriodIsInlineInItsDropdown(): void
    {
        $form = $this->element("<twig:LleCruditUi:Filter:Bar id=\"b\" label=\"Filter\" :filters=\"[{id: 'date', label: 'Date', name: 'f_date', type: 'period', primary: true}, {id: 'since', label: 'Since', name: 'f_since', type: 'period'}]\" />", 'form');

        $main = $form->querySelector('.crudit-ui-filter-bar__quick .crudit-ui-period');
        self::assertNotNull($main);
        self::assertStringContainsString('crudit-ui-period--inline', (string) $main->getAttribute('class'));
        self::assertNull($main->querySelector('.crudit-ui-dropdown'), 'no dropdown in the dropdown');

        $other = $form->querySelector('.crudit-ui-filter-panel .crudit-ui-period');
        self::assertStringNotContainsString('crudit-ui-period--inline', (string) $other?->getAttribute('class'));
    }

    public function testTheBarKeepsItsActionsInTheDefaultAttribute(): void
    {
        $form = $this->bar();

        $actions = (string) $form->getAttribute('data-action');
        self::assertStringContainsString('filters#changed', $actions);
        self::assertStringContainsString('dropdown:close->2lenet--crudit-ui-bundle--filters#closed', $actions);
    }

    public function testAPeriodHasTwoDatesAndReadyMadePeriods(): void
    {
        $field = $this->element('<twig:LleCruditUi:Filter:Field id="f" name="filter_date" label="Date" type="period" value="2026-10-01" to="2026-10-31" />', '.crudit-ui-filter-field');

        self::assertSame('2026-10-01', $field->querySelector('input[type=date][name=filter_date_value]')?->getAttribute('value'));
        self::assertSame('2026-10-31', $field->querySelector('input[type=date][name=filter_date_to]')?->getAttribute('value'));
        self::assertSame('f-period-value', $field->querySelector('input[name=filter_date_value]')?->getAttribute('id'));
        self::assertCount(7, $field->querySelectorAll('button.crudit-ui-period__period[data-from][data-to]'));
        $summary = (string) $field->querySelector('button .crudit-ui-period__summary')?->textContent;
        self::assertStringContainsString('→', $summary);
        self::assertStringContainsString('2026', $summary);
        self::assertSame('From', $field->querySelector('label[for=f-period-value]')?->textContent);
        // The summary and the dates answer to the controller of the period, not to that of the Dropdown around them.
        self::assertSame('2lenet--crudit-ui-bundle--filter-period', $field->querySelector('.crudit-ui-period')?->getAttribute('data-controller'));
        self::assertNotNull($field->querySelector('[data-2lenet--crudit-ui-bundle--filter-period-target=summary]'));
        self::assertNotNull($field->querySelector('[data-2lenet--crudit-ui-bundle--filter-period-target=start]'));
    }

    public function testAPeriodWithoutDatesSaysAllDates(): void
    {
        $period = $this->element('<twig:LleCruditUi:Filter:Period id="p" name="d" label="Date" />', '.crudit-ui-period');

        self::assertSame('All dates', $period->querySelector('.crudit-ui-period__summary')?->textContent);
        self::assertSame('', $period->querySelector('input[name=d_value]')?->getAttribute('value'));
    }

    public function testAnInlinePeriodHasNoPanel(): void
    {
        $period = $this->element('<twig:LleCruditUi:Filter:Period id="p" name="d" label="Date" value="2026-10-01" inline />', '.crudit-ui-period');

        self::assertSame('crudit-ui-period crudit-ui-period--inline', $period->getAttribute('class'));
        self::assertNull($period->querySelector('.crudit-ui-dropdown'));
        self::assertNotNull($period->querySelector('input[name=d_to]'));
        self::assertStringContainsString('2026', (string) $period->querySelector('p.crudit-ui-period__summary')?->textContent);
    }

    private const BAR_FILTERS = "[
        {id: 'status', label: 'Status', name: 'f_status', type: 'choice', primary: true, active: true, summary: 'Planned', operators: [{value: 'in', label: 'Is one of', icon: 'operator-equals'}, {value: 'notin', label: 'Is none of', icon: 'operator-not-equals'}], operator: 'in', value: 'planned', choices: [{value: 'planned', label: 'Planned'}, {value: 'done', label: 'Done'}]},
        {id: 'title', label: 'Title', name: 'f_title', type: 'text', active: true, summary: 'boiler', value: 'boiler', operators: [{value: 'contains', label: 'Contains', icon: 'operator-contains'}], operator: 'contains'},
        {id: 'duration', label: 'Duration', name: 'f_duration', type: 'number', operators: [{value: 'gt', label: 'Is greater than', icon: 'operator-greater-than'}, {value: 'lt', label: 'Is less than', icon: 'operator-less-than'}]},
        {id: 'notes', label: 'Notes', name: 'f_notes', type: 'text', hidden: true, active: true, summary: 'seen', value: 'seen'},
    ]";

    private function bar(string $extra = ''): \Dom\Element
    {
        return $this->element('<twig:LleCruditUi:Filter:Bar id="b" label="Filter the visits" resetUrl="/visits?reset=1" applyName="visit_filter" :search="{name: \'q\', value: \'ab\', label: \'Search\'}" ' . $extra . ' :filters="' . self::BAR_FILTERS . '" />', 'form');
    }

    public function testTheBarIsAGetFormNamedByItsLabel(): void
    {
        $form = $this->bar();

        self::assertSame('get', $form->getAttribute('method'));
        self::assertSame('search', $form->getAttribute('role'));
        self::assertSame('Filter the visits', $form->getAttribute('aria-label'));
        self::assertSame('2lenet--crudit-ui-bundle--filters', $form->getAttribute('data-controller'));
        self::assertSame('filter', $form->querySelector('input[type=hidden][name=visit_filter]')?->getAttribute('value'));
        self::assertSame('ab', $form->querySelector('input[type=search][name=q]')?->getAttribute('value'));
    }

    public function testAHiddenFilterShowsNowhere(): void
    {
        $form = $this->bar();

        self::assertNull($form->querySelector('[name^=f_notes]'), 'not in the bar nor the panel');
        self::assertStringNotContainsString('seen', $form->textContent ?? '', 'no chip');
        self::assertStringNotContainsString('Notes', $form->textContent ?? '');
    }

    public function testTheMainFiltersAreInTheBarAndTheOthersInThePanel(): void
    {
        $form = $this->bar();

        $quick = $form->querySelectorAll('.crudit-ui-filter-bar__quick [name^=f_]');
        self::assertGreaterThan(0, count($quick));
        foreach ($quick as $field) {
            self::assertStringStartsWith('f_status', (string) $field->getAttribute('name'));
        }
        self::assertNull($form->querySelector('.crudit-ui-filter-panel [name^=f_status]'), 'a filter is not twice in the form');
        self::assertNotNull($form->querySelector('.crudit-ui-filter-panel__grid [name=f_title_value]'));
        self::assertNotNull($form->querySelector('.crudit-ui-filter-panel__grid [name=f_duration_value]'));
    }

    public function testChipsAreTheActiveFiltersOutsideTheBar(): void
    {
        $form = $this->bar();

        $chips = $form->querySelectorAll('ul.crudit-ui-chips > li');
        self::assertCount(1, $chips, 'the main filter shows its value in its button, the hidden one nowhere');
        self::assertStringContainsString('Title', (string) $chips[0]?->textContent);
        self::assertStringContainsString('boiler', (string) $chips[0]?->textContent);
        $remove = $chips[0]?->querySelector('button.crudit-ui-chip__remove');
        self::assertSame('Remove the filter Title: boiler', trim((string) $remove?->textContent));
        self::assertSame('title', $remove?->getAttribute('data-2lenet--crudit-ui-bundle--filters-id-param'));
        self::assertSame('2lenet--crudit-ui-bundle--filters#clear', $remove->getAttribute('data-action'));
        self::assertNotNull($form->querySelector('.crudit-ui-filter-bar__quick-button--active'));
    }

    public function testTheButtonCountsTheActiveFiltersAndControlsThePanel(): void
    {
        $form = $this->bar();

        $toggle = $form->querySelector('[data-2lenet--crudit-ui-bundle--filters-target=toggle]');
        self::assertSame('false', $toggle?->getAttribute('aria-expanded'));
        self::assertSame('b-panel', $toggle->getAttribute('aria-controls'));
        self::assertStringContainsString('2', (string) $toggle->textContent, 'status and title: 2 active, the hidden one is not counted');
        $panel = $form->querySelector('dialog#b-panel');
        self::assertSame('Filters', $panel?->querySelector('h2')?->textContent);
        self::assertTrue($panel->querySelector('button[type=submit]')?->hasAttribute('data-2lenet--crudit-ui-bundle--filters-target'));
        self::assertSame('/visits?reset=1', $panel->querySelector('a.crudit-ui-btn--ghost')?->getAttribute('href'));
    }

    public function testClearAllNeedsTwoActiveFilters(): void
    {
        self::assertNotNull($this->bar()->querySelector('.crudit-ui-filter-bar__tools > a.crudit-ui-btn--ghost'));

        $one = $this->element('<twig:LleCruditUi:Filter:Bar id="b" label="Filter" resetUrl="/r" :filters="[{id: \'t\', label: \'Title\', name: \'t\', active: true, summary: \'x\'}]" />', 'form');
        self::assertNull($one->querySelector('.crudit-ui-filter-bar__tools > a.crudit-ui-btn--ghost'));
    }

    public function testAFieldWithOneConditionStillSendsIt(): void
    {
        $field = $this->element('<twig:LleCruditUi:Filter:Field id="f" name="f_s" label="Status" type="choice" :operators="[{value: \'in\', label: \'Is\', icon: \'operator-equals\'}]" :choices="[{value: \'a\', label: \'A\'}]" />', '.crudit-ui-filter-field');

        self::assertSame('in', $field->querySelector('input[type=hidden][name=f_s_op]')?->getAttribute('value'));
        self::assertNull($field->querySelector('.crudit-ui-operator'));
    }
}
