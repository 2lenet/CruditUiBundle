<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * The conventions of the Twig components (docs/components.md) and the design kit page.
 */
class ComponentsTest extends TestCase
{
    use RendersTwig;

    protected function tearDown(): void
    {
        (new Filesystem())->remove(sys_get_temp_dir() . '/lle_crudit_ui_bundle');
    }

    public function testAnonymousComponentOfTheBundle(): void
    {
        $html = $this->html($this->render(new TestKernel(), '<twig:LleCruditUi:Kit:Section id="colors" title="Colors"><p>Swatches</p></twig:LleCruditUi:Kit:Section>'));

        $section = $html->querySelector('section#kit-colors.crudit-ui-kit__section');
        self::assertNotNull($section);
        self::assertSame('kit-colors-title', $section->getAttribute('aria-labelledby'));
        self::assertSame('Colors', $html->querySelector('h2#kit-colors-title')?->textContent);
        self::assertSame('Swatches', $section->querySelector('p')?->textContent);
    }

    public function testProjectOverridesAComponentOfTheBundle(): void
    {
        $html = $this->render(new TestKernel([], __DIR__ . '/override-templates'), '<twig:LleCruditUi:Kit:Section id="colors" title="Colors">Swatches</twig:LleCruditUi:Kit:Section>');

        self::assertSame('<div class="project-section" data-id="colors">Colors: Swatches</div>', trim($html));
    }

    public function testDesignKitPage(): void
    {
        $html = $this->html($this->get(new TestKernel(), '/crudit-ui/kit'));

        self::assertSame('ltr', $html->documentElement?->getAttribute('dir'));
        self::assertSame('Design kit', $html->querySelector('main.crudit-ui-kit h1')?->textContent);
        $links = array_map(static fn (\Dom\Element $link): ?string => $link->getAttribute('href'), iterator_to_array($html->querySelectorAll('nav[aria-label] a')));
        self::assertSame(['#kit-colors', '#kit-typography', '#kit-buttons', '#kit-status', '#kit-states'], $links);
        self::assertNotNull($html->querySelector('#kit-colors code'));
        self::assertNotNull($html->querySelector('#kit-typography .crudit-ui-kit__space-bar'));
        self::assertCount(35, $html->querySelectorAll('#kit-buttons table .crudit-ui-btn'));
    }

    public function testDesignKitPageInARightToLeftLanguage(): void
    {
        $html = $this->html($this->get(new TestKernel(), '/crudit-ui/kit/ar'));

        self::assertSame('ar', $html->documentElement?->getAttribute('lang'));
        self::assertSame('rtl', $html->documentElement->getAttribute('dir'));
    }
}
