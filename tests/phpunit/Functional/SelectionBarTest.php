<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Tests\Functional;

use PHPUnit\Framework\TestCase;

/**
 * LleCruditUi:SelectionBar (docs/components.md).
 */
class SelectionBarTest extends TestCase
{
    use RendersTwig;

    private const string BAR = '2lenet--crudit-ui-bundle--selection-bar';

    public function testTheBarIsHiddenAndListensToTheTable(): void
    {
        $root = $this->element('<twig:LleCruditUi:SelectionBar table="visits" :total="137"/>', 'div');

        self::assertSame(self::BAR, $root->getAttribute('data-controller'));
        self::assertSame('visits', $root->getAttribute('data-' . self::BAR . '-table-value'));
        self::assertSame('137', $root->getAttribute('data-' . self::BAR . '-total-value'));
        self::assertSame(
            '2lenet--crudit-ui-bundle--table:select@window->' . self::BAR . '#update '
            . '2lenet--crudit-ui-bundle--table:paginate@window->' . self::BAR . '#keepForPager',
            $root->getAttribute('data-action'),
        );
        self::assertTrue($root->querySelector('[role="region"]')?->hasAttribute('hidden'));
        self::assertSame('Actions on the selection', $root->querySelector('[role="region"]')->getAttribute('aria-label'));
        self::assertSame('status', $root->querySelector('p.crudit-ui-visually-hidden')?->getAttribute('role'));
        self::assertSame('%count% rows selected', $root->getAttribute('data-' . self::BAR . '-many-value'));
        self::assertTrue($root->querySelector('[data-' . self::BAR . '-target="selectAll"]')?->hasAttribute('hidden'));
    }

    public function testTheActionsAndTheDialogsAreInTheScopeOfTheController(): void
    {
        $root = $this->element(
            '<twig:LleCruditUi:SelectionBar table="visits" label="Actions on the visits"><twig:block name="actions"><a href="/export" data-action="' . self::BAR . '#fill">Export</a></twig:block><twig:block name="after"><dialog id="assign"></dialog></twig:block></twig:LleCruditUi:SelectionBar>',
            'div',
        );

        self::assertSame('Actions on the visits', $root->querySelector('[role="region"]')?->getAttribute('aria-label'));
        self::assertSame('Export', $root->querySelector('.crudit-ui-selection-bar__actions a')?->textContent);
        self::assertNotNull($root->querySelector('dialog#assign'));
        self::assertSame('Clear selection', trim((string) $root->querySelector('[data-' . self::BAR . '-target="clearButton"]')?->textContent));
    }
}
