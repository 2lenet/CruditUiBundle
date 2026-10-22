<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * LleCruditUi:CommandPalette (docs/components.md).
 */
class CommandPaletteTest extends TestCase
{
    use RendersTwig;

    private const string PALETTE = '2lenet--crudit-ui-bundle--command-palette';
    private const string DIALOG = '2lenet--crudit-ui-bundle--dialog';

    protected function tearDown(): void
    {
        (new Filesystem())->remove(sys_get_temp_dir() . '/lle_crudit_ui_bundle');
    }

    public function testPalette(): void
    {
        $dialog = $this->element(<<<'TWIG'
            <twig:LleCruditUi:CommandPalette id="search" url="/search" title="Global search" placeholder="Client…" help="Clients and screens." class="project"/>
            TWIG, 'dialog');

        // The id is the target of commandfor; the two controllers share the <dialog>.
        self::assertSame('search', $dialog->getAttribute('id'));
        self::assertSame('crudit-ui-palette project', $dialog->getAttribute('class'));
        self::assertSame(self::DIALOG . ' ' . self::PALETTE, $dialog->getAttribute('data-controller'));
        self::assertSame('search-title', $dialog->getAttribute('aria-labelledby'));
        self::assertSame('/search', $dialog->getAttribute('data-' . self::PALETTE . '-url-value'));
        self::assertSame('2', $dialog->getAttribute('data-' . self::PALETTE . '-min-length-value'));
        self::assertSame('true', $dialog->getAttribute('data-' . self::PALETTE . '-shortcut-value'));
        self::assertSame('No result for “%query%”.', $dialog->getAttribute('data-' . self::PALETTE . '-empty-text-value'));

        self::assertSame('Global search', $dialog->querySelector('#search-title')?->textContent);

        // The combobox pattern: a field named by a label, which owns the listbox and is described by the help.
        $input = $dialog->querySelector('input');
        self::assertSame('search-input', $input?->getAttribute('id'));
        self::assertSame('combobox', $input->getAttribute('role'));
        self::assertSame('list', $input->getAttribute('aria-autocomplete'));
        self::assertSame('false', $input->getAttribute('aria-expanded'));
        self::assertSame('search-results', $input->getAttribute('aria-controls'));
        self::assertSame('search-help', $input->getAttribute('aria-describedby'));
        self::assertSame('Client…', $input->getAttribute('placeholder'));
        self::assertSame('off', $input->getAttribute('autocomplete'));
        self::assertSame('Global search', $dialog->querySelector('label[for="search-input"]')?->textContent);

        $results = $dialog->querySelector('#search-results');
        self::assertSame('listbox', $results?->getAttribute('role'));
        self::assertTrue($results->hasAttribute('hidden'));
        self::assertSame('Enter at least 2 characters. Clients and screens.', preg_replace('/\s+/', ' ', trim((string) $dialog->querySelector('#search-help')?->textContent)));

        // The count is announced politely.
        self::assertSame('status', $dialog->querySelector('[data-' . self::PALETTE . '-target="status"]')?->getAttribute('role'));
        // The close button asks the controller dialog.
        self::assertSame(self::DIALOG . '#dismiss', $dialog->querySelector('button.crudit-ui-icon-btn')?->getAttribute('data-action'));
    }

    public function testOptions(): void
    {
        $dialog = $this->element(<<<'TWIG'
            <twig:LleCruditUi:CommandPalette url="/search" title="Search" label="Search a client" :minLength="1" :shortcut="false"/>
            TWIG, 'dialog');

        self::assertStringStartsWith('crudit-ui-palette-', (string) $dialog->getAttribute('id'));
        self::assertSame('1', $dialog->getAttribute('data-' . self::PALETTE . '-min-length-value'));
        self::assertSame('false', $dialog->getAttribute('data-' . self::PALETTE . '-shortcut-value'));
        self::assertSame('Search a client', $dialog->querySelector('label')?->textContent);
        self::assertSame('Enter at least 1 character.', trim((string) $dialog->querySelector('.crudit-ui-palette__help')?->textContent));
    }

    public function testKitAnswersTheSearch(): void
    {
        $kernel = new TestKernel();

        $json = json_decode($this->get($kernel, '/crudit-ui/kit-search?q=mar'), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame(['clients'], array_column($json['groups'], 'id'));
        self::assertSame('Boulangerie Martin', $json['groups'][0]['results'][0]['title']);

        $json = json_decode($this->get($kernel, '/crudit-ui/kit-search?q=v-04'), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame(['visits'], array_column($json['groups'], 'id'));

        self::assertSame(['groups' => []], json_decode($this->get($kernel, '/crudit-ui/kit-search?q=none'), true, flags: JSON_THROW_ON_ERROR));
    }
}
