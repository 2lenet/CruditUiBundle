<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;

/**
 * The form theme `@LleCruditUi/form/theme.html.twig`: labels above and attached, help and errors attached to the
 * input, required fields, boxes, switch and units, keyboards of a phone, counter of characters, segmented control,
 * colour, dates in lists.
 */
class FormThemeTest extends TestCase
{
    use RendersTwig;

    private TestKernel $kernel;

    protected function setUp(): void
    {
        $this->kernel = new TestKernel();
        $this->kernel->boot();
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove(sys_get_temp_dir() . '/lle_crudit_ui_bundle');
    }

    public function testATextFieldHasItsLabelAboveAndItsHelpAttached(): void
    {
        $form = $this->form()->add('name', TextType::class, ['label' => 'Name', 'help' => 'As on the invoice'])->getForm();
        $html = $this->renderForm($form);

        $row = $html->querySelector('.crudit-ui-field');
        self::assertNotNull($row);
        $label = $row->querySelector('.crudit-ui-field__label-row > label.crudit-ui-label');
        self::assertSame('form_name', $label?->getAttribute('for'));
        self::assertSame('Name *', trim(str_replace("\u{a0}", ' ', (string) $label->textContent)));
        self::assertSame('true', $label->querySelector('.crudit-ui-required')?->getAttribute('aria-hidden'));

        $input = $row->querySelector('input#form_name');
        self::assertSame('crudit-ui-input', $input?->getAttribute('class'));
        self::assertTrue($input->hasAttribute('required'));
        self::assertSame('form_name_help', $input->getAttribute('aria-describedby'));
        self::assertFalse($input->hasAttribute('aria-invalid'));
        self::assertSame('As on the invoice', $html->getElementById('form_name_help')?->textContent);
    }

    public function testAnErrorIsAttachedAfterTheHelpWithAnIconAndInvalidState(): void
    {
        $form = $this->form()->add('name', TextType::class, ['help' => 'As on the invoice', 'attr' => ['aria-describedby' => 'mine']])->getForm();
        $form->submit(['name' => '']);
        $form->get('name')->addError(new FormError('Enter the name.'));
        $html = $this->renderForm($form);

        $input = $html->querySelector('input#form_name');
        self::assertSame('mine form_name_help form_name_error', $input?->getAttribute('aria-describedby'));
        self::assertSame('true', $input->getAttribute('aria-invalid'));
        self::assertStringContainsString('crudit-ui-field--invalid', (string) $html->querySelector('.crudit-ui-field')?->getAttribute('class'));

        $error = $html->getElementById('form_name_error');
        self::assertSame('Enter the name.', trim((string) $error?->textContent));
        self::assertNotNull($error?->querySelector('svg'), 'the error is not told by the colour only');
    }

    public function testTheErrorsOfTheWholeFormAreAnAlert(): void
    {
        $form = $this->form()->add('name', TextType::class)->getForm();
        $form->submit(['name' => 'Bob']);
        $form->addError(new FormError('The record changed meanwhile.'));

        $errors = $this->renderForm($form)->querySelector('.crudit-ui-form-errors');
        self::assertSame('alert', $errors?->getAttribute('role'));
        self::assertSame('The record changed meanwhile.', trim((string) $errors->textContent));
    }

    public function testAnOptionalFieldHasNoStar(): void
    {
        $html = $this->renderForm($this->form()->add('notes', TextareaType::class, ['required' => false])->getForm());

        self::assertNull($html->querySelector('.crudit-ui-required'));
        self::assertSame('crudit-ui-input crudit-ui-textarea', $html->querySelector('textarea')?->getAttribute('class'));
    }

    public function testChoicesShownAsBoxesAreAFieldsetNamedByItsLegend(): void
    {
        $form = $this->form()->add('channels', ChoiceType::class, [
            'label' => 'Channels',
            'choices' => ['Mail' => 'mail', 'Phone' => 'phone'],
            'expanded' => true,
            'multiple' => true,
            'help' => 'One at least',
        ])->getForm();
        $html = $this->renderForm($form);

        $fieldset = $html->querySelector('fieldset.crudit-ui-field.crudit-ui-field__group');
        self::assertNotNull($fieldset);
        $legend = $fieldset->querySelector('legend.crudit-ui-label');
        self::assertSame('Channels', trim(str_replace(["\u{a0}", '*'], '', (string) $legend?->textContent)));
        self::assertNull($legend?->getAttribute('for'));

        $boxes = $fieldset->querySelectorAll('ul.crudit-ui-check-list > li > label.crudit-ui-check');
        self::assertCount(2, $boxes);
        $first = $boxes->item(0);
        self::assertSame('Mail', $first?->querySelector('.crudit-ui-check__label')?->textContent);
        self::assertNull($first->querySelector('.crudit-ui-required'), 'the star is on the legend, not on each box');
        self::assertSame('form_channels_help', $first->querySelector('input')?->getAttribute('aria-describedby'));
    }

