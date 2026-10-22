<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Header of a page: PageHeader, Breadcrumb.
 */
class PageTest extends TestCase
{
    use RendersTwig;

    private const string ITEMS = "[{label: 'Home', url: '/'}, {label: 'Operations'}, {label: 'Visits', url: '/visits'}, {label: 'VIS-12', url: '/visits/12'}]";

    protected function tearDown(): void
    {
        (new Filesystem())->remove(sys_get_temp_dir() . '/lle_crudit_ui_bundle');
    }

    public function testBreadcrumbIsANamedNavWithAnOrderedList(): void
    {
        $nav = $this->element('<twig:LleCruditUi:Breadcrumb :items="' . self::ITEMS . '"/>', 'nav');

        self::assertSame('crudit-ui-breadcrumb', $nav->getAttribute('class'));
        self::assertSame('Breadcrumb', $nav->getAttribute('aria-label'));
        self::assertCount(4, $nav->querySelectorAll('ol.crudit-ui-breadcrumb__list > li.crudit-ui-breadcrumb__item'));
    }

    public function testCurrentPageIsNeverALink(): void
    {
        $nav = $this->element('<twig:LleCruditUi:Breadcrumb :items="' . self::ITEMS . '"/>', 'nav');

        $current = $nav->querySelector('li:last-child > span.crudit-ui-breadcrumb__current');
        self::assertSame('page', $current?->getAttribute('aria-current'));
        self::assertSame('VIS-12', $current->textContent);
        self::assertNull($nav->querySelector('li:last-child a'));
    }

    public function testLinksAndTexts(): void
    {
        $nav = $this->element('<twig:LleCruditUi:Breadcrumb :items="' . self::ITEMS . '"/>', 'nav');

        $links = array_map(static fn (\Dom\Element $link): ?string => $link->getAttribute('href'), iterator_to_array($nav->querySelectorAll('a.crudit-ui-breadcrumb__link')));
        self::assertSame(['/', '/visits'], $links);
        self::assertSame('Operations', $nav->querySelector('span.crudit-ui-breadcrumb__text')?->textContent);
    }

    public function testSeparatorsAreHiddenFromTheSecondItem(): void
    {
        $nav = $this->element('<twig:LleCruditUi:Breadcrumb :items="' . self::ITEMS . '"/>', 'nav');

        self::assertNull($nav->querySelector('li:first-child .crudit-ui-breadcrumb__separator'));
        $separators = $nav->querySelectorAll('.crudit-ui-breadcrumb__separator');
        self::assertCount(3, $separators);
        foreach ($separators as $separator) {
            self::assertSame('true', $separator->getAttribute('aria-hidden'));
        }
    }

    /**
     * @return iterable<string, array{string, ?string}>
     */
    public static function backLinks(): iterable
    {
        yield 'the parent' => [self::ITEMS, 'Visits'];
        yield 'the last level with a link' => ["[{label: 'Home', url: '/'}, {label: 'Operations'}, {label: 'Settings'}]", 'Home'];
        yield 'no level with a link' => ["[{label: 'Operations'}, {label: 'Settings'}]", null];
    }

