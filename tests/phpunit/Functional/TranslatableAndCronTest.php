<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * The templates of a translated field (`form/_translatable.html.twig`: one tab per language, ARIA tabs pattern) and of a
 * cron expression (`form/_cron.html.twig`: the controls of the widget rendered in the state given, hidden without
 * JavaScript).
 */
class TranslatableAndCronTest extends TestCase
{
    use RendersTwig;

    protected function tearDown(): void
    {
        (new Filesystem())->remove(sys_get_temp_dir() . '/lle_crudit_ui_bundle');
    }

    public function testATranslatedFieldHasOneTabPerLanguageWithItsInputInItsPanel(): void
    {
        $html = $this->translatable([]);

        $root = $html->getElementById('title');
        self::assertSame('2lenet--crudit-ui-bundle--tabs 2lenet--crudit-ui-bundle--translatable', $root?->getAttribute('data-controller'));
        self::assertSame('input->2lenet--crudit-ui-bundle--translatable#update', $root->getAttribute('data-action'));

        $list = $root->querySelector('[role="tablist"]');
        self::assertSame('Languages: Title', $list?->getAttribute('aria-label'));
        $tabs = $list->querySelectorAll('[role="tab"]');
        self::assertCount(3, $tabs);

        // The first language: selected, required (a star hidden from assistive technologies), filled.
        $fr = $tabs->item(0);
        self::assertSame('true', $fr?->getAttribute('aria-selected'));
        self::assertSame('0', $fr->getAttribute('tabindex'));
        self::assertSame('title-panel-fr', $fr->getAttribute('aria-controls'));
        self::assertSame('true', $fr->querySelector('.crudit-ui-required')?->getAttribute('aria-hidden'));
        self::assertTrue((bool) $fr->querySelector('.crudit-ui-translatable__state')?->hasAttribute('hidden'));

        // An empty language: " · empty" in the name of its tab.
        $en = $tabs->item(1);
        self::assertSame('false', $en?->getAttribute('aria-selected'));
        self::assertSame('-1', $en->getAttribute('tabindex'));
        $empty = $en->querySelector('.crudit-ui-translatable__state');
        self::assertNotNull($empty);
        self::assertFalse($empty->hasAttribute('hidden'));
        self::assertSame('English · empty', $this->text($en));
        self::assertSame('en', $empty->getAttribute('data-language'));

        $panel = $html->getElementById('title-panel-fr');
        self::assertSame('tabpanel', $panel?->getAttribute('role'));
        self::assertSame('title-tab-fr', $panel->getAttribute('aria-labelledby'));
        self::assertFalse($panel->hasAttribute('hidden'));
        $label = $panel->querySelector('label.crudit-ui-visually-hidden');
        self::assertSame('title_fr', $label?->getAttribute('for'));
        self::assertSame('title_fr_label', $label->getAttribute('id'));
        self::assertSame('Title (Français)', $label->textContent);
        self::assertNotNull($panel->querySelector('input#title_fr'));
        self::assertTrue((bool) $html->getElementById('title-panel-en')?->hasAttribute('hidden'));
    }

    public function testTheTabOfALanguageInErrorSaysItAndIsShownFirst(): void
    {
        $html = $this->translatable(['de' => ['invalid' => true, 'errors' => '<p id="title_de_error" class="crudit-ui-error">Too long.</p>']]);

        $de = $html->getElementById('title-tab-de');
        self::assertSame('true', $de?->getAttribute('aria-selected'));
        self::assertSame('false', $html->getElementById('title-tab-fr')?->getAttribute('aria-selected'));
        self::assertSame('Deutsch · error', $this->text($de));
        self::assertNotNull($de->querySelector('.crudit-ui-translatable__state--invalid svg'));
        // No target: " · error" stays while typing.
        self::assertNull($de->querySelector('[data-2lenet--crudit-ui-bundle--translatable-target]'));

        $panel = $html->getElementById('title-panel-de');
        self::assertNotNull($panel);
        self::assertFalse($panel->hasAttribute('hidden'));
        self::assertSame('Too long.', $panel->querySelector('#title_de_error')?->textContent);
        self::assertTrue((bool) $html->getElementById('title-panel-fr')?->hasAttribute('hidden'));
    }

