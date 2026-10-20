<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * LleCruditUi:Dialog, and its drawer variant (docs/components.md).
 */
class DialogTest extends TestCase
{
    use RendersTwig;

    private const string DIALOG = '2lenet--crudit-ui-bundle--dialog';

    protected function tearDown(): void
    {
        (new Filesystem())->remove(sys_get_temp_dir() . '/lle_crudit_ui_bundle');
    }

    public function testDialogIsNamedByItsTitle(): void
    {
        $dialog = $this->element('<twig:LleCruditUi:Dialog id="delete" title="Delete the visit V-0412?" class="project" data-test="1"><p>Body</p></twig:LleCruditUi:Dialog>', 'dialog');

        self::assertSame('delete', $dialog->getAttribute('id'));
        self::assertSame('crudit-ui-dialog crudit-ui-dialog--dialog project', $dialog->getAttribute('class'));
        self::assertSame(self::DIALOG, $dialog->getAttribute('data-controller'));
        self::assertSame('1', $dialog->getAttribute('data-test'));
        // Closed: opened modal by the controller, never by the attribute (a dialog that is not modal).
        self::assertFalse($dialog->hasAttribute('open'));
        self::assertFalse($dialog->hasAttribute('data-' . self::DIALOG . '-open-value'));
        self::assertFalse($dialog->hasAttribute('aria-describedby'));

        self::assertSame('delete-title', $dialog->getAttribute('aria-labelledby'));
        self::assertSame('Delete the visit V-0412?', $dialog->querySelector('h2#delete-title')?->textContent);
        self::assertSame('Body', $dialog->querySelector('.crudit-ui-dialog__body > p')?->textContent);
        // Footer without a block: empty, hidden by the CSS.
        self::assertSame('', $dialog->querySelector('.crudit-ui-dialog__footer')?->innerHTML);
    }

    public function testCloseButton(): void
    {
        $dialog = $this->element('<twig:LleCruditUi:Dialog title="Filters"/>', 'dialog');

        $close = $dialog->querySelector('.crudit-ui-dialog__header button');
        self::assertSame('button', $close?->getAttribute('type'));
        self::assertSame('crudit-ui-icon-btn crudit-ui-dialog__close', $close->getAttribute('class'));
        self::assertSame('Close', $close->querySelector('.crudit-ui-visually-hidden')?->textContent);
        // It fires "cancel" first, as Escape does.
        self::assertSame(self::DIALOG . '#dismiss', $close->getAttribute('data-action'));
    }

    public function testGeneratedIdNamesTheTitleAndTheDescription(): void
    {
        $dialog = $this->element('<twig:LleCruditUi:Dialog title="Delete?" description="This action cannot be undone." role="alertdialog"/>', 'dialog');

        $id = (string) $dialog->getAttribute('id');
        self::assertStringStartsWith('crudit-ui-dialog-', $id);
        self::assertSame('alertdialog', $dialog->getAttribute('role'));
        self::assertSame($id . '-title', $dialog->getAttribute('aria-labelledby'));
        self::assertSame($id . '-description', $dialog->getAttribute('aria-describedby'));
        self::assertSame('This action cannot be undone.', $dialog->querySelector('#' . $id . '-description')?->textContent);
        // The description opens the body, before the content.
        self::assertSame($id . '-description', $dialog->querySelector('.crudit-ui-dialog__body')?->firstElementChild?->getAttribute('id'));
    }

    public function testFooterAndOpenAndDrawer(): void
    {
        $dialog = $this->element(<<<'TWIG'
            <twig:LleCruditUi:Dialog id="filters" title="Filters" variant="drawer" open>
                <form id="filters-form" method="dialog"></form>
                <twig:block name="footer">
                    <twig:LleCruditUi:Button commandfor="filters" command="close">Cancel</twig:LleCruditUi:Button>
                    <twig:LleCruditUi:Button variant="primary" type="submit" form="filters-form">Apply</twig:LleCruditUi:Button>
                </twig:block>
            </twig:LleCruditUi:Dialog>
            TWIG, 'dialog');

        self::assertSame('crudit-ui-dialog crudit-ui-dialog--drawer', $dialog->getAttribute('class'));
        self::assertSame('true', $dialog->getAttribute('data-' . self::DIALOG . '-open-value'));
        self::assertFalse($dialog->hasAttribute('open'));

        $buttons = $dialog->querySelectorAll('.crudit-ui-dialog__footer > button');
        self::assertCount(2, $buttons);
        self::assertSame('filters', $buttons[0]?->getAttribute('commandfor'));
        self::assertSame('close', $buttons[0]->getAttribute('command'));
        self::assertNotNull($dialog->querySelector('.crudit-ui-dialog__body > form#filters-form'));
    }
}
