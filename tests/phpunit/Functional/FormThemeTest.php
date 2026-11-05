<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Twig\Environment;

/**
 * The form theme `@LleCruditUi/form/theme.html.twig`: labels above and attached, help and errors attached to the
 * input, required fields, boxes, switch and units.
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

    private function container(): ContainerInterface
    {
        /** @var ContainerInterface $container */
        $container = $this->kernel->getContainer()->get('test.service_container');

        return $container;
    }
}
