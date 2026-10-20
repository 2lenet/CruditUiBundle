<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * LleCruditUi:Dropdown, LleCruditUi:Menu, LleCruditUi:MenuItem, LleCruditUi:MenuGroup and LleCruditUi:Tooltip
 * (docs/components.md).
 */
class MenusTest extends TestCase
{
    use RendersTwig;

    private const string DROPDOWN = '2lenet--crudit-ui-bundle--dropdown';
    private const string TOOLTIP = '2lenet--crudit-ui-bundle--tooltip';

    protected function tearDown(): void
    {
        (new Filesystem())->remove(sys_get_temp_dir() . '/lle_crudit_ui_bundle');
    }

    public function testDropdownIsADisclosure(): void
    {
        $dropdown = $this->element('<twig:LleCruditUi:Dropdown id="actions" label="Actions" class="project" data-test="1"><p>Panel</p></twig:LleCruditUi:Dropdown>', 'div');

        self::assertSame('actions', $dropdown->getAttribute('id'));
        self::assertSame('crudit-ui-dropdown project', $dropdown->getAttribute('class'));
        self::assertSame(self::DROPDOWN, $dropdown->getAttribute('data-controller'));
        self::assertSame('1', $dropdown->getAttribute('data-test'));
        self::assertFalse($dropdown->hasAttribute('data-' . self::DROPDOWN . '-floating-value'));

        $button = $dropdown->firstElementChild;
        self::assertSame('button', $button?->getAttribute('type'));
        self::assertSame('crudit-ui-btn crudit-ui-btn--secondary', $button->getAttribute('class'));
        self::assertSame('false', $button->getAttribute('aria-expanded'));
        self::assertSame('actions-panel', $button->getAttribute('aria-controls'));
        self::assertSame('button', $button->getAttribute('data-' . self::DROPDOWN . '-target'));
        self::assertSame(self::DROPDOWN . '#toggle', $button->getAttribute('data-action'));
        self::assertSame('Actions', trim((string) $button->textContent));
        // The chevron, hidden, after the label.
        self::assertSame('true', $button->querySelector('svg.crudit-ui-dropdown__chevron')?->getAttribute('aria-hidden'));
        // Not an ARIA menu.
        self::assertNull($dropdown->querySelector('[role="menu"], [aria-haspopup]'));

        $panel = $dropdown->querySelector('#actions-panel');
        self::assertTrue($panel?->hasAttribute('hidden'));
        self::assertSame('crudit-ui-dropdown__panel', $panel->getAttribute('class'));
        self::assertSame('panel', $panel->getAttribute('data-' . self::DROPDOWN . '-target'));
        self::assertSame('Panel', $panel->querySelector('p')?->textContent);
    }

    public function testIconOnlyFloatingDropdownAlignedOnTheStart(): void
    {
        $dropdown = $this->element('<twig:LleCruditUi:Dropdown label="More actions for V-0412" iconOnly floating align="start" size="small">…</twig:LleCruditUi:Dropdown>', 'div');

        self::assertStringStartsWith('crudit-ui-dropdown-', (string) $dropdown->getAttribute('id'));
        self::assertSame('true', $dropdown->getAttribute('data-' . self::DROPDOWN . '-floating-value'));
        self::assertSame('start', $dropdown->getAttribute('data-' . self::DROPDOWN . '-align-value'));

        $button = $dropdown->firstElementChild;
        self::assertSame('crudit-ui-icon-btn crudit-ui-icon-btn--small', $button?->getAttribute('class'));
        self::assertSame('More actions for V-0412', $button->querySelector('.crudit-ui-visually-hidden')?->textContent);
        self::assertNull($button->querySelector('.crudit-ui-dropdown__chevron'));
        self::assertSame('crudit-ui-dropdown__panel crudit-ui-dropdown__panel--start crudit-ui-dropdown__panel--floating', $dropdown->querySelector('[hidden]')?->getAttribute('class'));
    }

