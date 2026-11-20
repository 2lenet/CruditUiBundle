<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\DataProvider;
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
        foreach (['value', 'to'] as $bound) {
            $input = $field->querySelector('input[name=filter_amount_' . $bound . ']');
            self::assertSame('number', $input?->getAttribute('type'));
            self::assertFalse($input->hasAttribute('inputmode'), 'a negative bound can be typed on a phone: ' . $bound);
        }
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
        self::assertFalse($number->querySelector('input#f-value')->hasAttribute('inputmode'), 'the keyboard of type="number" keeps the minus key (decimal on iOS has none)');
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

        $zero = $this->element('<twig:LleCruditUi:Filter:Field id="f" name="at" label="At" type="datetime" value="2026-11-05T10:30:00" />', '.crudit-ui-filter-field');
        self::assertSame('1', $zero->querySelector('input[type=datetime-local]')?->getAttribute('step'), 'seconds at zero are still seconds');
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

    public function testAPeriodSendsTheNameOfTheReadyMadePeriodChosen(): void
    {
        $period = $this->element('<twig:LleCruditUi:Filter:Field id="f" name="d" label="Date" type="period" value="2026-11-01" to="2026-11-30" preset="this_month" />', '.crudit-ui-filter-field');

        self::assertSame('this_month', $period->querySelector('input[type=hidden][name=d_preset]')?->getAttribute('value'));
        $names = [];
        foreach ($period->querySelectorAll('button[data-preset]') as $button) {
            $names[] = $button->getAttribute('data-preset');
        }
        self::assertSame(['today', 'last_7_days', 'last_30_days', 'this_month', 'last_month', 'next_month', 'this_year'], $names);

        $without = $this->element('<twig:LleCruditUi:Filter:Field id="f" name="d" label="Date" type="period" />', '.crudit-ui-filter-field');
        self::assertSame('', (string) $without->querySelector('input[type=hidden][name=d_preset]')?->getAttribute('value'));
    }

    public function testThePeriodsOfAProjectHaveNoName(): void
    {
        $period = $this->element("<twig:LleCruditUi:Filter:Period name=\"d\" label=\"Date\" :periods=\"[{label: 'Season', from: '2026-06-01', to: '2026-08-31'}]\" />", '.crudit-ui-period');

        self::assertNull($period->querySelector('button[data-from]')?->getAttribute('data-preset'));
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

    private const SAVED = "{saveUrl: '/crudit/filters/visit/saved', token: 'tok<en>', redirect: '/visits/', max: 3, items: [{id: 7, name: 'Late <b>visits</b>', summary: 'Status: Late', current: true, applyUrl: '/crudit/filters/visit/saved/7/apply', deleteUrl: '/crudit/filters/visit/saved/7/delete'}, {id: 9, name: 'This week', summary: '', current: false, applyUrl: '/crudit/filters/visit/saved/9/apply', deleteUrl: '/crudit/filters/visit/saved/9/delete'}]}";

    private function savedPage(string $saved = self::SAVED): \Dom\HTMLDocument
    {
        return $this->html($this->render(new TestKernel(), '<twig:LleCruditUi:Filter:Bar id="b" label="Filter the visits" resetUrl="/visits?reset=1" :saved="' . $saved . '" :filters="' . self::BAR_FILTERS . '" />'));
    }

    public function testWithoutSavedSetsTheBarHasNothingOfThem(): void
    {
        $form = $this->bar();

        self::assertSame('2lenet--crudit-ui-bundle--filters', $form->getAttribute('data-controller'));
        self::assertNull($form->querySelector('.crudit-ui-filter-saved'));
        self::assertNull($form->querySelector('.crudit-ui-filter-save'));
        self::assertNull($this->html($this->render(new TestKernel(), '<twig:LleCruditUi:Filter:Bar id="b" label="L" :filters="[]" />'))->getElementById('b-saved-form'));
    }

    public function testTheBarHasMyFiltersAndSaveTheseFilters(): void
    {
        $page = $this->savedPage();
        $form = $page->querySelector('form.crudit-ui-filter-bar');
        self::assertNotNull($form);

        self::assertSame('2lenet--crudit-ui-bundle--filters 2lenet--crudit-ui-bundle--saved-filters', $form->getAttribute('data-controller'));
        self::assertSame('["Late <b>visits<\/b>","This week"]', html_entity_decode((string) $form->getAttribute('data-2lenet--crudit-ui-bundle--saved-filters-names-value')));
        self::assertSame('3', $form->getAttribute('data-2lenet--crudit-ui-bundle--saved-filters-max-value'));

        // The save region is hidden in the panel, its three fields are off: a GET of the filters must not carry them.
        $region = $form->querySelector('dialog .crudit-ui-filter-save');
        self::assertNotNull($region);
        self::assertTrue($region->hasAttribute('hidden'));
        foreach (['_token', 'redirect'] as $name) {
            self::assertTrue($region->querySelector('[name="' . $name . '"]')?->hasAttribute('disabled'), $name . ' is off');
        }
        $name = $region->querySelector('input#b-save-name');
        self::assertNotNull($name);
        self::assertFalse($name->hasAttribute('name'), 'the name field is sent by no GET: no name until "Save"');
        self::assertFalse($name->hasAttribute('disabled'), 'it can be typed in');
        self::assertFalse($name->hasAttribute('required'), 'required only for the click on "Save": it would stop every send of the filters');
        $label = $region->querySelector('label');
        self::assertSame('Name of the filters', $label?->textContent);
        self::assertSame('b-save-name', $label->getAttribute('for'));
        self::assertSame('b-save-help', $name->getAttribute('aria-describedby'));
        $save = $region->querySelector('button[type=submit]');
        self::assertNotNull($save);
        self::assertSame('/crudit/filters/visit/saved', $save->getAttribute('formaction'));
        self::assertSame('post', $save->getAttribute('formmethod'));
        self::assertSame('Save', trim((string) $save->textContent));

        // The button of the panel that shows the field, and its state.
        $toggle = $form->querySelector('dialog button[aria-controls=b-save]');
        self::assertNotNull($toggle);
        self::assertSame('false', $toggle->getAttribute('aria-expanded'));
        self::assertStringContainsString('Save these filters', (string) $toggle->textContent);
        self::assertSame('button', $toggle->getAttribute('type'));
    }

    public function testTheSetsAreSentByAFormOutsideTheGetForm(): void
    {
        $page = $this->savedPage();

        $outside = $page->getElementById('b-saved-form');
        self::assertSame('post', $outside?->getAttribute('method'));
        self::assertNull($outside->closest('form.crudit-ui-filter-bar'), 'not inside the GET form');
        self::assertSame('tok<en>', $outside->querySelector('input[name=_token]')?->getAttribute('value'));
        self::assertSame('/visits/', $outside->querySelector('input[name=redirect]')?->getAttribute('value'));
        // Not in the GET form either: the address of a list never carries the token.
        $form = $page->querySelector('form.crudit-ui-filter-bar');
        self::assertSame('tok<en>', $form?->querySelector('dialog input[name=_token]')?->getAttribute('value'));
        self::assertTrue($form->querySelector('dialog input[name=_token]')->hasAttribute('disabled'));
        self::assertNull($form->querySelector('.crudit-ui-filter-saved input[name=_token]'));

        foreach ($page->querySelectorAll('.crudit-ui-filter-saved button[formaction]') as $button) {
            self::assertSame('b-saved-form', $button->getAttribute('form'), (string) $button->getAttribute('formaction'));
            self::assertSame('submit', $button->getAttribute('type'));
        }
    }

    public function testTheSetInUseIsCheckedNamedInTheButtonAndRemovedByItsLink(): void
    {
        $page = $this->savedPage();
        $saved = $page->querySelector('.crudit-ui-filter-saved');
        self::assertNotNull($saved);
        $button = $saved->querySelector('.crudit-ui-dropdown > button');
        self::assertNotNull($button);

        self::assertStringContainsString('crudit-ui-filter-saved__button--active', (string) $button->getAttribute('class'));
        self::assertSame('Late <b>visits</b>', $button->querySelector('.crudit-ui-filter-saved__label')?->textContent, 'a name is text, never HTML');
        self::assertNull($saved->querySelector('.crudit-ui-filter-saved__name b'));
        self::assertStringContainsString('(My filters)', (string) $button->textContent, 'the accessible name keeps what the button is');

        $items = $saved->querySelectorAll('.crudit-ui-filter-saved__item');
        self::assertCount(2, $items);
        $current = $items[0]->querySelector('.crudit-ui-filter-saved__apply');
        self::assertSame('a', strtolower((string) $current?->tagName));
        self::assertSame('/visits?reset=1', $current->getAttribute('href'));
        self::assertSame('true', $current->getAttribute('aria-current'));
        self::assertStringContainsString('Status: Late', (string) $current->textContent);
        self::assertStringContainsString('Applied: click to remove them', (string) $current->textContent);

        $other = $items[1]->querySelector('.crudit-ui-filter-saved__apply');
        self::assertSame('button', strtolower((string) $other?->tagName));
        self::assertSame('/crudit/filters/visit/saved/9/apply', $other->getAttribute('formaction'));
        self::assertNull($other->getAttribute('aria-current'));
    }

    public function testASetIsDeletedAfterAConfirmationThatNamesIt(): void
    {
        $item = $this->savedPage()->querySelectorAll('.crudit-ui-filter-saved__item')[1];

        $bin = $item->querySelector('.crudit-ui-icon-btn');
        self::assertSame('Delete the filters “This week”', trim((string) $bin?->textContent));
        self::assertSame('2lenet--crudit-ui-bundle--saved-filters#askDelete', $bin->getAttribute('data-action'));
        $confirm = $item->querySelector('.crudit-ui-filter-saved__confirm');
        self::assertTrue($confirm?->hasAttribute('hidden'), 'asked in place, only when the bin is clicked');
        self::assertSame('group', $confirm->getAttribute('role'));
        self::assertStringContainsString('Delete the filters “This week”?', (string) $confirm->textContent);
        self::assertSame('/crudit/filters/visit/saved/9/delete', $confirm->querySelector('button[formaction]')?->getAttribute('formaction'));
        self::assertNotNull($confirm->querySelector('[data-2lenet--crudit-ui-bundle--saved-filters-target=cancel]'));
    }

    public function testWithoutASetTheButtonSaysWhereToSaveOne(): void
    {
        $page = $this->savedPage("{saveUrl: '/s', token: 't', redirect: '/v', max: 10, items: []}");
        $saved = $page->querySelector('.crudit-ui-filter-saved');
        self::assertNotNull($saved);

        self::assertStringContainsString('Open “Filters”', (string) $saved->querySelector('.crudit-ui-filter-saved__empty')?->textContent);
        self::assertNull($saved->querySelector('.crudit-ui-filter-saved__list'));
        self::assertNull($saved->querySelector('.crudit-ui-filter-saved__button--active'));
        self::assertSame('My filters', $saved->querySelector('.crudit-ui-filter-saved__label')?->textContent);
    }

    public function testTheMaximumIsToldInTheList(): void
    {
        $page = $this->savedPage("{saveUrl: '/s', token: 't', redirect: '/v', max: 1, items: [{id: 1, name: 'One', summary: '', current: false, applyUrl: '/a', deleteUrl: '/d'}]}");

        self::assertStringContainsString('1 set at most', (string) $page->querySelector('.crudit-ui-filter-saved__note')?->textContent);
    }

    /**
     * @return iterable<string, array{int, string, string}>
     */
    public static function maxTexts(): iterable
    {
        yield 'one' => [1, 'The criteria of the panel are saved, then applied. 1 set at most.', '1 set at most: delete it to save another.'];
        yield 'three' => [3, 'The criteria of the panel are saved, then applied. 3 sets at most.', '3 sets at most: delete one to save another.'];
        yield 'none' => [0, 'The criteria of the panel are saved, then applied. 1 set at most.', '1 set at most: delete it to save another.'];
    }

    #[DataProvider('maxTexts')]
    public function testTheTextsOfTheMaximumAreWholeInTheSingularAndThePlural(int $max, string $help, string $reached): void
    {
        $page = $this->savedPage("{saveUrl: '/s', token: 't', redirect: '/v', max: " . $max . ", items: []}");

        self::assertSame($help, $page->getElementById('b-save-help')?->textContent);
        $form = $page->querySelector('form.crudit-ui-filter-bar');
        $texts = json_decode(html_entity_decode((string) $form?->getAttribute('data-2lenet--crudit-ui-bundle--saved-filters-texts-value')), true);
        self::assertIsArray($texts);
        self::assertSame($help, $texts['help']);
        self::assertSame($reached, $texts['maxReached']);
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
