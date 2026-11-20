<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Tests\Functional;

use PHPUnit\Framework\TestCase;

/**
 * LleCruditUi:Pager (docs/components.md).
 */
class PagerTest extends TestCase
{
    use RendersTwig;

    private const string PAGER = '2lenet--crudit-ui-bundle--pager';

    private function pager(string $props): \Dom\Element
    {
        return $this->element('<twig:LleCruditUi:Pager id="visits-pager" label="visits" path="/visits" ' . $props . '/>', 'div');
    }

    /**
     * @return list<string>
     */
    private function numbers(\Dom\Element $root): array
    {
        $numbers = [];
        foreach ($root->querySelectorAll('.crudit-ui-pager__number') as $item) {
            $numbers[] = trim(str_replace("\u{a0}", ' ', (string) $item->textContent));
        }

        return $numbers;
    }

    public function testNothingIsRenderedWithoutResult(): void
    {
        $html = $this->render(new TestKernel(), '<twig:LleCruditUi:Pager id="p" label="visits" :total="0" :perPage="30" path="/visits"/>');

        self::assertSame('', trim($html));
    }

    public function testASinglePageHasTheSummaryAndNoNavigation(): void
    {
        $root = $this->pager(':total="12" :perPage="30"');

        self::assertSame('1 to 12 of 12 visits', trim((string) $root->querySelector('.crudit-ui-pager__summary')?->textContent));
        self::assertNull($root->querySelector('nav'));
        self::assertNull($root->querySelector('form'), 'no selector without choices');
    }

    public function testTheFirstPageHasNoLinkBack(): void
    {
        $root = $this->pager(':total="131" :perPage="30" :page="1"');

        self::assertSame('1 to 30 of 131 visits', trim((string) $root->querySelector('.crudit-ui-pager__summary')?->textContent));
        self::assertSame('Pagination of the visits', $root->querySelector('nav')?->getAttribute('aria-label'));
        self::assertCount(2, $root->querySelectorAll('[role="link"][aria-disabled="true"]'), 'first and previous: not links, no dead link, read as unavailable');
        self::assertCount(0, $root->querySelectorAll('[aria-disabled="true"][href]'));
        self::assertSame('page', $root->querySelector('[aria-current]')?->getAttribute('aria-current'));
        self::assertNull($root->querySelector('[aria-current]')->getAttribute('href'), 'the current page is not a link');
        self::assertSame('/visits?page=2', $root->querySelector('a[href*="page=2"]')?->getAttribute('href'));
        self::assertSame('/visits?page=5', $root->querySelector('.crudit-ui-pager__edge:last-child a')?->getAttribute('href'), 'last page: 131 / 30 = 5');
    }

    public function testTheLastPageHasNoLinkForward(): void
    {
        $root = $this->pager(':total="131" :perPage="30" :page="5"');

        self::assertSame('121 to 131 of 131 visits', trim((string) $root->querySelector('.crudit-ui-pager__summary')?->textContent));
        self::assertSame('5 / 5', trim((string) $root->querySelector('.crudit-ui-pager__status')?->textContent));
        self::assertCount(2, $root->querySelectorAll('[aria-disabled="true"]'));
        self::assertSame('/visits?page=4', $root->querySelector('li:nth-child(2) a')?->getAttribute('href'));
    }

    public function testTheNumbersAreTheFirstTheLastAndTheNeighbours(): void
    {
        self::assertSame(['Page 1', 'Page 2', 'Page 3', 'Page 4', 'Page 5'], $this->numbers($this->pager(':total="131" :perPage="30" :page="3"')));
        self::assertSame(['Page 1', 'Page 2', 'Page 3', 'Page 4', 'Page 5', '…', 'Page 12'], $this->numbers($this->pager(':total="360" :perPage="30" :page="4"')), 'a gap of one page shows its number');
        self::assertSame(['Page 1', '…', 'Page 5', 'Page 6', 'Page 7', '…', 'Page 12'], $this->numbers($this->pager(':total="360" :perPage="30" :page="6"')));
        self::assertSame(['Page 1', 'Page 2', '…', 'Page 12'], $this->numbers($this->pager(':total="360" :perPage="30" :page="1"')));
        self::assertSame(['Page 1', '…', 'Page 11', 'Page 12'], $this->numbers($this->pager(':total="360" :perPage="30" :page="12"')));
    }