    public function testACheckboxHasItsLabelNextToIt(): void
    {
        $html = $this->renderForm($this->form()->add('active', CheckboxType::class, ['label' => 'Active', 'required' => false])->getForm());

        $label = $html->querySelector('.crudit-ui-field > label.crudit-ui-check');
        self::assertSame('checkbox', $label?->querySelector('input.crudit-ui-check__input')?->getAttribute('type'));
        self::assertSame('Active', $label->querySelector('.crudit-ui-check__label')?->textContent);
        self::assertNull($html->querySelector('.crudit-ui-field__label-row'));
    }

    public function testASwitchKeepsItsLabelAboveAndWritesItsState(): void
    {
        $form = $this->form()->add('active', CheckboxType::class, ['label' => 'Active', 'required' => false, 'attr' => ['role' => 'switch']])->getForm();
        $html = $this->renderForm($form);

        self::assertSame('form_active', $html->querySelector('.crudit-ui-field__label-row > label')?->getAttribute('for'));
        $switch = $html->querySelector('label.crudit-ui-switch');
        $input = $switch?->querySelector('input#form_active');
        self::assertSame('switch', $input?->getAttribute('role'));
        self::assertSame('crudit-ui-switch__input', $input->getAttribute('class'));
        self::assertSame('Yes', $switch->querySelector('.crudit-ui-switch__state--on')?->textContent);
        self::assertSame('No', $switch->querySelector('.crudit-ui-switch__state--off')?->textContent);
        self::assertSame('true', $switch->querySelector('.crudit-ui-switch__state--on')->getAttribute('aria-hidden'));
    }

    public function testAnAmountAndAPercentageShowTheirUnit(): void
    {
        $form = $this->form()
            ->add('amount', MoneyType::class, ['currency' => 'EUR'])
            ->add('rate', PercentType::class, ['symbol' => '%'])
            ->getForm();
        $html = $this->renderForm($form);

        $groups = $html->querySelectorAll('.crudit-ui-input-group');
        self::assertCount(2, $groups);
        self::assertSame('€', trim((string) $groups->item(0)?->querySelector('.crudit-ui-input-group__unit')?->textContent));
        self::assertSame('%', $groups->item(1)?->querySelector('.crudit-ui-input-group__unit')?->textContent);
        self::assertSame('true', $groups->item(1)->querySelector('.crudit-ui-input-group__unit')->getAttribute('aria-hidden'));
    }

    public function testTheEmptyOptionOfARequiredListCannotBeChosenAgain(): void
    {
        $form = $this->form()
            ->add('kind', ChoiceType::class, ['choices' => ['A' => 'a'], 'placeholder' => 'Choose…'])
            ->add('other', ChoiceType::class, ['choices' => ['A' => 'a'], 'placeholder' => 'None', 'required' => false])
            ->getForm();
        $html = $this->renderForm($form);

        self::assertSame('crudit-ui-select', $html->querySelector('select#form_kind')?->getAttribute('class'));
        self::assertTrue((bool) $html->querySelector('select#form_kind option[value=""]')?->hasAttribute('disabled'));
        self::assertFalse((bool) $html->querySelector('select#form_other option[value=""]')?->hasAttribute('disabled'));
    }

    public function testASubFormACollectionAndARepeatedFieldHaveNoStarOfTheirOwn(): void
    {
        $address = $this->form()->create('address', FormType::class, ['label' => 'Address'])->add('street', TextType::class, ['label' => 'Street']);
        $form = $this->form()
            ->add($address)
            ->add('phones', CollectionType::class, ['label' => 'Phones', 'entry_type' => TextType::class, 'data' => ['0601020304']])
            ->add('password', RepeatedType::class, ['first_options' => ['label' => 'Password'], 'second_options' => ['label' => 'Again']])
            ->getForm();
        $html = $this->renderForm($form);

        foreach (['form_address', 'form_phones'] as $id) {
            $legend = $html->querySelector('fieldset:has(#' . $id . ') > legend');
            self::assertNotNull($legend, $id);
            self::assertNull($legend->querySelector('.crudit-ui-required'), 'Symfony sets required on every compound form: ' . $id);
        }
        self::assertNotNull($html->querySelector('label[for="form_address_street"] .crudit-ui-required'), 'a field of the sub-form keeps its star');
        self::assertNotNull($html->querySelector('label[for="form_password_first"] .crudit-ui-required'));
        self::assertNotNull($html->querySelector('label[for="form_password_second"] .crudit-ui-required'));
        self::assertNull($html->querySelector('fieldset:has(#form_password_first)'), 'a repeated field is its two rows');
    }

