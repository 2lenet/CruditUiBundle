<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Tests\Functional;

use PHPUnit\Framework\TestCase;

/**
 * LleCruditUi:ExportDialog (docs/components.md).
 */
class ExportDialogTest extends TestCase
{
    use RendersTwig;

    private const string FORMATS = "[{value: 'xls', label: 'Excel', description: 'A spreadsheet', icon: 'file-excel'}, {value: 'csv', label: 'CSV'}]";

    private function dialog(string $props = ''): \Dom\Element
    {
        return $this->element(
            '<twig:LleCruditUi:ExportDialog id="export" title="Export the visits" description="The 137 visits." action="/visits/export" :formats="' . self::FORMATS . '" ' . $props . '/>',
            'dialog',
        );
    }

    public function testTheDialogIsNamedAndDescribed(): void
    {
        $dialog = $this->dialog();

        self::assertSame('export', $dialog->getAttribute('id'));
        self::assertSame('export-title', $dialog->getAttribute('aria-labelledby'));
        self::assertSame('export-description', $dialog->getAttribute('aria-describedby'));
        self::assertSame('Export the visits', trim((string) $dialog->querySelector('#export-title')?->textContent));
        self::assertSame('The 137 visits.', trim((string) $dialog->querySelector('#export-description')?->textContent));
    }

    public function testTheFormIsAGetThatTurboLeavesAlone(): void
    {
        $form = $this->dialog()->querySelector('form');

        self::assertSame('get', $form?->getAttribute('method'));
        self::assertSame('/visits/export', $form->getAttribute('action'));
        self::assertSame('false', $form->getAttribute('data-turbo'));
        self::assertSame('2lenet--crudit-ui-bundle--export', $form->getAttribute('data-controller'));
    }

    public function testTheFormatsAreRadioButtonsInAFieldsetAndTheFirstIsChecked(): void
    {
        $dialog = $this->dialog();

        self::assertSame('Format', trim((string) $dialog->querySelector('fieldset legend')?->textContent));
        $radios = $dialog->querySelectorAll('input[type=radio][name=format]');
        self::assertCount(2, $radios);
        self::assertTrue($radios[0]->hasAttribute('checked'));
        self::assertFalse($radios[1]->hasAttribute('checked'));
        self::assertStringContainsString('A spreadsheet', (string) $dialog->textContent);
    }

    public function testTheCheckedFormatCanBeGiven(): void
    {
        $radios = $this->dialog('format="csv"')->querySelectorAll('input[type=radio]');

        self::assertFalse($radios[0]->hasAttribute('checked'));
        self::assertSame('csv', $radios[1]->getAttribute('value'));
        self::assertTrue($radios[1]->hasAttribute('checked'));
    }

    public function testAFormatThatIsNotOfferedFallsBackOnTheFirst(): void
    {
        $radios = $this->dialog('format="doc"')->querySelectorAll('input[type=radio]');

        self::assertTrue($radios[0]->hasAttribute('checked'));
        self::assertFalse($radios[1]->hasAttribute('checked'));
    }

    public function testTheFieldsOfTheDialogCannotBeOverriddenByParams(): void
    {
        $dialog = $this->dialog(':params="{format: \'x\', download_token: \'y\', scope: \'z\'}"');

        self::assertCount(0, $dialog->querySelectorAll('input[name=format][type=hidden]'), 'no hidden format');
        self::assertSame('', (string) $dialog->querySelector('input[name=download_token]')?->getAttribute('value'));
        self::assertSame('z', $dialog->querySelector('input[type=hidden][name=scope]')?->getAttribute('value'));
    }

    public function testTheSubmitButtonBelongsToTheFormAndTheTokenIsDisabledUntilTheExport(): void
    {
        $dialog = $this->dialog();

        $submit = $dialog->querySelector('button[type=submit]');
        self::assertSame('export-form', $submit?->getAttribute('form'));
        $token = $dialog->querySelector('input[name=download_token]');
        self::assertTrue($token?->hasAttribute('disabled'));
    }

    public function testTheStatusIsPoliteAndTheErrorAnAlertHiddenUntilNeeded(): void
    {
        $dialog = $this->dialog();

        self::assertNotNull($dialog->querySelector('p[role=status]'));
        $error = $dialog->querySelector('p[role=alert]');
        self::assertTrue($error?->hasAttribute('hidden'));
    }

    public function testExtraParametersAreHiddenFieldsAndAreEscaped(): void
    {
        $dialog = $this->dialog(':params="{scope: \'a&b\'}"');

        $field = $dialog->querySelector('input[type=hidden][name=scope]');
        self::assertSame('a&b', $field?->getAttribute('value'));
    }
}