    public function testAPageBeyondTheEndIsTheLastOne(): void
    {
        $root = $this->pager(':total="60" :perPage="30" :page="9"');

        self::assertSame('31 to 60 of 60 visits', trim((string) $root->querySelector('.crudit-ui-pager__summary')?->textContent));
    }

    public function testTheOffsetModeHoldsTheNumberOfTheFirstRow(): void
    {
        $root = $this->pager(':total="131" :perPage="30" :page="2" mode="offset" pageParam="visits_offset" limitParam="visits_limit"');

        self::assertSame('/visits?visits_offset=0', $root->querySelector('.crudit-ui-pager__edge:first-child a')?->getAttribute('href'));
        self::assertSame('/visits?visits_offset=60', $root->querySelector('li:nth-last-child(2) a')?->getAttribute('href'));
        self::assertSame('/visits?visits_offset=120', $root->querySelector('.crudit-ui-pager__edge:last-child a')?->getAttribute('href'));
    }

    public function testTheOtherParametersAndTheAnchorAreKept(): void
    {
        $root = $this->pager(':total="131" :perPage="30" :page="2" :query="{q: \'a&b\', filter: {status: [\'late\', \'open\']}, page: 9, limit: 10}" anchor="#history"');

        $href = (string) $root->querySelector('li:nth-last-child(2) a')?->getAttribute('href');
        self::assertStringEndsWith('#history', $href);
        self::assertStringContainsString('q=a%26b', $href);
        self::assertStringContainsString('filter%5Bstatus%5D%5B0%5D=late', $href);
        self::assertSame(1, substr_count($href, 'page='), 'the page of the query is replaced, not repeated');
        self::assertStringContainsString('limit=10', $href, 'the links keep the rows per page of the query');
    }

    public function testTheRowsPerPageGoBackToTheFirstPage(): void
    {
        $root = $this->pager(':total="131" :perPage="30" :page="3" :perPageChoices="[10, 30, 50, 100]" :query="{q: \'a\', filter: {status: [\'late\']}, page: 3}" anchor="#history"');

        self::assertSame(self::PAGER, $root->getAttribute('data-controller'));
        $form = $root->querySelector('form');
        self::assertSame('get', $form?->getAttribute('method'));
        self::assertSame('/visits#history', $form->getAttribute('action'));
        $fields = [];
        foreach ($form->querySelectorAll('input[type="hidden"]') as $input) {
            $fields[(string) $input->getAttribute('name')] = $input->getAttribute('value');
        }
        self::assertSame(['q' => 'a', 'filter[status][0]' => 'late', 'page' => '1'], $fields, 'the first page is sent, the old page and limit are not');
        $select = $form->querySelector('select');
        self::assertSame('limit', $select?->getAttribute('name'));
        self::assertSame(['10', '30', '50', '100'], array_map(static fn (\Dom\Element $o): string => (string) $o->getAttribute('value'), iterator_to_array($select->querySelectorAll('option'))));
        self::assertSame('30', $select->querySelector('option[selected]')?->getAttribute('value'));
        self::assertSame('Rows per page', trim((string) $form->querySelector('label[for="visits-pager-limit"]')?->textContent));
        self::assertSame('visits-pager-limit-help', $select->getAttribute('aria-describedby'));
        self::assertNotNull($root->querySelector('#visits-pager-limit-help'));
        self::assertSame('pointerdown->' . self::PAGER . '#pointer', $root->getAttribute('data-action'), 'a press on a link drops the choice waiting');
        self::assertSame('change->' . self::PAGER . '#submit keydown->' . self::PAGER . '#key blur->' . self::PAGER . '#leave', $select->getAttribute('data-action'), 'the keyboard: an arrow waits for Enter or for the end of the focus');
        self::assertStringContainsString('<noscript>', $this->render(new TestKernel(), '<twig:LleCruditUi:Pager id="p" label="v" :total="131" :perPage="30" :perPageChoices="[10, 30]" path="/v"/>'));
    }