    public function testADateInListsHasItsStarAndEachListIsAttachedToItsHelpAndError(): void
    {
        $form = $this->form()->add('birth', DateType::class, ['label' => 'Birth', 'widget' => 'choice', 'help' => 'Day, month, year'])->getForm();
        $form->submit(['birth' => ['day' => '', 'month' => '', 'year' => '']]);
        $form->get('birth')->addError(new FormError('Enter the date.'));
        $html = $this->renderForm($form);

        self::assertNotNull($html->querySelector('fieldset:has(#form_birth_year) > legend .crudit-ui-required'));
        foreach (['day', 'month', 'year'] as $part) {
            $select = $html->querySelector('select#form_birth_' . $part);
            self::assertSame('form_birth_help form_birth_error', $select?->getAttribute('aria-describedby'), $part);
            self::assertSame('true', $select->getAttribute('aria-invalid'), $part);
        }
        self::assertNotNull($html->getElementById('form_birth_error'));
    }

    public function testTheListsOfADatetimeAreAttachedToTheErrorOfTheDatetime(): void
    {
        $form = $this->form()->add('at', DateTimeType::class, ['date_widget' => 'choice', 'time_widget' => 'choice'])->getForm();
        $form->submit(['at' => []]);
        $form->get('at')->addError(new FormError('Enter the date and the time.'));
        $html = $this->renderForm($form);

        foreach (['form_at_date_year', 'form_at_time_hour'] as $id) {
            $select = $html->querySelector('select#' . $id);
            self::assertSame('form_at_error', $select?->getAttribute('aria-describedby'), $id);
            self::assertSame('true', $select->getAttribute('aria-invalid'), $id);
        }
    }

    public function testAFieldOutsideADateIsNotAttachedToItsParent(): void
    {
        $address = $this->form()->create('address', FormType::class, ['help' => 'Where to deliver'])->add('street', TextType::class);
        $form = $this->form()->add($address)->getForm();
        $form->submit(['address' => ['street' => '']]);
        $form->get('address')->addError(new FormError('Unknown address.'));
        $html = $this->renderForm($form);

        $input = $html->querySelector('input#form_address_street');
        self::assertNotNull($input);
        self::assertFalse($input->hasAttribute('aria-describedby'));
        self::assertFalse($input->hasAttribute('aria-invalid'));
    }

    public function testTheKeyboardOfAPhoneFollowsTheKindOfTheValue(): void
    {
        $form = $this->form()
            ->add('quantity', IntegerType::class)
            ->add('stock', IntegerType::class, ['attr' => ['min' => 0]])
            ->add('offset', IntegerType::class, ['attr' => ['min' => -10]])
            ->add('weight', NumberType::class)
            ->add('price', MoneyType::class)
            ->add('rate', PercentType::class)
            ->add('email', EmailType::class)
            ->add('phone', TelType::class)
            ->add('site', UrlType::class, ['default_protocol' => null])
            ->add('native', NumberType::class, ['html5' => true])
            ->add('count', NumberType::class, ['scale' => 0])
            ->add('code', TelType::class, ['attr' => ['inputmode' => 'numeric']])
            ->add('name', TextType::class)
            ->getForm();
        $html = $this->renderForm($form);

        $modes = ['stock' => 'numeric', 'weight' => 'decimal', 'price' => 'decimal', 'rate' => 'decimal', 'count' => 'numeric', 'code' => 'numeric'];
        foreach ($modes as $name => $mode) {
            self::assertSame($mode, $html->getElementById('form_' . $name)?->getAttribute('inputmode'), $name);
        }
        self::assertSame('text', $html->getElementById('form_weight')?->getAttribute('type'), 'a number is typed as text (decimal comma)');
        self::assertFalse((bool) $html->getElementById('form_native')?->hasAttribute('inputmode'), 'type="number" has the keyboard of the browser');
        self::assertFalse((bool) $html->getElementById('form_quantity')?->hasAttribute('inputmode'), 'an integer without a minimum may be negative: the digit pad of iOS has no minus key');
        self::assertFalse((bool) $html->getElementById('form_offset')?->hasAttribute('inputmode'), 'an integer with a negative minimum keeps the keyboard with a minus key');
        self::assertFalse((bool) $html->getElementById('form_name')?->hasAttribute('inputmode'));
        foreach (['email' => 'email', 'phone' => 'tel', 'site' => 'url'] as $name => $type) {
            $input = $html->getElementById('form_' . $name);
            self::assertSame($type, $input?->getAttribute('type'));
            self::assertFalse($input->hasAttribute('inputmode'), 'the type gives the keyboard: ' . $name);
        }
        self::assertSame('false', $html->getElementById('form_email')?->getAttribute('spellcheck'));
        self::assertFalse((bool) $html->getElementById('form_name')?->hasAttribute('spellcheck'));
    }

