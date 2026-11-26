<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Error\RuntimeError;
use Twig\Markup;

/**
 * Collapsibles and tabs: Disclosure, Tabs.
 */
class NavigationTest extends TestCase
{
    use RendersTwig;

    private const TABS = <<<'TWIG'
        <twig:LleCruditUi:Tabs id="visit" label="Visit" :tabs="tabs" :selected="selected ?? null" :urlParam="urlParam ?? null">
            <twig:block name="general">General content</twig:block>
            <twig:block name="parts">Parts content</twig:block>
            <twig:block name="history">History content</twig:block>
        </twig:LleCruditUi:Tabs>
        TWIG;

    private const TABS_WITHOUT_BLOCKS = <<<'TWIG'
        <twig:LleCruditUi:Tabs id="visit" label="Visit" :tabs="tabs" :urlParam="urlParam ?? null"/>
        TWIG;

    // The pattern of docs/components.md: the panels captured in a loop; and |raw straight in the hash (escaped: raw is
    // safe only when printed at once; include() in the hash is too before Twig 3.28, a Markup since)
    private const TABS_FROM_A_LOOP = <<<'TWIG'
        {%- set tabs = [] -%}
        {%- for section in sections -%}
            {%- set content -%}{{ include('_tab_section.html.twig', {section: section}) }}{%- endset -%}
            {%- set tabs = tabs|merge([{id: section.code, label: section.label, count: section.items|length, content: content}]) -%}
        {%- endfor -%}
        {%- set tabs = tabs|merge([{id: 'raw', label: 'Raw', content: '<p class="section">Raw</p>'|raw}]) -%}
        <twig:LleCruditUi:Tabs id="visit" label="Visit" :tabs="tabs"/>
        TWIG;

    // Tabs with content and tabs with a block in the same list
    private const TABS_MIXED = <<<'TWIG'
        <twig:LleCruditUi:Tabs id="visit" label="Visit" :tabs="tabs">
            <twig:block name="general">General content</twig:block>
        </twig:LleCruditUi:Tabs>
        TWIG;

    protected function tearDown(): void
    {
        (new Filesystem())->remove(sys_get_temp_dir() . '/lle_crudit_ui_bundle');
    }

    public function testClosedDisclosure(): void
    {
        $disclosure = $this->element('<twig:LleCruditUi:Disclosure id="address" title="Address"><p>12 rue des Lilas</p></twig:LleCruditUi:Disclosure>', 'div');

        self::assertSame('address', $disclosure->getAttribute('id'));
        self::assertSame('crudit-ui-disclosure', $disclosure->getAttribute('class'));
        self::assertSame('2lenet--crudit-ui-bundle--disclosure', $disclosure->getAttribute('data-controller'));
        $toggle = $disclosure->querySelector('button.crudit-ui-disclosure__toggle');
        self::assertSame($disclosure, $toggle?->parentElement);
        self::assertSame('button', $toggle->getAttribute('type'));
        self::assertSame('false', $toggle->getAttribute('aria-expanded'));
        self::assertSame('address-panel', $toggle->getAttribute('aria-controls'));
        self::assertSame('Address', $toggle->querySelector('.crudit-ui-disclosure__title')?->textContent);
        self::assertSame('true', $toggle->querySelector('svg.crudit-ui-disclosure__chevron')?->getAttribute('aria-hidden'));
        $panel = $disclosure->querySelector('#address-panel');
        self::assertSame('crudit-ui-disclosure__panel crudit-ui-disclosure__panel--closed', $panel?->getAttribute('class'));
        self::assertSame('12 rue des Lilas', $panel->querySelector('p')?->textContent);
    }

    public function testOpenDisclosure(): void
    {
        $disclosure = $this->element('<twig:LleCruditUi:Disclosure id="address" title="Address" open>…</twig:LleCruditUi:Disclosure>', 'div');

        self::assertSame('true', $disclosure->querySelector('button')?->getAttribute('aria-expanded'));
        self::assertSame('crudit-ui-disclosure__panel', $disclosure->querySelector('#address-panel')?->getAttribute('class'));
    }

    public function testDisclosuresWithoutIdHaveTheirOwnIds(): void
    {
        $html = $this->html($this->render(new TestKernel(), '<twig:LleCruditUi:Disclosure title="A">a</twig:LleCruditUi:Disclosure><twig:LleCruditUi:Disclosure title="B">b</twig:LleCruditUi:Disclosure>'));

        $controls = array_map(static fn (\Dom\Element $toggle): ?string => $toggle->getAttribute('aria-controls'), iterator_to_array($html->querySelectorAll('button')));
        self::assertCount(2, array_unique($controls));
        foreach ($controls as $control) {
            self::assertNotNull($html->getElementById((string) $control));
        }
        self::assertNull($html->querySelector('.crudit-ui-disclosure[id]'));
    }

