<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * LleCruditUi:Table, LleCruditUi:Table:Row and LleCruditUi:Table:Actions (docs/components.md).
 */
class TableTest extends TestCase
{
    use RendersTwig;

    private const string TABLE = '2lenet--crudit-ui-bundle--table';
    private const string SORT = '2lenet--crudit-ui-bundle--table-sort';

    protected function tearDown(): void
    {
        (new Filesystem())->remove(sys_get_temp_dir() . '/lle_crudit_ui_bundle');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function columns(): array
    {
        return [
            ['id' => 'ref', 'label' => 'Visit', 'primary' => true, 'card' => true, 'sortUrl' => '/visits?sort=ref'],
            ['id' => 'customer', 'label' => 'Customer', 'card' => true, 'info' => 'Who ordered'],
            ['id' => 'amount', 'label' => 'Amount', 'align' => 'end', 'priority' => 'low'],
            ['id' => 'secret', 'label' => 'Secret', 'hidden' => true],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rows(): array
    {
        $actions = [
            ['label' => 'Edit', 'icon' => 'edit', 'url' => '/visits/412/edit', 'visible' => true],
            ['label' => 'Archive', 'icon' => 'locked', 'disabledReason' => 'Only a finished visit'],
            ['label' => 'Delete', 'icon' => 'delete', 'tone' => 'danger', 'attr' => ['data-action' => 'x#delete']],
        ];

        return [
            ['id' => '412', 'label' => 'V-0412', 'url' => '/visits/412', 'tone' => 'danger', 'actions' => $actions, 'cells' => [
                'ref' => 'V-0412', 'customer' => ['html' => '<b>Acme</b>', 'secondary' => ['text' => '12 days late', 'tone' => 'danger']], 'amount' => '1 240 €', 'secret' => 'x',
            ]],
            ['id' => '413', 'label' => 'V-0413', 'selected' => true, 'cells' => ['ref' => 'V-0413', 'customer' => '<i>Delta</i>', 'amount' => '380 €']],
        ];
    }

    /**
     * @param array<string, mixed> $context
     */
    private function table(string $attributes = '', array $context = []): \Dom\Element
    {
        return $this->element(
            '<twig:LleCruditUi:Table id="visits" caption="Visits" :columns="columns" :rows="rows" ' . $attributes . '/>',
            'div',
            $context + ['columns' => $this->columns(), 'rows' => $this->rows()],
        );
    }

    public function testTableWithItsCaptionHeadingsAndSort(): void
    {
        $table = $this->table(':sort="{column: \'ref\', dir: \'asc\'}"');

        self::assertSame('visits', $table->getAttribute('id'));
        self::assertSame('crudit-ui-table', $table->getAttribute('class'));
        self::assertSame(self::TABLE, $table->getAttribute('data-controller'));
        self::assertSame('Visits', $table->querySelector('table > caption.crudit-ui-visually-hidden')?->textContent);

        $headings = $table->querySelectorAll('thead th[scope="col"]');
        // Visit, Customer, Amount and the actions; the hidden column is not rendered.
        self::assertCount(4, $headings);
        self::assertSame('ascending', $headings[0]?->getAttribute('aria-sort'));
        self::assertSame('/visits?sort=ref', $headings[0]->querySelector('a.crudit-ui-table__sort')?->getAttribute('href'));
        self::assertFalse($headings[1]?->hasAttribute('aria-sort'), 'a column without a sort url has no aria-sort');
        self::assertStringContainsString('crudit-ui-table__heading--end', (string) $headings[2]?->getAttribute('class'));
        self::assertStringNotContainsString('Secret', (string) $table->querySelector('thead')?->textContent);
    }

    public function testUnsortedColumnIsNone(): void
    {
        $table = $this->table();

        self::assertSame('none', $table->querySelector('thead th')?->getAttribute('aria-sort'));
    }

    public function testRowIsClickableAndItsMainCellIsALink(): void
    {
        $row = $this->table()->querySelector('tbody tr[data-id="412"]');

        self::assertNotNull($row);
        self::assertSame('/visits/412', $row->getAttribute('data-url'));
        self::assertSame(self::TABLE . '#rowClick', str_replace('click->', '', (string) $row->getAttribute('data-action')));
        self::assertStringContainsString('crudit-ui-table__row--danger', (string) $row->getAttribute('class'));

        $main = $row->querySelector('th[scope="row"].crudit-ui-table__cell--primary');
        self::assertSame('/visits/412', $main?->querySelector('a.crudit-ui-table__link')?->getAttribute('href'));
        self::assertSame('V-0412', $main->textContent);

        // A row without an address is not clickable, and its main cell is not a link.
        $other = $this->table()->querySelector('tbody tr[data-id="413"]');
        self::assertFalse($other?->hasAttribute('data-url'));
        self::assertNull($other->querySelector('th a'));
    }

    public function testCellHtmlIsRenderedAndPlainTextIsEscaped(): void
    {
        $row = $this->table()->querySelector('tbody tr[data-id="412"]');

        self::assertSame('Acme', $row?->querySelector('td b')?->textContent);
        self::assertSame('12 days late', $row->querySelector('.crudit-ui-table__secondary--danger')?->textContent);

        $text = $this->table()->querySelector('tbody tr[data-id="413"] td');
        self::assertNull($text?->querySelector('i'), 'a string cell is text');
        self::assertSame('<i>Delta</i>', $text?->textContent);
    }

    public function testColumnInfoIsADropdownNamedAfterTheColumn(): void
    {
        $info = $this->table()->querySelector('thead .crudit-ui-dropdown');

        self::assertSame('visits-info-customer', $info?->getAttribute('id'));
        self::assertSame('Help on the column Customer', trim((string) $info->querySelector('button .crudit-ui-visually-hidden')?->textContent));
        self::assertSame('Who ordered', $info->querySelector('.crudit-ui-table__info-text')?->textContent);
    }

    public function testActionsAreNamedAfterTheRow(): void
    {
        $actions = $this->table()->querySelector('tbody tr[data-id="412"] td.crudit-ui-table__actions .crudit-ui-table__action-list');

        $edit = $actions?->querySelector('a.crudit-ui-icon-btn');
        self::assertSame('/visits/412/edit', $edit?->getAttribute('href'));
        self::assertSame('Edit V-0412', trim((string) $edit->querySelector('.crudit-ui-visually-hidden')?->textContent));

        $more = $actions->querySelector('.crudit-ui-dropdown > button');
        self::assertSame('More actions for V-0412', trim((string) $more?->querySelector('.crudit-ui-visually-hidden')?->textContent));
        self::assertSame('true', $more?->parentElement?->getAttribute('data-2lenet--crudit-ui-bundle--dropdown-floating-value'));

        $items = $actions->querySelectorAll('.crudit-ui-menu__item');
        self::assertCount(2, $items);
        self::assertSame('true', $items[0]?->getAttribute('aria-disabled'));
        self::assertStringContainsString('Only a finished visit', $items[0]->textContent);
        self::assertSame('x#delete', $items[1]?->getAttribute('data-action'));
        self::assertStringContainsString('crudit-ui-menu__item--danger', (string) $items[1]->getAttribute('class'));
    }

    public function testDisabledVisibleActionKeepsItsFocusAndItsReason(): void
    {
        $rows = [['id' => '1', 'label' => 'V-1', 'cells' => ['ref' => 'V-1'], 'actions' => [
            ['label' => 'Edit', 'icon' => 'edit', 'url' => '/edit', 'visible' => true, 'disabledReason' => 'Locked by Ana'],
        ]]];
        $button = $this->table('', ['rows' => $rows])->querySelector('tbody .crudit-ui-icon-btn');

        self::assertSame('true', $button?->getAttribute('aria-disabled'));
        self::assertFalse($button->hasAttribute('href'), 'a disabled action is not a link');
        $describedBy = (string) $button->getAttribute('aria-describedby');
        self::assertSame('Locked by Ana', $button->ownerDocument?->getElementById($describedBy)?->textContent);
    }

    public function testNoActionColumnWhenNoRowHasAction(): void
    {
        $rows = [['id' => '1', 'label' => 'V-1', 'cells' => ['ref' => 'V-1']]];
        $table = $this->table('', ['rows' => $rows]);

        self::assertNull($table->querySelector('.crudit-ui-table__actions'));
        self::assertCount(3, $table->querySelectorAll('thead th'));
    }

    public function testSelection(): void
    {
        $table = $this->table('selectable');

        $all = $table->querySelector('thead input[type="checkbox"]');
        self::assertSame('visits-select-page', $all?->getAttribute('id'));
        self::assertSame('Select all the rows of the page', $table->querySelector('label[for="visits-select-page"]')?->textContent);
        self::assertSame(self::TABLE . '#togglePage', $all->getAttribute('data-action'));

        $row = $table->querySelector('tbody tr[data-id="413"]');
        $box = $row?->querySelector('input[type="checkbox"]');
        self::assertTrue($box?->hasAttribute('checked'));
        self::assertSame('Select V-0413', $row->querySelector('label[for="' . $box->getAttribute('id') . '"]')?->textContent);
        self::assertStringContainsString('crudit-ui-table__row--selected', (string) $row->getAttribute('class'));
        self::assertFalse($table->querySelector('tbody tr[data-id="412"] input')?->hasAttribute('checked'));
        // The card of the row has its own box, in step with the one of the table (same data-id).
        self::assertSame('413', $table->querySelector('.crudit-ui-table__cards input[type="checkbox"][data-id="413"]')?->getAttribute('data-id'));
    }

    public function testCardsShowTheColumnsMarkedCard(): void
    {
        $card = $this->table()->querySelector('.crudit-ui-table__cards li.crudit-ui-table__card--danger');

        self::assertNotNull($card);
        self::assertSame('V-0412', trim((string) $card->querySelector('.crudit-ui-table__card-title')?->textContent));
        self::assertSame('/visits/412', $card->querySelector('.crudit-ui-table__card-title a')?->getAttribute('href'));
        $labels = array_map(static fn (\Dom\Element $dt): string => (string) $dt->textContent, iterator_to_array($card->querySelectorAll('dl dt')));
        self::assertSame(['Customer'], $labels, 'the main column is the title, Amount is not marked card');
    }

    public function testGroupsSubtotalsAndTotals(): void
    {
        $rows = $this->rows();
        $table = $this->element(
            '<twig:LleCruditUi:Table id="inv" caption="Invoices" :columns="columns" :groups="groups" :totals="totals"/>',
            'div',
            [
                'columns' => $this->columns(),
                'groups' => [
                    ['key' => 'aug', 'label' => 'August', 'count' => '1 visit', 'rows' => [$rows[0]], 'subtotals' => ['amount' => '1 240 €']],
                    ['key' => 'sep', 'label' => 'September', 'children' => [['label' => 'Planned', 'rows' => [$rows[1]]]]],
                ],
                'totals' => [['column' => 'amount', 'label' => 'Amount', 'html' => '<b>1 620 €</b>'], ['label' => 'Average', 'html' => '810 €', 'standalone' => true]],
            ],
        );

        $groups = $table->querySelectorAll('table > tbody.crudit-ui-table__group');
        self::assertCount(2, $groups);
        $toggle = $groups[0]?->querySelector('th[scope="rowgroup"] button[aria-expanded="true"]');
        self::assertSame(self::TABLE . '#toggleGroup', $toggle?->getAttribute('data-action'));
        self::assertStringContainsString('August', (string) $toggle->textContent);
        self::assertStringContainsString('1 visit', (string) $toggle->textContent);
        // Rows, level 2 heading and subtotals fold with their group.
        self::assertCount(2, $groups[1]?->querySelectorAll('[data-group-body]') ?? []);
        self::assertSame('Subtotal August', $groups[0]->querySelector('tr.crudit-ui-table__total--sub th')?->textContent);
        self::assertStringContainsString('1 240 €', (string) $groups[0]->querySelector('tr.crudit-ui-table__total--sub')?->textContent);
        self::assertSame('Planned', trim((string) $groups[1]->querySelector('tr.crudit-ui-table__group-row--level2 th[scope="rowgroup"]')?->textContent));

        $foot = $table->querySelector('table > tfoot tr.crudit-ui-table__total');
        self::assertSame('Total', $foot?->querySelector('th[scope="row"]')?->textContent);
        self::assertSame('1 620 €', $foot->querySelector('td b')?->textContent);
        self::assertSame('Average', $table->querySelector('dl.crudit-ui-table__totals--standalone dt')?->textContent);
        self::assertSame('810 €', $table->querySelector('dl.crudit-ui-table__totals--standalone dd')?->textContent);
    }

    public function testManualOrder(): void
    {
        $table = $this->table(':sortable="{url: \'/sort\'}"');

        self::assertSame(self::TABLE . ' ' . self::SORT, $table->getAttribute('data-controller'));
        self::assertSame('/sort', $table->getAttribute('data-' . self::SORT . '-url-value'));
        self::assertSame('false', $table->getAttribute('data-' . self::SORT . '-disabled-value'));
        self::assertSame('Position %position% of %total%', $table->getAttribute('data-' . self::SORT . '-position-value'));
        self::assertNotNull($table->querySelector('p#visits-sort-help'));
        self::assertNotNull($table->querySelector('[role="status"][data-' . self::SORT . '-target="status"]'));

        $handle = $table->querySelector('tbody tr[data-id="412"] button.crudit-ui-table__handle');
        self::assertSame('visits-sort-help', $handle?->getAttribute('aria-describedby'));
        self::assertSame('Move V-0412, position 1 of 2', trim((string) $handle->querySelector('.crudit-ui-visually-hidden')?->textContent));
        self::assertFalse($handle->hasAttribute('aria-disabled'));

        // Cards: Move up / Move down, the first one cannot go up.
        $buttons = $table->querySelectorAll('.crudit-ui-table__card-order button');
        self::assertCount(4, $buttons);
        self::assertTrue($buttons[0]?->hasAttribute('disabled'));
        self::assertFalse($buttons[1]?->hasAttribute('disabled'));
        self::assertSame(self::SORT . '#down', $buttons[1]->getAttribute('data-action'));
    }

    public function testManualOrderDisabledByAFilter(): void
    {
        $table = $this->table(':sortable="{url: \'/sort\', disabledReason: \'A filter is active.\'}"');

        self::assertSame('true', $table->getAttribute('data-' . self::SORT . '-disabled-value'));
        self::assertSame('true', $table->querySelector('.crudit-ui-table__handle')?->getAttribute('aria-disabled'));
        self::assertStringContainsString('A filter is active.', (string) $table->querySelector('#visits-sort-help')?->textContent);
        self::assertTrue($table->querySelector('.crudit-ui-table__card-order button')?->hasAttribute('disabled'));
    }

    public function testManualOrderInsideGroups(): void
    {
        $rows = $this->rows();
        $table = $this->element(
            '<twig:LleCruditUi:Table id="g" caption="G" :columns="columns" :groups="groups" :sortable="{url: \'/sort\'}"/>',
            'div',
            ['columns' => $this->columns(), 'groups' => [['key' => 'a', 'label' => 'A', 'rows' => [$rows[0]]], ['key' => 'b', 'label' => 'B', 'rows' => [$rows[1]]]]],
        );

        self::assertSame(self::TABLE . ' ' . self::SORT, $table->getAttribute('data-controller'));
        self::assertCount(2, $table->querySelectorAll('tbody.crudit-ui-table__group .crudit-ui-table__handle'));
        // The position is read in the group: one row of one.
        self::assertSame('Move V-0413, position 1 of 1', trim((string) $table->querySelector('tbody[data-group="b"] .crudit-ui-table__handle .crudit-ui-visually-hidden')?->textContent));
        self::assertCount(2, $table->querySelectorAll('.crudit-ui-table__card-list'));
    }

    public function testIdsAreInThePageOnce(): void
    {
        $rows = $this->rows();
        $rows[0]['domId'] = 'list-visit-412';
        $rows[0]['cells']['customer'] = ['html' => 'Acme', 'id' => 'list-visit-412-customer'];
        $rows[0]['cells']['ref'] = ['html' => 'V-0412', 'id' => 'list-visit-412-ref'];
        $table = $this->table('', ['rows' => $rows]);

        // The table carries the ids; the card carries them as data attributes.
        self::assertSame('tr', $table->querySelector('#list-visit-412')?->localName);
        self::assertSame('td', $table->querySelector('#list-visit-412-customer')?->localName);
        self::assertSame('th', $table->querySelector('#list-visit-412-ref')?->localName);
        self::assertSame('li', $table->querySelector('[data-row-id="list-visit-412"]')?->localName);
        self::assertSame('Acme', $table->querySelector('dd[data-cell-id="list-visit-412-customer"]')?->textContent);
        self::assertSame('list-visit-412-ref', $table->querySelector('.crudit-ui-table__card-title')?->getAttribute('data-cell-id'));

        $ids = array_map(static fn (\Dom\Element $element): string => (string) $element->getAttribute('id'), iterator_to_array($table->querySelectorAll('[id]')));
        self::assertSame([], array_keys(array_filter(array_count_values($ids), static fn (int $count): bool => $count > 1)));
    }

    public function testTotalsKeepTheirIds(): void
    {
        $table = $this->element(
            '<twig:LleCruditUi:Table id="t" caption="T" :columns="columns" :rows="rows" :totals="totals"/>',
            'div',
            ['columns' => $this->columns(), 'rows' => $this->rows(), 'totals' => [
                ['column' => 'amount', 'label' => 'Amount', 'html' => '1 620 €', 'id' => 'total-amount'],
                ['label' => 'Average', 'html' => '810 €', 'standalone' => true, 'id' => 'total-average'],
            ]],
        );

        self::assertSame('td', $table->querySelector('tfoot #total-amount')?->localName);
        self::assertSame('1 620 €', $table->querySelector('.crudit-ui-table__cards dd[data-cell-id="total-amount"]')?->textContent);
        self::assertSame('810 €', $table->querySelector('dl.crudit-ui-table__totals--standalone dd#total-average')?->textContent);
    }

    public function testEmptyLoadingAndErrorStates(): void
    {
        $empty = $this->table('', ['rows' => []]);
        self::assertSame('No result', $empty->querySelector('.crudit-ui-table__state h2.crudit-ui-empty-state__title')?->textContent);
        self::assertNull($empty->querySelector('table'));

        $custom = $this->element('<twig:LleCruditUi:Table id="v" caption="V" :columns="columns"><twig:block name="empty"><p>Create a visit</p></twig:block></twig:LleCruditUi:Table>', 'div', ['columns' => $this->columns()]);
        self::assertSame('Create a visit', $custom->querySelector('.crudit-ui-table__state p')?->textContent);

        $loading = $this->table('state="loading"');
        self::assertSame('true', $loading->querySelector('.crudit-ui-table__state')?->getAttribute('aria-busy'));
        self::assertSame('Loading the list…', $loading->querySelector('.crudit-ui-visually-hidden')?->textContent);
        self::assertCount(5, $loading->querySelectorAll('.crudit-ui-skeleton'));

        $error = $this->table('state="error" retryUrl="/visits"');
        self::assertSame('alert', $error->querySelector('.crudit-ui-alert')?->getAttribute('role'));
        self::assertSame('/visits', $error->querySelector('.crudit-ui-alert a')?->getAttribute('href'));
        self::assertNull($this->table('state="error"')->querySelector('.crudit-ui-alert a'));
    }

    public function testBlocksBeforeAndFooter(): void
    {
        $table = $this->element(
            '<twig:LleCruditUi:Table id="v" caption="V" :columns="columns" :rows="rows"><twig:block name="before"><p class="help">Help</p></twig:block><twig:block name="footer"><nav class="pager">Pager</nav></twig:block></twig:LleCruditUi:Table>',
            'div',
            ['columns' => $this->columns(), 'rows' => $this->rows()],
        );

        self::assertSame('p', $table->firstElementChild?->localName);
        self::assertSame('Pager', $table->lastElementChild?->textContent);
    }

    public function testAttributesOfTheProjectAreKept(): void
    {
        $table = $this->table('class="visits" data-test="1"');

        self::assertSame('crudit-ui-table visits', $table->getAttribute('class'));
        self::assertSame('1', $table->getAttribute('data-test'));
    }
}