    public function testALongTextWithAMaximumLengthHasACounterAttachedAfterItsHelp(): void
    {
        $form = $this->form()
            ->add('notes', TextareaType::class, ['help' => 'For the driver', 'attr' => ['maxlength' => 80], 'data' => 'Back door'])
            ->add('email', EmailType::class, ['attr' => ['maxlength' => 80]])
            ->add('name', TextType::class, ['attr' => ['maxlength' => 120]])
            ->add('code', TextType::class, ['attr' => ['maxlength' => 8]])
            ->add('memo', TextareaType::class, ['attr' => ['maxlength' => 50]])
            ->getForm();
        $form->submit(['notes' => 'Back door', 'email' => 'a@b.c', 'name' => 'Bob', 'code' => 'AB', 'memo' => '']);
        $form->get('notes')->addError(new FormError('Too long.'));
        $html = $this->renderTemplate($form, "{{ form_row(form.notes) }}{{ form_row(form.email) }}{{ form_row(form.name) }}{{ form_row(form.code, {counter: true}) }}{{ form_row(form.memo, {counter: false}) }}");

        $notes = $html->getElementById('form_notes');
        self::assertSame('form_notes_help form_notes_count form_notes_error', $notes?->getAttribute('aria-describedby'));
        self::assertSame('2lenet--crudit-ui-bundle--char-count', $notes->getAttribute('data-controller'));
        self::assertSame('%length% / 80 characters', $notes->getAttribute('data-2lenet--crudit-ui-bundle--char-count-many-value'));
        self::assertSame('%count% characters left', $notes->getAttribute('data-2lenet--crudit-ui-bundle--char-count-left-many-value'));

        $counter = $html->getElementById('form_notes_count');
        self::assertSame('crudit-ui-field__count', $counter?->getAttribute('class'));
        self::assertSame('9 / 80 characters', $counter->querySelector('[data-crudit-ui-count]')?->textContent);
        self::assertSame('9 / 80 characters', trim((string) $counter->textContent), 'the description of the input is the count alone, not the message told');
        $live = $html->getElementById('form_notes_count_live');
        self::assertSame('polite', $live?->getAttribute('aria-live'));
        self::assertSame($live, $counter->nextElementSibling, 'the live region is next to the counter');
        $help = $html->getElementById('form_notes_help');
        self::assertSame($counter, $help?->nextElementSibling, 'the counter is under the help, above the error');

        self::assertNull($html->getElementById('form_email_count'), 'a text on one line has none by default');
        self::assertNull($html->getElementById('form_name_count'), 'a text on one line has none (Symfony puts the length of the column in maxlength)');
        self::assertFalse((bool) $html->getElementById('form_name')?->hasAttribute('data-controller'));
        self::assertSame('2 / 8 characters', $html->getElementById('form_code_count')?->querySelector('[data-crudit-ui-count]')?->textContent, 'asked with counter: true');
        self::assertNull($html->getElementById('form_memo_count'), 'taken away with counter: false');
        self::assertFalse((bool) $html->getElementById('form_memo')?->hasAttribute('aria-describedby'));
    }

