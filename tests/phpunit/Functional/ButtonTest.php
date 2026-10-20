<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * LleCruditUi:Button, LleCruditUi:IconButton and LleCruditUi:ButtonGroup (docs/components.md).
 */
class ButtonTest extends TestCase
{
    use RendersTwig;

    protected function tearDown(): void
    {
        (new Filesystem())->remove(sys_get_temp_dir() . '/lle_crudit_ui_bundle');
    }

    public function testSecondaryButtonByDefault(): void
    {
        $button = $this->element('<twig:LleCruditUi:Button>Cancel</twig:LleCruditUi:Button>', 'button');

        self::assertSame('button', $button->getAttribute('type'));
        self::assertSame('crudit-ui-btn crudit-ui-btn--secondary', $button->getAttribute('class'));
        self::assertSame('Cancel', $button->textContent);
    }

    public function testVariantSizeIconAndCount(): void
    {
        $button = $this->element('<twig:LleCruditUi:Button variant="primary" size="small" icon="filter" count="3" type="submit">Filters</twig:LleCruditUi:Button>', 'button');

        self::assertSame('submit', $button->getAttribute('type'));
        self::assertSame('crudit-ui-btn crudit-ui-btn--primary crudit-ui-btn--small', $button->getAttribute('class'));
        // The icon first, hidden; the count after the label, read with it.
        // The spinner of busyOnSubmit (a submit button) waits in a template; then the icon, first, hidden.
        $icon = $button->firstElementChild?->nextElementSibling;
        self::assertSame(['template', 'svg'], [$button->firstElementChild?->localName, $icon?->localName]);
        self::assertSame('true', $icon?->getAttribute('aria-hidden'));
        self::assertSame('3', $button->querySelector('.crudit-ui-btn__count')?->textContent);
        self::assertSame('Filters3', $button->textContent);
    }

    public function testAttributesOfTheProjectAreKept(): void
    {
        $button = $this->element('<twig:LleCruditUi:Button variant="primary" class="crudit-eip-submit" form="edit" data-action="save" aria-keyshortcuts="Control+S">Save</twig:LleCruditUi:Button>', 'button');

        self::assertSame('crudit-ui-btn crudit-ui-btn--primary crudit-eip-submit', $button->getAttribute('class'));
        self::assertSame('2lenet--crudit-ui-bundle--button', $button->getAttribute('data-controller'));
        self::assertSame('edit', $button->getAttribute('form'));
        self::assertSame('save', $button->getAttribute('data-action'));
        self::assertSame('Control+S', $button->getAttribute('aria-keyshortcuts'));
    }

    public function testLinkThatLooksLikeAButton(): void
    {
        $link = $this->element('<twig:LleCruditUi:Button href="/list">Back</twig:LleCruditUi:Button>', 'a');

        self::assertSame('/list', $link->getAttribute('href'));
        self::assertFalse($link->hasAttribute('type'));
    }

    public function testDisabledButtonAndLink(): void
    {
        $button = $this->element('<twig:LleCruditUi:Button disabled>Save</twig:LleCruditUi:Button>', 'button');
        self::assertTrue($button->hasAttribute('disabled'));

        // A disabled link is neither followed nor focused.
        $link = $this->element('<twig:LleCruditUi:Button href="/list" disabled>Back</twig:LleCruditUi:Button>', 'a');
        self::assertFalse($link->hasAttribute('href'));
        self::assertSame('link', $link->getAttribute('role'));
        self::assertSame('true', $link->getAttribute('aria-disabled'));
    }

    public function testLoadingKeepsTheFocusAndTheLabel(): void
    {
        $button = $this->element('<twig:LleCruditUi:Button variant="primary" icon="save" loading>Save</twig:LleCruditUi:Button>', 'button');

        self::assertFalse($button->hasAttribute('disabled'));
        self::assertSame('true', $button->getAttribute('aria-disabled'));
        self::assertSame('true', $button->getAttribute('aria-busy'));
        self::assertCount(1, $button->querySelectorAll('svg'));
        self::assertStringContainsString('crudit-ui-icon--spin', (string) $button->querySelector('svg')?->getAttribute('class'));
        self::assertSame('Save', $button->textContent);
    }

    public function testSubmitButtonIsBusyWhenItSendsItsForm(): void
    {
        $value = 'data-2lenet--crudit-ui-bundle--button-busy-on-submit-value';

        $button = $this->element('<twig:LleCruditUi:Button type="submit" icon="save">Save</twig:LleCruditUi:Button>', 'button');
        self::assertSame('true', $button->getAttribute($value));
        self::assertSame('spinner', $button->querySelector('template')?->getAttribute('data-2lenet--crudit-ui-bundle--button-target'));

        $button = $this->element('<twig:LleCruditUi:IconButton type="submit" icon="check" label="Save"/>', 'button');
        self::assertSame('true', $button->getAttribute($value));

        // Not for a plain button, nor when the project sends the form itself (Ajax).
        self::assertFalse($this->element('<twig:LleCruditUi:Button>Cancel</twig:LleCruditUi:Button>', 'button')->hasAttribute($value));
        $button = $this->element('<twig:LleCruditUi:Button type="submit" :busyOnSubmit="false">Save</twig:LleCruditUi:Button>', 'button');
        self::assertFalse($button->hasAttribute($value));
        self::assertNull($button->querySelector('template'));
    }