    /**
     * Below 768 px only the item --back is shown, as a link "← parent".
     */
    #[DataProvider('backLinks')]
    public function testBackLinkIsTheLastLevelWithALinkBeforeTheCurrentPage(string $items, ?string $back): void
    {
        $nav = $this->element('<twig:LleCruditUi:Breadcrumb :items="' . $items . '"/>', 'nav');

        $items = $nav->querySelectorAll('.crudit-ui-breadcrumb__item--back');
        self::assertCount($back === null ? 0 : 1, $items);
        if ($back !== null) {
            self::assertSame($back, $items[0]?->querySelector('a')?->textContent);
            self::assertSame('true', $items[0]->querySelector('.crudit-ui-breadcrumb__back')?->getAttribute('aria-hidden'));
        }
        self::assertCount($back === null ? 0 : 1, $nav->querySelectorAll('.crudit-ui-breadcrumb__back'));
        self::assertSame($back === null, str_contains((string) $nav->getAttribute('class'), 'crudit-ui-breadcrumb--no-back'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function tooShort(): iterable
    {
        yield 'home page' => ["[{label: 'Home', url: '/'}]"];
        yield 'none' => ['[]'];
    }

    #[DataProvider('tooShort')]
    public function testNothingWithFewerThanTwoItems(string $items): void
    {
        self::assertSame('', $this->render(new TestKernel(), '<twig:LleCruditUi:Breadcrumb :items="' . $items . '"/>'));
    }

    public function testNameAndAttributesOfTheProject(): void
    {
        $nav = $this->element('<twig:LleCruditUi:Breadcrumb :items="' . self::ITEMS . '" label="Path to the visit" id="crumbs" class="mb-2"/>', 'nav');

        self::assertSame('Path to the visit', $nav->getAttribute('aria-label'));
        self::assertSame('crumbs', $nav->getAttribute('id'));
        self::assertSame('crudit-ui-breadcrumb mb-2', $nav->getAttribute('class'));
    }

    public function testLabelsEscapedOnce(): void
    {
        $html = $this->render(new TestKernel(), '<twig:LleCruditUi:Breadcrumb :items="items"/>', [
            'items' => [['label' => 'R&D', 'url' => '/rd?a=1&b=2'], ['label' => '<b>Visit</b>']],
        ]);

        self::assertStringContainsString('>R&amp;D</a>', $html);
        self::assertStringContainsString('href="/rd?a=1&amp;b=2"', $html);
        self::assertStringContainsString('&lt;b&gt;Visit&lt;/b&gt;', $html);
    }

    public function testPageHeaderWithATitleOnly(): void
    {
        $header = $this->element('<twig:LleCruditUi:PageHeader title="Visits"/>', 'div');

        self::assertSame('crudit-ui-page-header', $header->getAttribute('class'));
        $title = $header->querySelector('h1');
        self::assertSame('page-title', $title?->getAttribute('id'));
        self::assertSame('-1', $title->getAttribute('tabindex'));
        self::assertSame('Visits', $title->textContent);
        self::assertNull($header->querySelector('.crudit-ui-page-header__subtitle'));
        // No element: top and actions hidden by :not(:has(> *)); no metadata: no zone at all
        foreach (['top', 'actions'] as $zone) {
            self::assertSame('', $header->querySelector('.crudit-ui-page-header__' . $zone)?->innerHTML, $zone);
        }
        self::assertNull($header->querySelector('.crudit-ui-page-header__meta'));
    }

    public function testPageHeaderWithEverything(): void
    {
        $header = $this->element(
            '<twig:LleCruditUi:PageHeader title="Visit VIS-12" subtitle="Boiler" titleId="visit-title" id="visit-header" class="mb-3" :breadcrumb="' . self::ITEMS . '">'
            . '<twig:block name="top"><a href="/next">Next</a></twig:block>'
            . '<twig:block name="badge"><twig:LleCruditUi:Status tone="warning">To plan</twig:LleCruditUi:Status></twig:block>'
            . '<twig:block name="meta">Created on 12 October</twig:block>'
            . '<twig:block name="actions"><button type="button">Edit</button></twig:block>'
            . '</twig:LleCruditUi:PageHeader>',
            'div',
        );

        self::assertSame('crudit-ui-page-header mb-3', $header->getAttribute('class'));
        self::assertSame('visit-header', $header->getAttribute('id'), 'the id of the project on the root');
        self::assertSame('Visit VIS-12', $header->querySelector('h1#visit-title')?->textContent);
        self::assertNotNull($header->querySelector('.crudit-ui-page-header__top > nav.crudit-ui-breadcrumb + a[href="/next"]'));
        self::assertNotNull($header->querySelector('.crudit-ui-page-header__title-row > h1 + .crudit-ui-status'));
        self::assertSame('Boiler', $header->querySelector('p.crudit-ui-page-header__subtitle')?->textContent);
        self::assertSame('Created on 12 October', $header->querySelector('.crudit-ui-page-header__meta')?->textContent);
        self::assertSame('Edit', $header->querySelector('.crudit-ui-page-header__actions > button')?->textContent);
    }

    public function testBreadcrumbBlockReplacesTheBreadcrumb(): void
    {
        $header = $this->element(
            '<twig:LleCruditUi:PageHeader title="Visits" :breadcrumb="' . self::ITEMS . '">'
            . '<twig:block name="breadcrumb"><a href="/">Back</a></twig:block>'
            . '</twig:LleCruditUi:PageHeader>',
            'div',
        );

        self::assertNull($header->querySelector('nav'));
        self::assertSame('Back', $header->querySelector('.crudit-ui-page-header__top > a')?->textContent);
    }

    /**
     * A block that renders nothing but spaces (no breadcrumb on the home page): the zone has no element, hidden in CSS.
     */
    public function testBreadcrumbBlockWithSpacesOnly(): void
    {
        $header = $this->element(
            "<twig:LleCruditUi:PageHeader title=\"Home\">\n<twig:block name=\"breadcrumb\">\n    {% if false %}<nav></nav>{% endif %}\n</twig:block>\n</twig:LleCruditUi:PageHeader>",
            'div',
        );

        self::assertSame(0, $header->querySelector('.crudit-ui-page-header__top')?->childElementCount);
    }

    /**
     * A block meta that renders nothing but spaces (an {% if %} on several lines): no zone, no margin left.
     */
    public function testMetaBlockWithSpacesOnly(): void
    {
        $header = $this->element(
            "<twig:LleCruditUi:PageHeader title=\"Visit\">\n<twig:block name=\"meta\">\n    {% if false %}Created{% endif %}\n</twig:block>\n</twig:LleCruditUi:PageHeader>",
            'div',
        );

        self::assertNull($header->querySelector('.crudit-ui-page-header__meta'));
    }

    public function testEmptySubtitleIsNotRendered(): void
    {
        $header = $this->element('<twig:LleCruditUi:PageHeader title="Visits" subtitle=""/>', 'div');

        self::assertNull($header->querySelector('.crudit-ui-page-header__subtitle'));
    }
}