    public function testAttributesOfTheButtonAndOfThePanelAndTriggerBlock(): void
    {
        $dropdown = $this->element('<twig:LleCruditUi:Dropdown label="Account" trigger:class="crudit-ui-is-hover" trigger:data-action="tracking#menu" panel:class="wide" panel:aria-label="Account"><twig:block name="trigger"><span class="avatar">VH</span> Valentin</twig:block>…</twig:LleCruditUi:Dropdown>', 'div');

        $button = $dropdown->firstElementChild;
        self::assertSame('crudit-ui-btn crudit-ui-btn--secondary crudit-ui-is-hover', $button?->getAttribute('class'));
        // The action of the project comes on top of the toggle.
        self::assertSame(self::DROPDOWN . '#toggle tracking#menu', $button->getAttribute('data-action'));
        self::assertSame('VH Valentin', $button->textContent);
        self::assertSame('crudit-ui-dropdown__panel wide', $dropdown->querySelector('[hidden]')?->getAttribute('class'));
        self::assertSame('Account', $dropdown->querySelector('[hidden]')->getAttribute('aria-label'));
        // The nested attributes stay off the root.
        self::assertFalse($dropdown->hasAttribute('trigger:class'));
        self::assertSame('crudit-ui-dropdown', $dropdown->getAttribute('class'));
    }

    public function testMenuItems(): void
    {
        $menu = $this->element(<<<'TWIG'
            <twig:LleCruditUi:Menu>
                <twig:LleCruditUi:MenuItem icon="edit" href="/visits/1/edit">Edit</twig:LleCruditUi:MenuItem>
                <twig:LleCruditUi:MenuItem data-action="visit#duplicate">Duplicate</twig:LleCruditUi:MenuItem>
                <twig:LleCruditUi:MenuItem icon="delete" tone="danger">Delete</twig:LleCruditUi:MenuItem>
            </twig:LleCruditUi:Menu>
            TWIG, 'ul');

        self::assertSame('crudit-ui-menu', $menu->getAttribute('class'));
        $items = iterator_to_array($menu->querySelectorAll('li > .crudit-ui-menu__item'));
        self::assertCount(3, $items);

        self::assertSame('a', $items[0]->localName);
        self::assertSame('/visits/1/edit', $items[0]->getAttribute('href'));
        self::assertSame('true', $items[0]->querySelector('svg')?->getAttribute('aria-hidden'));
        self::assertSame('Edit', $items[0]->querySelector('.crudit-ui-menu__text')?->textContent);

        self::assertSame('button', $items[1]->localName);
        self::assertSame('button', $items[1]->getAttribute('type'));
        self::assertSame('visit#duplicate', $items[1]->getAttribute('data-action'));

        self::assertSame('crudit-ui-menu__item crudit-ui-menu__item--danger', $items[2]->getAttribute('class'));
    }

    public function testDisabledItemKeepsTheFocusAndItsReason(): void
    {
        $menu = $this->element(<<<'TWIG'
            <twig:LleCruditUi:Menu>
                <twig:LleCruditUi:MenuItem href="/visits/1/archive" tone="danger" reason="Only a finished visit can be archived">Archive</twig:LleCruditUi:MenuItem>
                <twig:LleCruditUi:MenuItem disabled>Duplicate</twig:LleCruditUi:MenuItem>
            </twig:LleCruditUi:Menu>
            TWIG, 'ul');

        [$archive, $duplicate] = iterator_to_array($menu->querySelectorAll('.crudit-ui-menu__item'));
        // A disabled link loses its href: a button, focusable, that the controller button ignores.
        self::assertSame('button', $archive->localName);
        self::assertFalse($archive->hasAttribute('href'));
        self::assertFalse($archive->hasAttribute('disabled'));
        self::assertSame('true', $archive->getAttribute('aria-disabled'));
        self::assertSame('2lenet--crudit-ui-bundle--button', $archive->getAttribute('data-controller'));
        // No danger colour on a disabled item; its reason is read with its label.
        self::assertSame('crudit-ui-menu__item', $archive->getAttribute('class'));
        self::assertSame('ArchiveOnly a finished visit can be archived', $archive->textContent);
        self::assertSame('Only a finished visit can be archived', $archive->querySelector('.crudit-ui-menu__reason')?->textContent);

        self::assertSame('true', $duplicate->getAttribute('aria-disabled'));
        self::assertNull($duplicate->querySelector('.crudit-ui-menu__reason'));
    }