    public function testLoadingLinkIsNotFollowed(): void
    {
        $link = $this->element('<twig:LleCruditUi:Button href="/export" loading>Export</twig:LleCruditUi:Button>', 'a');

        self::assertFalse($link->hasAttribute('href'));
        self::assertSame('true', $link->getAttribute('aria-busy'));
        // Without href, an <a> would lose its role.
        self::assertSame('link', $link->getAttribute('role'));
    }

    public function testVariablesOfTheCallerDoNotReachTheButton(): void
    {
        $template = "{% set extra = {'data-leak': 'yes'} %}{% set class = 'leak' %}{% set busyOnSubmit = true %}"
            . '<twig:LleCruditUi:Button>Save</twig:LleCruditUi:Button><twig:LleCruditUi:Tag>Lift</twig:LleCruditUi:Tag>';
        $html = $this->html($this->render(new TestKernel(), $template));

        foreach ($html->querySelectorAll('button') as $button) {
            self::assertFalse($button->hasAttribute('data-leak'));
            self::assertStringNotContainsString('leak', (string) $button->getAttribute('class'));
            self::assertFalse($button->hasAttribute('data-2lenet--crudit-ui-bundle--button-busy-on-submit-value'));
        }
    }

    public function testGuardOfTheClickIsMergedWithTheControllersOfTheProject(): void
    {
        $button = $this->element('<twig:LleCruditUi:Button>Save</twig:LleCruditUi:Button>', 'button');
        self::assertSame('2lenet--crudit-ui-bundle--button', $button->getAttribute('data-controller'));

        $button = $this->element('<twig:LleCruditUi:IconButton icon="edit" label="Edit" data-controller="tooltip"/>', 'button');
        self::assertSame('2lenet--crudit-ui-bundle--button tooltip', $button->getAttribute('data-controller'));
    }

    public function testEachAttributeOnce(): void
    {
        // A disabled link that is also loading, and an aria-disabled passed by the project with loading.
        $templates = [
            '<twig:LleCruditUi:Button href="/list" disabled loading>Back</twig:LleCruditUi:Button>',
            '<twig:LleCruditUi:Button aria-disabled="true" loading>Save</twig:LleCruditUi:Button>',
            '<twig:LleCruditUi:IconButton icon="edit" label="Edit" href="/edit" disabled loading/>',
        ];
        foreach ($templates as $template) {
            $html = $this->render(new TestKernel(), $template);
            self::assertSame(1, substr_count($html, 'aria-disabled='), $html);
            self::assertSame(1, substr_count($html, 'aria-busy='), $html);
        }

        // The value of the project wins.
        $button = $this->element('<twig:LleCruditUi:Button aria-busy="false" loading>Save</twig:LleCruditUi:Button>', 'button');
        self::assertSame('false', $button->getAttribute('aria-busy'));
    }

    public function testIconButtonIsNamedByItsHiddenLabel(): void
    {
        $button = $this->element('<twig:LleCruditUi:IconButton icon="notifications" label="Notifications (2 unread)" count="2"/>', 'button');

        self::assertSame('crudit-ui-icon-btn crudit-ui-icon-btn--with-count', $button->getAttribute('class'));
        self::assertSame('true', $button->querySelector('svg')?->getAttribute('aria-hidden'));
        self::assertSame('Notifications (2 unread)', $button->querySelector('.crudit-ui-visually-hidden')?->textContent);
        // The count is in the label: the badge is hidden, not read twice.
        self::assertSame('true', $button->querySelector('.crudit-ui-icon-btn__count')?->getAttribute('aria-hidden'));
    }

    public function testSmallIconButtonLoadingAndAsALink(): void
    {
        $button = $this->element('<twig:LleCruditUi:IconButton icon="check" label="Save" size="small" loading/>', 'button');
        self::assertSame('crudit-ui-icon-btn crudit-ui-icon-btn--small', $button->getAttribute('class'));
        self::assertSame('true', $button->getAttribute('aria-busy'));
        self::assertStringContainsString('crudit-ui-icon--spin', (string) $button->querySelector('svg')?->getAttribute('class'));

        $link = $this->element('<twig:LleCruditUi:IconButton icon="edit" label="Edit" href="/edit/1"/>', 'a');
        self::assertSame('/edit/1', $link->getAttribute('href'));
    }

    public function testButtonGroup(): void
    {
        $group = $this->element('<twig:LleCruditUi:ButtonGroup label="Edit in place"><twig:LleCruditUi:IconButton icon="check" label="Save"/><twig:LleCruditUi:IconButton icon="close" label="Cancel"/></twig:LleCruditUi:ButtonGroup>', 'div');

        self::assertSame('crudit-ui-btn-group', $group->getAttribute('class'));
        self::assertSame('group', $group->getAttribute('role'));
        self::assertSame('Edit in place', $group->getAttribute('aria-label'));
        self::assertCount(2, $group->querySelectorAll('button'));

        // Without a label, only a layout.
        self::assertFalse($this->element('<twig:LleCruditUi:ButtonGroup>…</twig:LleCruditUi:ButtonGroup>', 'div')->hasAttribute('role'));
    }
}