    public function testThePluralOfTheCharactersLeftFollowsTheLocaleOfTheRequest(): void
    {
        $form = $this->form()
            ->add('notes', TextareaType::class, ['attr' => ['maxlength' => 80]])
            ->getForm();
        self::assertFalse((bool) $this->renderForm($form)->getElementById('form_notes')?->hasAttribute('data-2lenet--crudit-ui-bundle--char-count-locale-value'), 'without a request, the lang of the page');

        /** @var RequestStack $requests */
        $requests = $this->container()->get('request_stack');
        $request = new Request();
        $request->setLocale('fr');
        $requests->push($request);
        $html = $this->renderForm($form);
        $requests->pop();

        self::assertSame('fr', $html->getElementById('form_notes')?->getAttribute('data-2lenet--crudit-ui-bundle--char-count-locale-value'));
    }

    public function testALongTextRenderedWithoutItsRowHasNoCounterToAttach(): void
    {
        $form = $this->form()
            ->add('notes', TextareaType::class, ['attr' => ['maxlength' => 80]])
            ->getForm();
        $html = $this->renderTemplate($form, '{{ form_widget(form.notes) }}');

        $notes = $html->getElementById('form_notes');
        self::assertNotNull($notes);
        self::assertFalse($notes->hasAttribute('aria-describedby'), 'no aria-describedby to a counter that is not rendered');
        self::assertFalse($notes->hasAttribute('data-controller'));
    }

    public function testShortChoicesWithOneAnswerCanBeASegmentedControl(): void
    {
        $form = $this->form()
            ->add('size', ChoiceType::class, ['label' => 'Size', 'choices' => ['Small' => 's', 'Large' => 'l'], 'expanded' => true, 'help' => 'Of the box', 'data' => 'l'])
            ->add('channels', ChoiceType::class, ['choices' => ['Mail' => 'mail'], 'expanded' => true, 'multiple' => true])
            ->getForm();
        $html = $this->renderTemplate($form, "{{ form_row(form.size, {segmented: true}) }}{{ form_row(form.channels, {segmented: true}) }}");

        $fieldset = $html->querySelector('fieldset.crudit-ui-field__group:has(#form_size)');
        self::assertSame('Size', trim(str_replace(["\u{a0}", '*'], '', (string) $fieldset?->querySelector('legend')?->textContent)));
        $control = $html->getElementById('form_size');
        self::assertNotNull($control);
        self::assertSame('div', strtolower($control->tagName));
        self::assertSame('crudit-ui-segmented', $control->getAttribute('class'));
        $options = $control->querySelectorAll('label.crudit-ui-segmented__option');
        self::assertCount(2, $options);
        $large = $options->item(1);
        self::assertNotNull($large);
        $radio = $large->querySelector('input.crudit-ui-segmented__input');
        self::assertSame('radio', $radio?->getAttribute('type'));
        self::assertTrue($radio->hasAttribute('checked'));
        self::assertSame('form_size_help', $radio->getAttribute('aria-describedby'));
        self::assertSame('Large', $large->querySelector('.crudit-ui-segmented__label')?->textContent);
        self::assertNull($control->querySelector('.crudit-ui-check'));

        self::assertNotNull($html->querySelector('ul#form_channels.crudit-ui-check-list'), 'several answers stay boxes');
    }

    public function testASegmentedControlNotRequiredHasAnEmptyChoiceFirst(): void
    {
        $form = $this->form()
            ->add('size', ChoiceType::class, ['choices' => ['Small' => 's', 'Large' => 'l'], 'expanded' => true, 'required' => false])
            ->add('shape', ChoiceType::class, ['choices' => ['Round' => 'r', 'Square' => 'q'], 'expanded' => true, 'required' => false, 'placeholder' => false])
            ->getForm();
        $html = $this->renderTemplate($form, "{{ form_row(form.size, {segmented: true}) }}{{ form_row(form.shape, {segmented: true}) }}");

        $labels = [];
        foreach ($html->querySelectorAll('#form_size .crudit-ui-segmented__label') as $label) {
            $labels[] = $label->textContent;
        }
        self::assertSame(['None', 'Small', 'Large'], $labels, 'not required: an empty choice first, to take the answer away');
        self::assertSame('', $html->querySelector('#form_size .crudit-ui-segmented__input')?->getAttribute('value'));
        self::assertCount(2, $html->querySelectorAll('#form_shape .crudit-ui-segmented__option'), 'placeholder: false takes the empty choice away');
    }

