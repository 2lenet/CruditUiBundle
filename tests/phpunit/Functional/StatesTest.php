<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Components of a whole zone: Spinner, Skeleton, EmptyState, Alert.
 */
class StatesTest extends TestCase
{
    use RendersTwig;

    protected function tearDown(): void
    {
        (new Filesystem())->remove(sys_get_temp_dir() . '/lle_crudit_ui_bundle');
    }

    public function testSpinnerIsAStatus(): void
    {
        $spinner = $this->element('<twig:LleCruditUi:Spinner/>', 'span');

        self::assertSame('status', $spinner->getAttribute('role'));
        self::assertSame('Loading…', $spinner->querySelector('.crudit-ui-visually-hidden')?->textContent);
        self::assertSame('Saving…', $this->element('<twig:LleCruditUi:Spinner label="Saving…"/>', 'span')->textContent);
    }

    public function testSkeletonIsHidden(): void
    {
        $skeleton = $this->element('<twig:LleCruditUi:Skeleton variant="title"/>', 'span');

        self::assertSame('crudit-ui-skeleton crudit-ui-skeleton--title', $skeleton->getAttribute('class'));
        self::assertSame('true', $skeleton->getAttribute('aria-hidden'));
        self::assertSame('crudit-ui-skeleton', $this->element('<twig:LleCruditUi:Skeleton/>', 'span')->getAttribute('class'));
    }

    public function testEmptyStateWithTitleTextAndActions(): void
    {
        $empty = $this->element('<twig:LleCruditUi:EmptyState title="No visit" :level="2">Create the first one.<twig:block name="actions"><a href="/new">Create</a></twig:block></twig:LleCruditUi:EmptyState>', 'div');

        self::assertSame('true', $empty->querySelector('.crudit-ui-empty-state__icon')?->getAttribute('aria-hidden'));
        self::assertSame('No visit', $empty->querySelector('h2.crudit-ui-empty-state__title')?->textContent);
        self::assertSame('Create the first one.', $empty->querySelector('div.crudit-ui-empty-state__text')?->textContent);
        self::assertSame('Create', $empty->querySelector('.crudit-ui-empty-state__actions a')?->textContent);
    }

    public function testCompactEmptyStateWithoutTitleNorAction(): void
    {
        $empty = $this->element('<twig:LleCruditUi:EmptyState compact :icon="null">No visit to plan.</twig:LleCruditUi:EmptyState>', 'div');

        self::assertSame('crudit-ui-empty-state crudit-ui-empty-state--compact', $empty->getAttribute('class'));
        self::assertNull($empty->querySelector('.crudit-ui-empty-state__icon'));
        self::assertNull($empty->querySelector('h3'));
        // Hidden by :empty.
        self::assertSame('', $empty->querySelector('.crudit-ui-empty-state__actions')?->innerHTML);
    }

    public function testAlertReadsItsTypeFirst(): void
    {
        $alert = $this->element('<twig:LleCruditUi:Alert tone="danger" role="alert">The server refused the record.</twig:LleCruditUi:Alert>', 'div');

        self::assertSame('crudit-ui-alert crudit-ui-alert--danger', $alert->getAttribute('class'));
        self::assertSame('alert', $alert->getAttribute('role'));
        self::assertSame('true', $alert->querySelector('.crudit-ui-alert__icon')?->getAttribute('aria-hidden'));
        self::assertSame('Error: The server refused the record.', $alert->querySelector('div.crudit-ui-alert__text')?->textContent);
        self::assertNull($alert->querySelector('button'));
    }

    public function testAlertWithTitleActionsAndInline(): void
    {
        $alert = $this->element('<twig:LleCruditUi:Alert tone="warning" title="Late visit" inline>Planned 5 days ago.<twig:block name="actions"><button type="button">Plan</button></twig:block></twig:LleCruditUi:Alert>', 'div');

        self::assertSame('crudit-ui-alert crudit-ui-alert--warning crudit-ui-alert--inline', $alert->getAttribute('class'));
        // With a title, the type is read before the title.
        self::assertSame('Warning: Late visit', $alert->querySelector('.crudit-ui-alert__title')?->textContent);
        self::assertSame('Planned 5 days ago.', $alert->querySelector('.crudit-ui-alert__text')?->textContent);
        self::assertSame('Plan', $alert->querySelector('.crudit-ui-alert__actions button')?->textContent);
    }

    public function testDismissibleAlertHasANamedCloseButton(): void
    {
        $alert = $this->element('<twig:LleCruditUi:Alert tone="success" dismissible>Visit saved.</twig:LleCruditUi:Alert>', 'div');

        self::assertSame('2lenet--crudit-ui-bundle--alert', $alert->getAttribute('data-controller'));
        $close = $alert->querySelector('button.crudit-ui-alert__close');
        self::assertNotNull($close);
        self::assertSame('2lenet--crudit-ui-bundle--alert#close', $close->getAttribute('data-action'));
        self::assertSame('Close', $close->querySelector('.crudit-ui-visually-hidden')?->textContent);
    }

    public function testContentMayHoldSeveralParagraphs(): void
    {
        $alert = $this->element('<twig:LleCruditUi:Alert><p>First.</p><ul><li>Second</li></ul></twig:LleCruditUi:Alert>', 'div');
        self::assertCount(1, $alert->querySelectorAll('.crudit-ui-alert__text p'));
        self::assertCount(1, $alert->querySelectorAll('.crudit-ui-alert__text ul'));

        $empty = $this->element('<twig:LleCruditUi:EmptyState><p>First.</p><p>Second.</p></twig:LleCruditUi:EmptyState>', 'div');
        self::assertCount(2, $empty->querySelectorAll('.crudit-ui-empty-state__text p'));
    }

    public function testLevelOfTheTitleOfAnEmptyStateIsBounded(): void
    {
        $empty = $this->element('<twig:LleCruditUi:EmptyState title="No visit" level="x"/>', 'div');
        self::assertNotNull($empty->querySelector('h3'));

        $empty = $this->element('<twig:LleCruditUi:EmptyState title="No visit" :level="9"/>', 'div');
        self::assertNotNull($empty->querySelector('h3'));
    }
}
