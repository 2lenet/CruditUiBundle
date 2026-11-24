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
use Symfony\Component\Form\Extension\Core\Type\DateIntervalType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
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

    public function testARichHelpIsADiv(): void
    {
        $form = $this->form()->add('name', TextType::class, ['help' => '<ul><li>One</li><li>Two</li></ul>', 'help_html' => true])->getForm();
        $html = $this->renderForm($form);

        $help = $html->getElementById('form_name_help');
        self::assertSame('div', $help?->localName);
        self::assertCount(2, $help->querySelectorAll('ul > li'));
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

    public function testTheLabelOfACheckboxTakesItsLabelAttr(): void
    {
        $form = $this->form()
            ->add('active', CheckboxType::class, ['required' => false, 'label_attr' => ['class' => 'app-strong', 'data-x' => '1']])
            ->add('enabled', CheckboxType::class, ['required' => false, 'label_attr' => ['class' => 'app-strong'], 'attr' => ['role' => 'switch']])
            ->getForm();
        $html = $this->renderForm($form);

        $label = $html->querySelector('label.crudit-ui-check');
        self::assertSame('crudit-ui-check app-strong', $label?->getAttribute('class'));
        self::assertSame('1', $label->getAttribute('data-x'));
        // A switch: label_attr on its label above, not on the switch
        self::assertSame('crudit-ui-switch', $html->querySelector('label.crudit-ui-switch')?->getAttribute('class'));
        self::assertSame('crudit-ui-label app-strong', $html->querySelector('label[for="form_enabled"]')?->getAttribute('class'));
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
        // The unit is read with the input, before its help
        $unit = $html->getElementById('form_rate_unit');
        self::assertSame('%', $unit?->textContent);
        self::assertFalse($unit->hasAttribute('aria-hidden'));
        self::assertSame('form_amount_unit', $html->getElementById('form_amount')?->getAttribute('aria-describedby'));
        self::assertSame('€', trim((string) $html->getElementById('form_amount_unit')?->textContent));
    }

    public function testTheUnitIsReadBeforeTheHelpAndTheError(): void
    {
        $form = $this->form()->add('rate', PercentType::class, ['symbol' => '%', 'help' => 'Of the total'])->getForm();
        $form->get('rate')->addError(new FormError('Too high'));
        $html = $this->renderForm($form);

        self::assertSame('form_rate_unit form_rate_help form_rate_error', $html->getElementById('form_rate')?->getAttribute('aria-describedby'));
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

    public function testAnIntervalInPartsHasItsErrorOnceAndItsStarOnTheLabelOfTheInterval(): void
    {
        $form = $this->form()
            ->add('duration', DateIntervalType::class, ['widget' => 'integer', 'with_days' => true, 'with_hours' => true])
            ->getForm();
        $form->get('duration')->addError(new FormError('Invalid interval'));
        $html = $this->renderTemplate($form, '{{ form_row(form.duration) }}');

        self::assertSame(1, $html->querySelectorAll('[id="form_duration_error"]')->length, 'one error, one id');
        self::assertSame(1, $html->querySelectorAll('.crudit-ui-required')->length, 'the star is on the label of the interval, not on each part');
    }

    public function testThePluralOfTheCharactersLeftFollowsTheLocaleOfTheRequest(): void
    {
        $form = $this->form()
            ->add('notes', TextareaType::class, ['attr' => ['maxlength' => 80]])
            ->getForm();
        self::assertFalse((bool) $this->renderForm($form)->getElementById('form_notes')?->hasAttribute('data-2lenet--crudit-ui-bundle--char-count-locale-value'), 'without a request, the lang of the page');

        /** @var RequestStack $requests */
        $requests = $this->container()->get('request_stack');
        // A locale of Symfony (fr_FR, ar_SA) is not a tag BCP 47: Intl.PluralRules would throw a RangeError
        foreach (['fr' => 'fr', 'fr_FR' => 'fr-FR', 'ar_SA' => 'ar-SA'] as $locale => $tag) {
            $request = new Request();
            $request->setLocale($locale);
            $requests->push($request);
            $html = $this->renderForm($form);
            $requests->pop();

            self::assertSame($tag, $html->getElementById('form_notes')?->getAttribute('data-2lenet--crudit-ui-bundle--char-count-locale-value'), $locale);
        }
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

    public function testAListWithSearchIsAComboboxWithItsChoicesAsTokens(): void
    {
        $form = $this->form()
            ->add('zones', ChoiceType::class, [
                'label' => 'Zones',
                'choices' => ['North' => 'n', 'South' => 's', 'East & West' => 'ew'],
                'multiple' => true,
                'help' => 'Where the technician goes',
                'data' => ['s', 'ew'],
            ])
            ->getForm();
        $html = $this->renderTemplate($form, '{{ form_row(form.zones, {combobox: true}) }}');

        self::assertSame('form_zones', $html->querySelector('label.crudit-ui-label')?->getAttribute('for'), 'a label, not a legend');
        $root = $html->querySelector('.crudit-ui-combobox');
        self::assertSame('2lenet--crudit-ui-bundle--combobox', $root?->getAttribute('data-controller'));
        self::assertSame('true', $root->getAttribute('data-2lenet--crudit-ui-bundle--combobox-multiple-value'));
        self::assertFalse($root->hasAttribute('data-2lenet--crudit-ui-bundle--combobox-url-value'));
        self::assertSame('Remove %label%', $root->getAttribute('data-2lenet--crudit-ui-bundle--combobox-remove-text-value'));

        $input = $html->getElementById('form_zones');
        self::assertNotNull($input);
        self::assertSame('input', strtolower($input->tagName));
        self::assertSame('combobox', $input->getAttribute('role'));
        self::assertSame('list', $input->getAttribute('aria-autocomplete'));
        self::assertSame('false', $input->getAttribute('aria-expanded'));
        self::assertSame('form_zones_listbox', $input->getAttribute('aria-controls'));
        self::assertSame('form_zones_help', $input->getAttribute('aria-describedby'));
        self::assertSame('true', $input->getAttribute('aria-required'));
        self::assertFalse($input->hasAttribute('name'), 'the hidden list sends the value');
        self::assertFalse($input->hasAttribute('required'));
        self::assertSame('Search…', $input->getAttribute('placeholder'));

        $tokens = $html->querySelector('ul.crudit-ui-combobox__tokens');
        self::assertSame('Zones: choices made', $tokens?->getAttribute('aria-label'));
        self::assertFalse($tokens->hasAttribute('hidden'));
        $items = $tokens->querySelectorAll('li.crudit-ui-combobox__token');
        self::assertCount(2, $items);
        self::assertSame('s', $items->item(0)?->getAttribute('data-value'));
        self::assertSame('South', $items->item(0)->querySelector('.crudit-ui-combobox__token-label')?->textContent);
        $remove = $items->item(1)?->querySelector('button.crudit-ui-combobox__remove');
        self::assertSame('button', $remove?->getAttribute('type'));
        self::assertSame('Remove East & West', $remove->querySelector('.crudit-ui-visually-hidden')?->textContent);

        $listbox = $html->getElementById('form_zones_listbox');
        self::assertSame('listbox', $listbox?->getAttribute('role'));
        self::assertSame('Zones', $listbox->getAttribute('aria-label'));
        self::assertSame('true', $listbox->getAttribute('aria-multiselectable'));
        self::assertTrue($listbox->hasAttribute('hidden'));
        self::assertNotNull($root->querySelector('[role="status"][aria-live="polite"]'));

        $select = $root->querySelector('select[name="form[zones][]"]');
        self::assertNotNull($select);
        self::assertTrue($select->hasAttribute('hidden'));
        self::assertTrue($select->hasAttribute('multiple'));
        self::assertFalse($select->hasAttribute('required'), 'the browser could not show the message of a hidden list');
        $selected = array_map(static fn (\Dom\Element $option): string => (string) $option->getAttribute('value'), iterator_to_array($select->querySelectorAll('option[selected]')));
        self::assertSame(['s', 'ew'], $selected);
        self::assertCount(3, $select->querySelectorAll('option'));
    }

    public function testAListWithSearchWithOneAnswerHasAnEmptyChoiceAndNoToken(): void
    {
        $form = $this->form()
            ->add('priority', ChoiceType::class, ['choices' => ['Low' => 'low', 'High' => 'high'], 'required' => false, 'placeholder' => 'Any priority'])
            ->getForm();
        $html = $this->renderTemplate($form, '{{ form_widget(form.priority, {combobox: true}) }}');

        $root = $html->querySelector('.crudit-ui-combobox');
        self::assertSame('false', $root?->getAttribute('data-2lenet--crudit-ui-bundle--combobox-multiple-value'));
        self::assertTrue((bool) $html->querySelector('ul.crudit-ui-combobox__tokens')?->hasAttribute('hidden'));
        self::assertFalse((bool) $html->getElementById('form_priority_listbox')?->hasAttribute('aria-multiselectable'));
        $input = $html->getElementById('form_priority');
        self::assertFalse((bool) $input?->hasAttribute('aria-required'));
        self::assertSame('Any priority', $input?->getAttribute('placeholder'));
        $select = $root->querySelector('select[name="form[priority]"]');
        self::assertFalse((bool) $select?->hasAttribute('multiple'));
        self::assertSame('', $select?->querySelector('option')?->getAttribute('value'), 'an empty choice: the choice can be removed');
    }

    public function testAListWithSearchOfTheServerSendsTheValuesJoined(): void
    {
        // A field type of its own whose widget is the list with search (as AutocompleteType of CruditBundle): it reads
        // the values joined, with a data transformer
        $form = $this->form()->add('technicians', TextType::class, ['label' => 'Technicians'])->getForm();
        $form->get('technicians')->addError(new FormError('Choose a technician.'));
        /** @var Environment $twig */
        $twig = $this->container()->get('twig');
        $template = $twig->createTemplate(<<<'TWIG'
            {% form_theme form with ['remote_combobox_theme.html.twig'] only %}
            {{ form_row(form.technicians, {
                multiple: true,
                combobox_url: '/technician/autocomplete',
                combobox_selected: [{value: '3', label: 'Ana <Diaz>'}, {value: '7', label: 'Bob'}],
            }) }}
            TWIG);
        $html = $this->html($template->render(['form' => $form->createView()]));

        $root = $html->querySelector('.crudit-ui-combobox');
        self::assertSame('/technician/autocomplete', $root?->getAttribute('data-2lenet--crudit-ui-bundle--combobox-url-value'));
        self::assertSame('0', $root->getAttribute('data-2lenet--crudit-ui-bundle--combobox-min-length-value'));
        self::assertSame(',', $root->getAttribute('data-2lenet--crudit-ui-bundle--combobox-separator-value'));
        self::assertSame('true', $root->getAttribute('data-2lenet--crudit-ui-bundle--combobox-multiple-value'));
        self::assertStringContainsString('crudit-ui-combobox--invalid', (string) $root->getAttribute('class'));
        self::assertNull($root->querySelector('select'));
        $hidden = $root->querySelector('input[type="hidden"]');
        self::assertSame('form[technicians]', $hidden?->getAttribute('name'));
        self::assertSame('3,7', $hidden->getAttribute('value'));
        self::assertSame('Ana <Diaz>', $root->querySelector('.crudit-ui-combobox__token-label')?->textContent, 'the label is text, escaped');

        $input = $html->getElementById('form_technicians');
        self::assertSame('true', $input?->getAttribute('aria-invalid'));
        self::assertSame('form_technicians_error', $input->getAttribute('aria-describedby'));
    }

    public function testAChoiceWithAnAddressOfTheServerKeepsItsChoicesInThePage(): void
    {
        // A ChoiceType reads a list (form[zones][]): the values joined of the server mode would be refused
        $form = $this->form()
            ->add('zones', ChoiceType::class, ['choices' => ['North' => 'n', 'South' => 's'], 'multiple' => true, 'data' => ['s']])
            ->getForm();
        $html = $this->renderTemplate($form, "{{ form_widget(form.zones, {combobox: true, combobox_url: '/zone/autocomplete'}) }}");

        $root = $html->querySelector('.crudit-ui-combobox');
        self::assertNotNull($root);
        self::assertFalse($root->hasAttribute('data-2lenet--crudit-ui-bundle--combobox-url-value'));
        self::assertNull($root->querySelector('input[type="hidden"]'));
        $select = $root->querySelector('select[name="form[zones][]"]');
        self::assertSame(['n', 's'], array_map(fn ($option) => $option->getAttribute('value'), iterator_to_array($select?->querySelectorAll('option') ?? [])));
    }

    public function testADisabledChoiceOfAListWithSearchIsShownButCannotBeChosen(): void
    {
        $form = $this->form()
            ->add('zones', ChoiceType::class, [
                'choices' => ['North' => 'n', 'South' => 's', 'East' => 'e'],
                'choice_attr' => fn (string $value) => in_array($value, ['s', 'e'], true) ? ['disabled' => 'disabled'] : [],
                'multiple' => true,
                'data' => ['s'],
            ])
            ->getForm();
        $html = $this->renderTemplate($form, '{{ form_row(form.zones, {combobox: true}) }}');

        $select = $html->querySelector('.crudit-ui-combobox select');
        self::assertNotNull($select);
        self::assertSame(['n', 's', 'e'], array_map(fn ($option) => $option->getAttribute('value'), iterator_to_array($select->querySelectorAll('option'))));
        self::assertFalse($select->querySelector('option[value="n"]')?->hasAttribute('data-disabled'));
        self::assertTrue($select->querySelector('option[value="e"]')?->hasAttribute('data-disabled'), 'the script reads it and does not choose it');
        // Never the attribute disabled: a browser does not send a disabled option, the choice already made would be lost
        self::assertNull($select->querySelector('option[disabled]'));
        self::assertTrue($select->querySelector('option[value="s"]')?->hasAttribute('selected'));
        // The disabled choice already made keeps its token, without a button
        $token = $html->querySelector('.crudit-ui-combobox__token[data-value="s"]');
        self::assertTrue($token?->hasAttribute('data-disabled'));
        self::assertNull($token->querySelector('button'));
        self::assertSame(
            [['value' => 's', 'label' => 'South', 'disabled' => true]],
            json_decode((string) $html->querySelector('.crudit-ui-combobox')?->getAttribute('data-2lenet--crudit-ui-bundle--combobox-initial-value'), true),
            'the choices of the server, for the reset of the form',
        );
    }

    public function testThePreferredChoicesNotDuplicatedAreInTheListWithSearch(): void
    {
        $form = $this->form()
            ->add('zones', ChoiceType::class, [
                'choices' => ['North' => 'n', 'South' => 's', 'Côte d’Azur' => 'cst'],
                'preferred_choices' => ['cst'],
                'duplicate_preferred_choices' => false,
                'group_by' => fn (string $value) => 'cst' === $value ? 'Coast' : 'Mainland',
                'multiple' => true,
                'data' => ['cst'],
            ])
            ->getForm();
        $html = $this->renderTemplate($form, '{{ form_row(form.zones, {combobox: true}) }}');

        // The preferred choice first (as in a native list), once; its token shown
        $select = $html->querySelector('.crudit-ui-combobox select');
        self::assertNotNull($select);
        self::assertSame(['cst', 'n', 's'], array_map(fn ($option) => $option->getAttribute('value'), iterator_to_array($select->querySelectorAll('option'))));
        self::assertSame(['cst'], array_map(fn ($option) => $option->getAttribute('value'), iterator_to_array($select->querySelectorAll('option[selected]'))));
        self::assertSame('Côte d’Azur', $html->querySelector('.crudit-ui-combobox__token[data-value="cst"] .crudit-ui-combobox__token-label')?->textContent);
    }

    public function testAClickInTheListOfAListWithSearchLeavesTheFocusInTheInput(): void
    {
        $form = $this->form()->add('zones', ChoiceType::class, ['choices' => ['North' => 'n'], 'multiple' => true])->getForm();
        $html = $this->renderTemplate($form, '{{ form_row(form.zones, {combobox: true}) }}');

        self::assertStringContainsString(
            'mousedown->2lenet--crudit-ui-bundle--combobox#keepFocus',
            (string) $html->querySelector('[role="listbox"]')?->getAttribute('data-action'),
            'a click on its message or its scrollbar does not close it',
        );
    }

    public function testADisabledListWithSearchHasNoButtonToRemoveAChoice(): void
    {
        $form = $this->form()
            ->add('zones', ChoiceType::class, ['choices' => ['North' => 'n'], 'multiple' => true, 'disabled' => true, 'data' => ['n']])
            ->getForm();
        $html = $this->renderTemplate($form, '{{ form_widget(form.zones, {combobox: true}) }}');

        self::assertTrue((bool) $html->getElementById('form_zones')?->hasAttribute('disabled'));
        self::assertNotNull($html->querySelector('.crudit-ui-combobox--disabled'));
        self::assertNull($html->querySelector('.crudit-ui-combobox__remove'));
        self::assertTrue((bool) $html->querySelector('select[name="form[zones][]"]')?->hasAttribute('disabled'));
    }

    public function testAListWithoutTheVariableComboboxStaysNative(): void
    {
        $form = $this->form()->add('zones', ChoiceType::class, ['choices' => ['North' => 'n'], 'multiple' => true])->getForm();
        $html = $this->renderForm($form);

        self::assertNull($html->querySelector('.crudit-ui-combobox'));
        self::assertSame('select', strtolower((string) $html->getElementById('form_zones')?->tagName));
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

    public function testACollectionIsAListOfNamedLinesThatCanBeAddedRemovedAndMoved(): void
    {
        $form = $this->form()
            ->add('lines', CollectionType::class, [
                'label' => 'Lines',
                'entry_type' => LineType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'data' => [3 => ['part' => 'Pump', 'quantity' => 2, 'position' => 1], 5 => ['part' => 'Valve', 'quantity' => 1, 'position' => 2]],
            ])
            ->getForm();
        $html = $this->renderTemplate($form, "{{ form_row(form.lines, {collection_sortable: 'position', collection_entry_label: 'Piece %index%'}) }}");

        self::assertSame('Lines', trim((string) $html->querySelector('fieldset > legend')?->textContent), 'a collection is a fieldset named by its legend');
        $root = $html->getElementById('form_lines');
        self::assertNotNull($root);
        $controller = '2lenet--crudit-ui-bundle--collection';
        self::assertStringContainsString('crudit-ui-collection', (string) $root->getAttribute('class'));
        self::assertSame($controller, $root->getAttribute('data-controller'));
        self::assertSame('Piece %index%', $root->getAttribute('data-' . $controller . '-entry-value'));
        self::assertSame('6', $root->getAttribute('data-' . $controller . '-index-value'), 'a line added takes the index after the greatest one');
        self::assertSame('__name__', $root->getAttribute('data-' . $controller . '-prototype-name-value'));
        self::assertSame('true', $root->getAttribute('data-' . $controller . '-sortable-value'));
        self::assertSame('Moved to position %position% of %total%.', $root->getAttribute('data-' . $controller . '-moved-text-value'));
        self::assertTrue((bool) $root->querySelector('.crudit-ui-collection__empty')?->hasAttribute('hidden'));

        $rows = $root->querySelectorAll('ol.crudit-ui-collection__rows > li.crudit-ui-collection__row');
        self::assertCount(2, $rows);
        $first = $rows->item(0);
        $last = $rows->item(1);
        self::assertSame('Piece 1', $first?->getAttribute('aria-label'), 'a line is named in the order of the page, not by its key');
        self::assertSame('Piece 2', $last?->getAttribute('aria-label'));
        self::assertSame('true', $first->querySelector('.crudit-ui-collection__index')?->getAttribute('aria-hidden'));
        self::assertSame('Part, Piece 1', trim((string) $html->querySelector('label[for="form_lines_3_part"]')?->textContent), 'each field says its line');
        self::assertSame(', Piece 2', $html->querySelector('label[for="form_lines_5_quantity"] [data-crudit-ui-collection-entry-name]')?->textContent);

        $position = $html->getElementById('form_lines_5_position');
        self::assertSame('hidden', $position?->getAttribute('type'));
        self::assertTrue($position->hasAttribute('data-crudit-ui-collection-position'));
        self::assertSame('2', $position->getAttribute('value'), 'the positions of the server are kept until the order changes');

        $up = $first->querySelector('[data-crudit-ui-collection-up]');
        $down = $first->querySelector('[data-crudit-ui-collection-down]');
        self::assertSame('button', $up?->getAttribute('type'));
        self::assertTrue($up->hasAttribute('disabled'), 'the first line cannot go up');
        self::assertFalse((bool) $down?->hasAttribute('disabled'));
        self::assertSame('Move up: Piece 1', $up->querySelector('.crudit-ui-visually-hidden')?->textContent);
        self::assertTrue((bool) $last->querySelector('[data-crudit-ui-collection-down]')?->hasAttribute('disabled'), 'the last line cannot go down');
        self::assertSame('Remove: Piece 2', $last->querySelector('[data-crudit-ui-collection-remove] .crudit-ui-visually-hidden')?->textContent);
        self::assertSame($controller . '#remove', $last->querySelector('[data-crudit-ui-collection-remove]')?->getAttribute('data-action'));

        $template = $root->querySelector('template[data-' . $controller . '-target="prototype"]');
        self::assertNotNull($template);
        $prototype = $this->html($template->innerHTML);
        self::assertSame('Piece __name__', $prototype->querySelector('li')?->getAttribute('aria-label'));
        self::assertNotNull($prototype->querySelector('input[name="form[lines][__name__][part]"]'));
        self::assertNotNull($prototype->querySelector('input[name="form[lines][__name__][position]"][data-crudit-ui-collection-position]'));

        $add = $root->querySelector('button.crudit-ui-collection__add');
        self::assertSame('Add: Lines', $add?->getAttribute('aria-label'), 'the button names its collection');
        self::assertSame('Add', trim((string) $add->textContent));
        self::assertNotNull($root->querySelector('[role="status"][aria-live="polite"]'));
    }

    public function testALineRenderedAloneReadsItsPlaceInItsCollection(): void
    {
        $form = $this->form()
            ->add('lines', CollectionType::class, [
                'label' => 'Lines',
                'entry_type' => LineType::class,
                'allow_delete' => true,
                'data' => [
                    3 => ['part' => 'Pump', 'quantity' => 2, 'position' => 1],
                    5 => ['part' => 'Valve', 'quantity' => 1, 'position' => 2],
                    8 => ['part' => 'Seal', 'quantity' => 4, 'position' => 3],
                ],
            ])
            ->getForm();
        $view = $form->createView();
        // As CruditBundle does with the option sortable
        $view['lines']->vars['collection_sortable'] = 'position';
        /** @var Environment $twig */
        $twig = $this->container()->get('twig');
        $html = $this->html($twig->createTemplate("{% form_theme form with ['@LleCruditUi/form/theme.html.twig'] only %}<ol>{% for key in [3, 5, 8] %}{{ form_row(form.lines[key]) }}{% endfor %}</ol>")->render(['form' => $view]));

        $rows = $html->querySelectorAll('li.crudit-ui-collection__row');
        self::assertCount(3, $rows);
        $buttons = [];
        foreach ($rows as $row) {
            $buttons[] = [
                $row->getAttribute('aria-label'),
                trim((string) $row->querySelector('.crudit-ui-collection__index')?->textContent),
                (bool) $row->querySelector('[data-crudit-ui-collection-up]')?->hasAttribute('disabled'),
                (bool) $row->querySelector('[data-crudit-ui-collection-down]')?->hasAttribute('disabled'),
            ];
        }
        self::assertSame([
            ['Line 1', '1', true, false],
            ['Line 2', '2', false, false],
            ['Line 3', '3', false, true],
        ], $buttons, 'a line rendered alone is named and numbered from 1 by its place, and can be moved');
        self::assertSame('Part, Line 2', trim((string) $html->querySelector('label[for="form_lines_5_part"]')?->textContent));
    }

    public function testTheErrorsKeptByTheFieldOfThePositionAreShown(): void
    {
        $form = $this->form()
            ->add('lines', CollectionType::class, [
                'label' => 'Lines',
                'entry_type' => LineType::class,
                'data' => [['part' => 'Pump', 'quantity' => 2, 'position' => 1]],
            ])
            ->getForm();
        // Kept by the field (another type than HiddenType, or error_bubbling: false): addError() would make it go up
        $view = $form->createView();
        $position = $form->get('lines')->get('0')->get('position');
        $view['lines']['0']['position']->vars['errors'] = new \Symfony\Component\Form\FormErrorIterator($position, [new \Symfony\Component\Form\FormError('Not a position.')]);
        /** @var Environment $twig */
        $twig = $this->container()->get('twig');
        $html = $this->html($twig->createTemplate("{% form_theme form with ['@LleCruditUi/form/theme.html.twig'] only %}{{ form_row(form.lines, {collection_sortable: 'position'}) }}")->render(['form' => $view]));

        self::assertStringContainsString('Not a position.', (string) $html->querySelector('li.crudit-ui-collection__row')?->textContent, 'an error of the field of the position is shown with its line');
    }

    public function testADisabledCollectionTellsItsController(): void
    {
        $form = $this->form()
            ->add('lines', CollectionType::class, [
                'label' => 'Lines',
                'entry_type' => LineType::class,
                'disabled' => true,
                'data' => [['part' => 'Pump', 'quantity' => 2, 'position' => 1], ['part' => 'Valve', 'quantity' => 1, 'position' => 2]],
            ])
            ->getForm();
        $html = $this->renderTemplate($form, "{{ form_row(form.lines, {collection_sortable: 'position'}) }}");

        self::assertSame('true', $html->getElementById('form_lines')?->getAttribute('data-2lenet--crudit-ui-bundle--collection-disabled-value'), 'the controller would enable the buttons again');
        foreach ($html->querySelectorAll('[data-crudit-ui-collection-up], [data-crudit-ui-collection-down]') as $button) {
            self::assertTrue($button->hasAttribute('disabled'));
        }
    }

    public function testACollectionWithoutAddRemoveNorOrderHasNoButtonAndALineOfOneFieldIsNamed(): void
    {
        $form = $this->form()
            ->add('phones', CollectionType::class, ['label' => 'Phones', 'entry_type' => TextType::class, 'data' => ['0601020304', '0605060708']])
            ->getForm();
        $html = $this->renderForm($form);

        $root = $html->getElementById('form_phones');
        self::assertNotNull($root);
        self::assertFalse($root->hasAttribute('data-2lenet--crudit-ui-bundle--collection-sortable-value'));
        self::assertNull($root->querySelector('template'), 'no prototype without allow_add');
        self::assertNull($root->querySelector('button'));
        self::assertNull($root->querySelector('.crudit-ui-collection__actions'));
        $label = $html->querySelector('label[for="form_phones_1"]');
        self::assertSame('Line 2', $label?->textContent, 'a line of one field: its input is named by the line');
        self::assertStringContainsString('crudit-ui-visually-hidden', (string) $label->getAttribute('class'));
    }

    public function testAnEmptyCollectionSaysIt(): void
    {
        $form = $this->form()
            ->add('phones', CollectionType::class, ['label' => 'Phones', 'entry_type' => TextType::class, 'allow_add' => true, 'data' => []])
            ->getForm();
        $html = $this->renderForm($form);

        $empty = $html->querySelector('#form_phones .crudit-ui-collection__empty');
        self::assertFalse((bool) $empty?->hasAttribute('hidden'));
        self::assertSame('No lines yet.', trim((string) $empty?->textContent));
        self::assertCount(0, $html->querySelectorAll('#form_phones li'));
        self::assertSame('0', $html->getElementById('form_phones')?->getAttribute('data-2lenet--crudit-ui-bundle--collection-index-value'));
    }

    public function testAFileIsADropZoneCoveredByItsInput(): void
    {
        $form = $this->form()->add('attachment', FileType::class, ['label' => 'Attachment', 'help' => 'A PDF', 'required' => false, 'attr' => ['accept' => '.pdf']])->getForm();
        $html = $this->renderForm($form);

        self::assertSame('form_attachment', $html->querySelector('label.crudit-ui-label')?->getAttribute('for'));
        $root = $html->querySelector('.crudit-ui-file');
        self::assertSame('2lenet--crudit-ui-bundle--file-drop', $root?->getAttribute('data-controller'));
        $input = $root->querySelector('.crudit-ui-file__zone > input[type="file"]');
        self::assertSame('form_attachment', $input?->getAttribute('id'));
        self::assertSame('form[attachment]', $input->getAttribute('name'));
        self::assertSame('.pdf', $input->getAttribute('accept'));
        self::assertSame('form_attachment_help', $input->getAttribute('aria-describedby'));
        self::assertSame('crudit-ui-file__input', $input->getAttribute('class'), 'not the frame of a text input');
        $text = $root->querySelector('.crudit-ui-file__text');
        self::assertSame('true', $text?->getAttribute('aria-hidden'), 'the input is named by its label only');
        self::assertSame('Drop a file here or browse', preg_replace('/\s+/', ' ', trim((string) $text->textContent)));
        self::assertTrue((bool) $root->querySelector('.crudit-ui-file__list')?->hasAttribute('hidden'));
        self::assertNotNull($root->querySelector('template[data-2lenet--crudit-ui-bundle--file-drop-target="chosen"]'));
        self::assertSame('Remove %name%', $root->getAttribute('data-2lenet--crudit-ui-bundle--file-drop-remove-text-value'));
    }

    public function testTheFileAlreadySentCanBeDownloadedAndDeleted(): void
    {
        $form = $this->form()->add('attachment', FileType::class, ['label' => 'Attachment', 'required' => false])->getForm();
        $html = $this->renderTemplate($form, "{{ form_row(form.attachment, {crudit_ui_file_current: {name: 'plan.pdf', size: 1572864, download: '/files/plan.pdf', preview: null}, crudit_ui_file_delete: 'form[attachment_delete]'}) }}");

        $current = $html->querySelector('.crudit-ui-file__list:not([hidden]) > li');
        self::assertSame('plan.pdf', $current?->querySelector('.crudit-ui-file__label')?->textContent);
        self::assertSame('1.5 MB · current file', $current->querySelector('.crudit-ui-file__size')?->textContent);
        self::assertNull($current->querySelector('img'), 'no preview: an icon');
        $link = $current->querySelector('a[download]');
        self::assertSame('/files/plan.pdf', $link?->getAttribute('href'));
        self::assertSame('Download plan.pdf', preg_replace('/\s+/', ' ', trim((string) $link->textContent)));
        $delete = $current->querySelector('button[data-2lenet--crudit-ui-bundle--file-drop-target="delete"]');
        self::assertSame('Delete plan.pdf', $delete?->querySelector('.crudit-ui-visually-hidden')?->textContent);
        self::assertTrue((bool) $current->querySelector('[data-2lenet--crudit-ui-bundle--file-drop-target="keep"]')?->hasAttribute('hidden'));
        self::assertTrue((bool) $current->querySelector('[data-2lenet--crudit-ui-bundle--file-drop-target="note"]')?->hasAttribute('hidden'));
        $replace = $current->querySelector('[data-2lenet--crudit-ui-bundle--file-drop-target="replaceNote"]');
        self::assertSame('Will be replaced when saved', $replace?->textContent, 'one file per field: a file chosen replaces it');
        self::assertTrue($replace->hasAttribute('hidden'));
        self::assertSame('%name% will be replaced when saved.', $html->querySelector('.crudit-ui-file')?->getAttribute('data-2lenet--crudit-ui-bundle--file-drop-replaced-text-value'));
        $checkbox = $current->querySelector('input[type="checkbox"][name="form[attachment_delete]"]');
        self::assertTrue((bool) $checkbox?->hasAttribute('hidden'));
        self::assertFalse($checkbox->hasAttribute('checked'));
    }

    public function testTheImageAlreadySentHasAPreviewAndASizeInKilobytes(): void
    {
        $form = $this->form()->add('photo', FileType::class, ['label' => 'Photo', 'required' => false])->getForm();
        $html = $this->renderTemplate($form, "{{ form_row(form.photo, {crudit_ui_file_current: {name: 'front.jpg', size: 300, download: null, preview: '/files/front.jpg'}}) }}");

        $current = $html->querySelector('.crudit-ui-file__list > li');
        self::assertSame('Preview of front.jpg', $current?->querySelector('img.crudit-ui-file__preview')?->getAttribute('alt'));
        self::assertSame('1 KB · current file', $current->querySelector('.crudit-ui-file__size')?->textContent, 'never 0 KB');
        self::assertNull($current->querySelector('a'), 'no address: no link');
        self::assertNull($current->querySelector('button'), 'no checkbox to ask the deletion: no button');
    }

    public function testSeveralFilesDoNotReplaceTheFileAlreadySent(): void
    {
        $form = $this->form()->add('photos', FileType::class, ['label' => 'Photos', 'required' => false, 'multiple' => true])->getForm();
        $html = $this->renderTemplate($form, "{{ form_row(form.photos, {crudit_ui_file_current: {name: 'front.jpg', size: 300, download: null, preview: null}}) }}");

        self::assertNull($html->querySelector('[data-2lenet--crudit-ui-bundle--file-drop-target="replaceNote"]'));
    }

    public function testTheInputOfAFileAloneForTheMarkupOfAnotherType(): void
    {
        $form = $this->form()->add('attachment', FileType::class, ['label' => 'Attachment'])->getForm();
        $html = $this->renderTemplate($form, '{{ form_widget(form.attachment, {crudit_ui_file_input_only: true}) }}');

        self::assertNull($html->querySelector('.crudit-ui-file'));
        self::assertSame('crudit-ui-file__input', $html->querySelector('input[type="file"]')?->getAttribute('class'));
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