    public function testDisclosureButtonInAHeadingOfItsLevel(): void
    {
        $disclosure = $this->element('<twig:LleCruditUi:Disclosure title="Address" :level="3">…</twig:LleCruditUi:Disclosure>', 'div');
        $heading = $disclosure->querySelector('h3.crudit-ui-disclosure__heading');

        self::assertSame($disclosure, $heading?->parentElement);
        self::assertSame('BUTTON', $heading->firstElementChild?->tagName);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function levelsWithoutHeading(): iterable
    {
        yield 'null' => [null];
        yield '1' => [1];
        yield '7' => [7];
    }

    #[DataProvider('levelsWithoutHeading')]
    public function testDisclosureWithoutHeading(mixed $level): void
    {
        $disclosure = $this->element('<twig:LleCruditUi:Disclosure title="Planning" :level="level">…</twig:LleCruditUi:Disclosure>', 'div', ['level' => $level]);

        self::assertSame($disclosure, $disclosure->querySelector('button')?->parentElement);
        self::assertNull($disclosure->querySelector('h1, h2, h3, h4, h5, h6, h7'));
    }

    public function testDisclosureTitleEscapedOnce(): void
    {
        $disclosure = $this->element('<twig:LleCruditUi:Disclosure :title="title">…</twig:LleCruditUi:Disclosure>', 'div', ['title' => "Tom's <b>& Co</b>"]);

        self::assertSame("Tom's <b>& Co</b>", $disclosure->querySelector('.crudit-ui-disclosure__title')?->textContent);
    }

    public function testDisclosureMergesTheAttributesOfTheProject(): void
    {
        $html = $this->render(new TestKernel(), '<twig:LleCruditUi:Disclosure title="A" class="menu" data-turbo="false">…</twig:LleCruditUi:Disclosure>');
        $disclosure = $this->html($html)->querySelector('body > div');

        self::assertSame('crudit-ui-disclosure menu', $disclosure?->getAttribute('class'));
        self::assertSame('false', $disclosure->getAttribute('data-turbo'));
        self::assertSame(1, preg_match_all('/class=/', (string) strstr($html, '<button', true)));
    }

    public function testEachTabPanelHasTheBlockNamedByItsId(): void
    {
        $tabs = $this->tabs();

        self::assertSame('General content', $tabs->querySelector('#visit-panel-general')?->textContent);
        self::assertSame('Parts content', $tabs->querySelector('#visit-panel-parts')?->textContent);
        self::assertSame('History content', $tabs->querySelector('#visit-panel-history')?->textContent);
    }

    public function testTabsFollowTheAriaPattern(): void
    {
        $tabs = $this->tabs(['selected' => 'parts']);

        self::assertSame('visit', $tabs->getAttribute('id'));
        self::assertSame('2lenet--crudit-ui-bundle--tabs', $tabs->getAttribute('data-controller'));
        self::assertSame('Visit', $tabs->querySelector('[role="tablist"]')?->getAttribute('aria-label'));
        foreach (['general', 'parts', 'history'] as $id) {
            $tab = $tabs->querySelector('#visit-tab-' . $id);
            $panel = $tabs->querySelector('#visit-panel-' . $id);
            self::assertSame('tab', $tab?->getAttribute('role'));
            self::assertSame('button', $tab->getAttribute('type'));
            self::assertSame('visit-panel-' . $id, $tab->getAttribute('aria-controls'));
            self::assertSame('tabpanel', $panel?->getAttribute('role'));
            self::assertSame('visit-tab-' . $id, $panel->getAttribute('aria-labelledby'));
            self::assertSame('0', $panel->getAttribute('tabindex'));
        }
        self::assertSame(['visit-tab-parts'], $this->ids($tabs->querySelectorAll('[role="tab"][aria-selected="true"]')));
        self::assertSame(['visit-tab-parts'], $this->ids($tabs->querySelectorAll('[role="tab"][tabindex="0"]')));
        self::assertSame(['visit-tab-general', 'visit-tab-history'], $this->ids($tabs->querySelectorAll('[role="tab"][tabindex="-1"]')));
        self::assertSame(['visit-panel-parts'], $this->ids($tabs->querySelectorAll('[role="tabpanel"]:not([hidden])')));
    }

    /**
     * @return iterable<string, array{?string}>
     */
    public static function selectionsOfTheFirstTab(): iterable
    {
        yield 'none' => [null];
        yield 'unknown' => ['invoices'];
    }

    #[DataProvider('selectionsOfTheFirstTab')]
    public function testFirstTabByDefault(?string $selected): void
    {
        $tabs = $this->tabs(['selected' => $selected]);

        self::assertSame('true', $tabs->querySelector('#visit-tab-general')?->getAttribute('aria-selected'));
        self::assertSame(['visit-panel-general'], $this->ids($tabs->querySelectorAll('[role="tabpanel"]:not([hidden])')));
    }

    public function testTabWithoutItsBlockFails(): void
    {
        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('Block "invoices"');

        $this->tabs(['tabs' => [['id' => 'general', 'label' => 'General'], ['id' => 'invoices', 'label' => 'Invoices']]]);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function idsThatAreNotTheNameOfABlock(): iterable
    {
        yield 'another case' => ['Parts'];
        // A Twig block cannot start with a digit: no block can have this name.
        yield 'starting with a digit' => ['1_general'];
    }

    #[DataProvider('idsThatAreNotTheNameOfABlock')]
    public function testTabIdThatIsNotTheNameOfItsBlockFails(string $id): void
    {
        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage(sprintf('Block "%s"', $id));

        $this->tabs(['tabs' => [['id' => $id, 'label' => 'Parts']]]);
    }

    public function testTabsWithTheirContentAsValues(): void
    {
        // Tabs known only when the page is rendered: no block, the panel given as a value; the id is free.
        $tabs = $this->element(self::TABS_WITHOUT_BLOCKS, 'div', $this->context(['tabs' => [
            ['id' => 'notes-1', 'label' => 'Notes', 'content' => new Markup('<p>Rendered by <b>Twig</b></p>', 'UTF-8')],
            ['id' => '2024', 'label' => 'Year', 'content' => 'A <b>string</b>, escaped', 'count' => 2],
        ]]));

        self::assertSame('Rendered by Twig', $tabs->querySelector('#visit-panel-notes-1 p')?->textContent);
        self::assertNotNull($tabs->querySelector('#visit-panel-notes-1 p b'));
        self::assertSame('A <b>string</b>, escaped', $tabs->querySelector('#visit-panel-2024')?->textContent);
        self::assertSame('Year 2', $tabs->querySelector('#visit-tab-2024')?->textContent);
        self::assertSame(['visit-panel-notes-1'], $this->ids($tabs->querySelectorAll('[role="tabpanel"]:not([hidden])')));
    }

    public function testTheTabsOfTheDocumentationRenderedFromALoop(): void
    {
        $tabs = $this->element(self::TABS_FROM_A_LOOP, 'div', ['sections' => [
            ['code' => 'parts', 'label' => 'Parts', 'items' => [1, 2]],
            ['code' => 'notes', 'label' => 'Notes', 'items' => []],
        ]]);

        // Captured: HTML
        self::assertSame('Parts 2', $tabs->querySelector('#visit-panel-parts p.section')?->textContent);
        self::assertNotNull($tabs->querySelector('#visit-panel-notes p.section b'));
        self::assertSame('Parts 2', $tabs->querySelector('#visit-tab-parts')?->textContent);
        // |raw straight in the hash: a string, escaped, shown as text
        self::assertNull($tabs->querySelector('#visit-panel-raw p'));
        self::assertStringContainsString('<p class="section">', (string) $tabs->querySelector('#visit-panel-raw')?->textContent);
    }

    public function testTabsWithAndWithoutContentInTheSameList(): void
    {
        $tabs = $this->element(self::TABS_MIXED, 'div', ['tabs' => [
            ['id' => 'general', 'label' => 'General'],
            ['id' => 'notes-1', 'label' => 'Notes', 'content' => 'Notes content'],
            // A section that gave nothing: an empty panel, not the error of a missing block
            ['id' => 'empty-1', 'label' => 'Empty', 'content' => null],
        ]]);

        self::assertSame('General content', $tabs->querySelector('#visit-panel-general')?->textContent);
        self::assertSame('Notes content', $tabs->querySelector('#visit-panel-notes-1')?->textContent);
        self::assertSame('', $tabs->querySelector('#visit-panel-empty-1')?->textContent);
    }

    public function testAnIdThatIsANumberChosenByTheAddress(): void
    {
        // A code from the database (an integer); the address gives a string
        $tabs = $this->tabsOfRequest('/visit?tab=2024', ['urlParam' => 'tab', 'tabs' => [
            ['id' => 2023, 'label' => '2023', 'content' => 'Year 2023'],
            ['id' => 2024, 'label' => '2024', 'content' => 'Year 2024'],
        ]], self::TABS_WITHOUT_BLOCKS);

        self::assertSame('true', $tabs->querySelector('#visit-tab-2024')?->getAttribute('aria-selected'));
        self::assertSame(['visit-panel-2024'], $this->ids($tabs->querySelectorAll('[role="tabpanel"]:not([hidden])')));
    }

    public function testCountOfATab(): void
    {
        $tabs = $this->tabs(['tabs' => [
            ['id' => 'general', 'label' => 'General'],
            ['id' => 'parts', 'label' => 'Parts', 'count' => 3],
            ['id' => 'history', 'label' => 'History', 'count' => 0],
        ]]);

        self::assertNull($tabs->querySelector('#visit-tab-general .crudit-ui-tabs__count'));
        // Read as it is, after a space: "Parts 3".
        self::assertSame('Parts 3', $tabs->querySelector('#visit-tab-parts')?->textContent);
        self::assertSame('0', $tabs->querySelector('#visit-tab-history .crudit-ui-tabs__count')?->textContent);
    }

    public function testTabLabelsEscapedOnce(): void
    {
        $tabs = $this->tabs(['tabs' => [['id' => 'general', 'label' => "Tom's <b>& Co</b>"]]]);

        self::assertSame("Tom's <b>& Co</b>", $tabs->querySelector('#visit-tab-general')?->textContent);
    }

    public function testUrlParamGivenToTheController(): void
    {
        self::assertSame('tab', $this->tabs(['urlParam' => 'tab'])->getAttribute('data-2lenet--crudit-ui-bundle--tabs-url-param-value'));
        self::assertFalse($this->tabs()->hasAttribute('data-2lenet--crudit-ui-bundle--tabs-url-param-value'));
    }

    public function testTabOfTheAddressWinsOverSelected(): void
    {
        self::assertSame('true', $this->tabsOfRequest('/visit?tab=history', ['urlParam' => 'tab', 'selected' => 'parts'])->querySelector('#visit-tab-history')?->getAttribute('aria-selected'));
        // An unknown tab in the address: selected.
        self::assertSame('true', $this->tabsOfRequest('/visit?tab=invoices', ['urlParam' => 'tab', 'selected' => 'parts'])->querySelector('#visit-tab-parts')?->getAttribute('aria-selected'));
        // An array typed in the address (?tab[]=history): selected, not an error.
        self::assertSame('true', $this->tabsOfRequest('/visit?tab[]=history', ['urlParam' => 'tab', 'selected' => 'parts'])->querySelector('#visit-tab-parts')?->getAttribute('aria-selected'));
        // Without urlParam, the address is not read.
        self::assertSame('true', $this->tabsOfRequest('/visit?tab=history', ['selected' => 'parts'])->querySelector('#visit-tab-parts')?->getAttribute('aria-selected'));
    }

    /**
     * @param array<string, mixed> $context
     */
    private function tabs(array $context = []): \Dom\Element
    {
        return $this->element(self::TABS, 'div', $this->context($context));
    }

    /**
     * Tabs rendered during a request of the path.
     *
     * @param array<string, mixed> $context
     */
    private function tabsOfRequest(string $path, array $context, string $template = self::TABS): \Dom\Element
    {
        $kernel = new TestKernel();
        $kernel->boot();
        /** @var ContainerInterface $testContainer */
        $testContainer = $kernel->getContainer()->get('test.service_container');
        /** @var RequestStack $requests */
        $requests = $testContainer->get('request_stack');
        $requests->push(Request::create($path));

        $tabs = $this->html($this->render($kernel, $template, $this->context($context)))->querySelector('body > div');
        self::assertNotNull($tabs);

        return $tabs;
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    private function context(array $context): array
    {
        return $context + ['tabs' => [
            ['id' => 'general', 'label' => 'General'],
            ['id' => 'parts', 'label' => 'Parts', 'count' => 3],
            ['id' => 'history', 'label' => 'History'],
        ]];
    }

    /**
     * @param \Dom\NodeList<\Dom\Element> $elements
     *
     * @return list<?string>
     */
    private function ids(\Dom\NodeList $elements): array
    {
        return array_values(array_map(static fn (\Dom\Element $element): ?string => $element->getAttribute('id'), iterator_to_array($elements)));
    }
}