    public function testCurrentOptionAndGroup(): void
    {
        $menu = $this->element(<<<'TWIG'
            <twig:LleCruditUi:Menu>
                <twig:LleCruditUi:MenuGroup label="Language">
                    <twig:LleCruditUi:MenuItem href="/fr" :current="true">Français</twig:LleCruditUi:MenuItem>
                    <twig:LleCruditUi:MenuItem href="/en" :current="false">English</twig:LleCruditUi:MenuItem>
                </twig:LleCruditUi:MenuGroup>
                <twig:LleCruditUi:MenuItem>Settings</twig:LleCruditUi:MenuItem>
            </twig:LleCruditUi:Menu>
            TWIG, 'ul');

        $group = $menu->querySelector('li.crudit-ui-menu__group');
        $heading = $group?->querySelector('span.crudit-ui-menu__heading');
        self::assertSame('Language', $heading?->textContent);
        self::assertSame($heading->getAttribute('id'), $group->querySelector('ul.crudit-ui-menu__sub')?->getAttribute('aria-labelledby'));

        [$french, $english] = iterator_to_array($group->querySelectorAll('.crudit-ui-menu__item'));
        self::assertSame('true', $french->getAttribute('aria-current'));
        self::assertNotNull($french->querySelector('.crudit-ui-menu__check svg'));
        // The room of the check is kept, empty.
        self::assertFalse($english->hasAttribute('aria-current'));
        self::assertSame('', $english->querySelector('.crudit-ui-menu__check')?->innerHTML);
        // Without current, no check.
        self::assertNull($menu->lastElementChild?->querySelector('.crudit-ui-menu__check'));
    }

    public function testTooltipIsVisualOnly(): void
    {
        $host = $this->element('<twig:LleCruditUi:Tooltip text="Edit"><twig:LleCruditUi:IconButton icon="edit" label="Edit"/></twig:LleCruditUi:Tooltip>', 'span');

        self::assertSame('crudit-ui-tooltip-host', $host->getAttribute('class'));
        self::assertSame(self::TOOLTIP, $host->getAttribute('data-controller'));
        self::assertFalse($host->hasAttribute('data-' . self::TOOLTIP . '-floating-value'));
        self::assertSame('Edit', $host->querySelector('button .crudit-ui-visually-hidden')?->textContent);

        $tooltip = $host->querySelector('.crudit-ui-tooltip');
        self::assertSame('crudit-ui-tooltip', $tooltip?->getAttribute('class'));
        self::assertSame('true', $tooltip->getAttribute('aria-hidden'));
        self::assertSame('tooltip', $tooltip->getAttribute('data-' . self::TOOLTIP . '-target'));
        self::assertSame('Edit', $tooltip->textContent);
    }

    public function testTooltipPlacements(): void
    {
        $below = $this->element('<twig:LleCruditUi:Tooltip text="Copy" placement="below" align="start"><button type="button">Copy</button></twig:LleCruditUi:Tooltip>', 'span');
        self::assertSame('crudit-ui-tooltip crudit-ui-tooltip--below crudit-ui-tooltip--start', $below->querySelector('.crudit-ui-tooltip')?->getAttribute('class'));

        // Beside is always floating: placed by the controller.
        $beside = $this->element('<twig:LleCruditUi:Tooltip text="Visits" placement="beside"><a href="/visits">Visits</a></twig:LleCruditUi:Tooltip>', 'span');
        self::assertSame('true', $beside->getAttribute('data-' . self::TOOLTIP . '-floating-value'));
        self::assertSame('beside', $beside->getAttribute('data-' . self::TOOLTIP . '-placement-value'));
        self::assertSame('crudit-ui-tooltip crudit-ui-tooltip--floating', $beside->querySelector('.crudit-ui-tooltip')?->getAttribute('class'));
    }
}