    public function testTheOffsetModeSendsTheFirstRowOfTheFirstPage(): void
    {
        $root = $this->pager(':total="131" :perPage="30" :page="3" mode="offset" pageParam="visits_offset" limitParam="visits_limit" :perPageChoices="[10, 30]" :query="{visits_offset: 60, visits_limit: 30, q: \'a\'}"');

        $fields = [];
        foreach ($root->querySelectorAll('form input[type="hidden"]') as $input) {
            $fields[(string) $input->getAttribute('name')] = $input->getAttribute('value');
        }
        self::assertSame(['q' => 'a', 'visits_offset' => '0'], $fields, 'the server keeps the offset in the session: it must be told');
        self::assertSame('visits_limit', $root->querySelector('select')?->getAttribute('name'));
    }

    public function testTheRowsPerPageInUseIsAlwaysAChoice(): void
    {
        $root = $this->pager(':total="131" :perPage="25" :perPageChoices="[50, 10, 30, 30]"');
        $select = $root->querySelector('select');

        self::assertSame(['10', '25', '30', '50'], array_map(static fn (\Dom\Element $o): string => (string) $o->getAttribute('value'), iterator_to_array($select?->querySelectorAll('option') ?? [])));
        self::assertSame('25', $select?->querySelector('option[selected]')?->getAttribute('value'), 'the selector shows what the list shows');

        $small = $this->pager(':total="8" :perPage="5" :perPageChoices="[10, 30]"');
        self::assertNotNull($small->querySelector('select'), 'a page of 5 is not stuck: the user can choose more');
    }

    public function testTheNumbersFollowTheLanguage(): void
    {
        $root = $this->pager(':total="12345" :perPage="10" :page="1234"');

        self::assertSame('12,331 to 12,340 of 12,345 visits', trim((string) $root->querySelector('.crudit-ui-pager__summary')?->textContent));
        self::assertSame('1,234 / 1,235', trim((string) $root->querySelector('.crudit-ui-pager__status')?->textContent));
        self::assertStringContainsString('Last page (1,235)', (string) $root->textContent, 'the name of the last page uses the same number');
    }

    public function testTheActionOfTheProjectIsAddedToTheOneOfThePager(): void
    {
        $root = $this->pager(':total="131" :perPage="30" :perPageChoices="[10, 30]" data-controller="project" data-action="click->project#log"');

        self::assertSame('pointerdown->' . self::PAGER . '#pointer click->project#log', $root->getAttribute('data-action'));
        self::assertSame(self::PAGER . ' project', $root->getAttribute('data-controller'), 'the controller of the project does not replace the one of the pager');
        $without = $this->pager(':total="131" :perPage="30" data-controller="project" data-action="click->project#log"');
        self::assertSame('click->project#log', $without->getAttribute('data-action'), 'no selector, no action of the pager');
        self::assertSame('project', $without->getAttribute('data-controller'), 'no selector, no controller of the pager');
    }

    public function testNoSelectorWhenEverythingFitsTheSmallestChoice(): void
    {
        $root = $this->pager(':total="8" :perPage="10" :perPageChoices="[10, 30, 50]"');

        self::assertNull($root->querySelector('form'));
        self::assertNull($root->getAttribute('data-controller'));
    }

    public function testThePathAndTheParametersAreEscaped(): void
    {
        $html = $this->render(new TestKernel(), '<twig:LleCruditUi:Pager id="p" label="visits" :total="90" :perPage="30" path="/v?x=&quot;><script>" :query="{q: \'<b>\'}"/>');

        self::assertStringNotContainsString('<script>', $html);
        self::assertStringNotContainsString('<b>', $html);
    }
}