    public function testTheTabSelectedWinsOverTheFirstInError(): void
    {
        $html = $this->translatable(['de' => ['invalid' => true]], 'en');

        self::assertSame('true', $html->getElementById('title-tab-en')?->getAttribute('aria-selected'));
        self::assertSame('false', $html->getElementById('title-tab-de')?->getAttribute('aria-selected'));
    }

    public function testACronExpressionShowsTheControlsOfItsStateOnlyWithJavaScript(): void
    {
        $html = $this->cron('30 8 * * 1,4', ['frequency' => 'weekly', 'days' => ['1', '4'], 'time' => '08:30']);

        $root = $html->querySelector('.crudit-ui-cron');
        self::assertSame('2lenet--crudit-ui-bundle--cron', $root?->getAttribute('data-controller'));
        self::assertSame('weekly', $root->getAttribute('data-2lenet--crudit-ui-bundle--cron-frequency-value'));
        self::assertSame('Every %days% at %time%', $root->getAttribute('data-2lenet--crudit-ui-bundle--cron-weekly-text-value'));

        // Without JavaScript: the widget hidden, the text input shown, with its label.
        $widget = $root->querySelector('.crudit-ui-cron__frequency');
        self::assertNotNull($widget);
        self::assertTrue($widget->hasAttribute('hidden'));
        self::assertTrue((bool) $root->querySelector('.crudit-ui-cron__details')?->hasAttribute('hidden'));
        $custom = $root->querySelector('.crudit-ui-cron__custom');
        self::assertNotNull($custom);
        self::assertFalse($custom->hasAttribute('hidden'));
        self::assertSame('reminder', $custom->querySelector('label')?->getAttribute('for'));
        self::assertSame('Cron expression', $custom->querySelector('label')->textContent);
        self::assertSame('30 8 * * 1,4', $custom->querySelector('input#reminder')?->getAttribute('value'));

        // The frequencies: radios not sent with the form (a name outside the form), the one of the state checked.
        $radios = $widget->querySelectorAll('input[type="radio"]');
        self::assertCount(5, $radios);
        self::assertSame(['none', 'daily', 'weekly', 'monthly', 'custom'], array_map(fn (\Dom\Element $radio) => $radio->getAttribute('value'), iterator_to_array($radios)));
        self::assertSame('weekly', $widget->querySelector('input:checked')?->getAttribute('value'));
        self::assertSame('reminder-cron__frequency', $radios->item(0)?->getAttribute('name'));
        self::assertSame('reminder_help', $radios->item(0)->getAttribute('aria-describedby'));

        // The days, Monday first: those of the state checked, named in full, an initial shown.
        $days = $root->querySelector('fieldset.crudit-ui-cron__days');
        self::assertNotNull($days);
        self::assertFalse($days->hasAttribute('hidden'));
        self::assertSame('Days', $days->querySelector('legend')?->textContent);
        $boxes = $days->querySelectorAll('input[type="checkbox"]');
        self::assertSame(['1', '2', '3', '4', '5', '6', '0'], array_map(fn (\Dom\Element $box) => $box->getAttribute('value'), iterator_to_array($boxes)));
        self::assertSame(['1', '4'], array_map(fn (\Dom\Element $box) => $box->getAttribute('value'), iterator_to_array($days->querySelectorAll('input:checked'))));
        self::assertFalse($boxes->item(0)?->hasAttribute('name'));
        self::assertSame('Monday', $days->querySelector('.crudit-ui-visually-hidden')?->textContent);
        self::assertSame('M', $days->querySelector('[aria-hidden="true"]')?->textContent);

        self::assertTrue((bool) $root->querySelector('[data-2lenet--crudit-ui-bundle--cron-target="monthDay"]')?->hasAttribute('hidden'));
        $time = $html->getElementById('reminder-cron_time');
        self::assertSame('08:30', $time?->getAttribute('value'));
        self::assertFalse($time->hasAttribute('name'));
        self::assertSame('Time', $root->querySelector('label[for="reminder-cron_time"]')?->textContent);
        // aria-live set by the controller once the sentence is written: it is not read when the page loads.
        self::assertFalse((bool) $root->querySelector('.crudit-ui-cron__summary')?->hasAttribute('aria-live'));
    }