    public function testAColourShowsItsCode(): void
    {
        $html = $this->renderForm($this->form()->add('tint', ColorType::class, ['data' => '#1e40af'])->getForm());

        $input = $html->querySelector('.crudit-ui-input-group--color > input#form_tint');
        self::assertSame('color', $input?->getAttribute('type'));
        self::assertSame('2lenet--crudit-ui-bundle--color-code', $input->getAttribute('data-controller'));
        self::assertSame('#1e40af', $html->querySelector('.crudit-ui-input-group--color > [data-color-code]')?->textContent);
        self::assertSame('true', $html->querySelector('.crudit-ui-input-group--color > [data-color-code]')->getAttribute('aria-hidden'), 'hidden like the units: the picker tells its value');
    }

    public function testTheListsOfADateAreOnOneLineEachNamed(): void
    {
        $form = $this->form()
            ->add('birth', DateType::class, ['widget' => 'choice'])
            ->add('day', DateType::class, ['widget' => 'single_text'])
            ->add('typed', DateType::class, ['widget' => 'text'])
            ->getForm();
        $html = $this->renderForm($form);

        self::assertStringContainsString('crudit-ui-date-lists', (string) $html->getElementById('form_birth')?->getAttribute('class'));
        foreach (['day' => 'Day', 'month' => 'Month', 'year' => 'Year'] as $part => $name) {
            self::assertSame($name, $html->getElementById('form_birth_' . $part)?->getAttribute('aria-label'), 'each list is named, the legend names the date');
        }
        self::assertFalse((bool) $html->getElementById('form_day')?->hasAttribute('aria-label'), 'a date in one input is named by its label');
        self::assertStringNotContainsString('crudit-ui-date-lists', (string) $html->getElementById('form_day')?->getAttribute('class'));
        self::assertStringContainsString('crudit-ui-date-lists', (string) $html->getElementById('form_typed')?->getAttribute('class'), 'the inputs of a date are on one line too');
        foreach (['day', 'month', 'year'] as $part) {
            $input = $html->getElementById('form_typed_' . $part);
            self::assertSame('numeric', $input?->getAttribute('inputmode'), 'a part of a date in inputs is never negative: ' . $part);
            self::assertSame('year' === $part, str_contains((string) $input->getAttribute('class'), 'crudit-ui-input--year'), 'only the year is wider: ' . $part);
        }
        self::assertFalse((bool) $html->getElementById('form_day')?->hasAttribute('inputmode'), 'a date in one input has the picker of the browser');
    }

    public function testToggletipOpensItsPanel(): void
    {
        $html = $this->html($this->render($this->kernel, '<twig:LleCruditUi:Toggletip label="Help on the field Phone" id="phone-help">With the <b>country code</b>.</twig:LleCruditUi:Toggletip>'));

        $button = $html->querySelector('button.crudit-ui-toggletip');
        self::assertSame('button', $button?->getAttribute('type'));
        self::assertSame('phone-help', $button->getAttribute('popovertarget'));
        self::assertSame('2lenet--crudit-ui-bundle--toggletip', $button->getAttribute('data-controller'));
        self::assertSame('Help on the field Phone', $button->querySelector('.crudit-ui-visually-hidden')?->textContent);
        $panel = $html->getElementById('phone-help');
        self::assertTrue((bool) $panel?->hasAttribute('popover'));
        self::assertSame('country code', $panel->querySelector('b')?->textContent);
    }

    /**
     * @return \Symfony\Component\Form\FormBuilderInterface<mixed>
     */
    private function form(): \Symfony\Component\Form\FormBuilderInterface
    {
        return Forms::createFormFactory()->createNamedBuilder('form', FormType::class);
    }

    /**
     * @param FormInterface<mixed> $form
     */
    private function renderForm(FormInterface $form): \Dom\HTMLDocument
    {
        /** @var Environment $twig */
        $twig = $this->container()->get('twig');
        $template = $twig->createTemplate("{% form_theme form with ['@LleCruditUi/form/theme.html.twig'] only %}{{ form(form) }}");

        return $this->html($template->render(['form' => $form->createView()]));
    }

    /**
     * @param FormInterface<mixed> $form
     */
    private function renderTemplate(FormInterface $form, string $template): \Dom\HTMLDocument
    {
        /** @var Environment $twig */
        $twig = $this->container()->get('twig');

        return $this->html($twig->createTemplate("{% form_theme form with ['@LleCruditUi/form/theme.html.twig'] only %}" . $template)->render(['form' => $form->createView()]));
    }

    private function container(): ContainerInterface
    {
        /** @var ContainerInterface $container */
        $container = $this->kernel->getContainer()->get('test.service_container');

        return $container;
    }
}