    public function testACronExpressionOnTheLastDayOfTheMonth(): void
    {
        $html = $this->cron('0 18 L * *', ['frequency' => 'monthly', 'month_day' => 'L', 'time' => '18:00']);

        self::assertFalse((bool) $html->querySelector('[data-2lenet--crudit-ui-bundle--cron-target="monthDay"]')?->hasAttribute('hidden'));
        self::assertTrue((bool) $html->querySelector('fieldset.crudit-ui-cron__days')?->hasAttribute('hidden'));
        $select = $html->getElementById('reminder-cron_month_day');
        self::assertSame('L', $select?->querySelector('option[selected]')?->getAttribute('value'));
        self::assertSame('1st', $select->querySelector('option')?->textContent);
        self::assertCount(29, $select->querySelectorAll('option'));
    }

    public function testADisabledCronExpressionHasItsControlsDisabled(): void
    {
        $html = $this->cron('', ['frequency' => 'none'], ['disabled' => true, 'invalid' => true]);

        $root = $html->querySelector('.crudit-ui-cron');
        self::assertNotNull($root);
        self::assertStringContainsString('crudit-ui-cron--invalid', (string) $root->getAttribute('class'));
        foreach ($root->querySelectorAll('.crudit-ui-cron__frequency input, .crudit-ui-cron__details input, .crudit-ui-cron__details select') as $control) {
            self::assertTrue($control->hasAttribute('disabled'));
        }
        self::assertSame('true', $root->querySelector('.crudit-ui-cron__frequency input')?->getAttribute('aria-invalid'));
    }

    /**
     * @param array<string, array<string, mixed>> $changes by code of language
     */
    private function translatable(array $changes, ?string $selected = null): \Dom\HTMLDocument
    {
        $languages = [];
        foreach (['fr' => 'Français', 'en' => 'English', 'de' => 'Deutsch'] as $code => $label) {
            $languages[] = array_merge([
                'code' => $code,
                'label' => $label,
                'input_id' => 'title_' . $code,
                'input' => \sprintf('<input type="text" id="title_%s" lang="%s">', $code, $code),
                'required' => 'fr' === $code,
                'empty' => 'en' === $code,
                'invalid' => false,
            ], $changes[$code] ?? []);
        }

        return $this->html($this->render(new TestKernel(), "{{ include('@LleCruditUi/form/_translatable.html.twig', {id: 'title', label: 'Title', languages: languages, selected: selected}, with_context: false) }}", [
            'languages' => $languages,
            'selected' => $selected,
        ]));
    }

    /**
     * @param array<string, mixed> $state
     * @param array<string, mixed> $options
     */
    private function cron(string $value, array $state, array $options = []): \Dom\HTMLDocument
    {
        return $this->html($this->render(new TestKernel(), "{{ include('@LleCruditUi/form/_cron.html.twig', {id: 'reminder-cron', input_id: 'reminder', input: input, state: state, describedby: 'reminder_help'}|merge(options), with_context: false) }}", [
            'input' => \sprintf('<input type="text" id="reminder" name="reminder" value="%s">', $value),
            'state' => $state,
            'options' => $options,
        ]));
    }

    private function text(\Dom\Element $element): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', str_replace("\u{a0}", ' ', (string) $element->textContent)));
    }
}
