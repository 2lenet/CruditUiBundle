<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Tests\Functional;

use Lle\CruditUiBundle\LleCruditUiBundle;
use Lle\CruditUiBundle\Twig\ThemeExtension;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\AssetMapper\AssetMapperRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;

class LleCruditUiBundleTest extends TestCase
{
    protected function tearDown(): void
    {
        (new Filesystem())->remove(sys_get_temp_dir() . '/lle_crudit_ui_bundle');
    }

    public function testDefaultConfiguration(): void
    {
        $kernel = new TestKernel();
        $kernel->boot();
        $container = $kernel->getContainer();

        self::assertSame('fontawesome', $container->getParameter('lle_crudit_ui.icons.library'));
        self::assertSame([], $container->getParameter('lle_crudit_ui.icons.map'));
        self::assertSame(['ar', 'fa', 'he', 'ur'], $container->getParameter('lle_crudit_ui.rtl_languages'));
    }

    public function testIconConfiguration(): void
    {
        $kernel = new TestKernel([
            'icons' => [
                'library' => 'tabler',
                'map' => ['delete' => 'tabler:trash-x'],
            ],
        ]);
        $kernel->boot();
        $container = $kernel->getContainer();

        self::assertSame('tabler', $container->getParameter('lle_crudit_ui.icons.library'));
        self::assertSame(['delete' => 'tabler:trash-x'], $container->getParameter('lle_crudit_ui.icons.map'));
    }

    public function testAssetsAreExposedToAssetMapper(): void
    {
        $kernel = new TestKernel();
        $kernel->boot();

        /** @var ContainerInterface $testContainer */
        $testContainer = $kernel->getContainer()->get('test.service_container');
        /** @var AssetMapperRepository $repository */
        $repository = $testContainer->get('asset_mapper.repository');
        $paths = $repository->allDirectories();

        self::assertSame(LleCruditUiBundle::ASSETS_PACKAGE, $paths[\dirname(__DIR__, 3) . '/assets/dist'] ?? null);
    }

    public function testIconFunction(): void
    {
        self::assertSame(
            '<svg viewBox="0 0 16 16" fill="currentColor" class="crudit-ui-icon" aria-hidden="true"><path d="M1 1h14v14H1z"></path></svg>',
            $this->render(new TestKernel(), "{{ ui_icon('delete') }}"),
        );
    }

    public function testLibraryAndMap(): void
    {
        $kernel = new TestKernel(['icons' => ['library' => 'lucide', 'map' => ['edit' => 'fa7-solid:pen']]]);

        self::assertSame(
            '<svg viewBox="0 0 24 24" fill="currentColor" class="crudit-ui-icon" aria-hidden="true"><path d="M3 6h18"></path></svg>'
            . '<svg viewBox="0 0 16 16" fill="currentColor" class="crudit-ui-icon" aria-hidden="true"><path d="M2 2h12v12H2z"></path></svg>',
            $this->render($kernel, "{{ ui_icon('delete') }}{{ ui_icon('edit') }}"),
        );
    }

    public function testUnknownIconDoesNotBreakThePage(): void
    {
        self::assertSame('before||after', $this->render(new TestKernel(), "before|{{ ui_icon('unknown') }}|after"));
    }

    public function testThemeFunction(): void
    {
        $kernel = new TestKernel();
        $kernel->boot();

        /** @var ContainerInterface $testContainer */
        $testContainer = $kernel->getContainer()->get('test.service_container');
        /** @var RequestStack $requestStack */
        $requestStack = $testContainer->get('request_stack');
        $requestStack->push(new Request(cookies: [ThemeExtension::COOKIE => 'dark']));

        self::assertSame('<html data-theme="dark">', $this->render($kernel, '<html{% if ui_theme() %} data-theme="{{ ui_theme() }}"{% endif %}>'));
    }

    public function testLangAndDirFunctions(): void
    {
        $kernel = new TestKernel();
        $kernel->boot();

        /** @var ContainerInterface $testContainer */
        $testContainer = $kernel->getContainer()->get('test.service_container');
        /** @var RequestStack $requestStack */
        $requestStack = $testContainer->get('request_stack');
        $request = new Request();
        $request->setLocale('ar_MA');
        $requestStack->push($request);

        self::assertSame('<html lang="ar-MA" dir="rtl">', $this->render($kernel, '<html lang="{{ ui_lang() }}" dir="{{ ui_dir() }}">'));
    }

    #[RequiresPhpExtension('intl')]
    public function testFormatFilters(): void
    {
        $kernel = new TestKernel();
        $kernel->boot();

        /** @var ContainerInterface $testContainer */
        $testContainer = $kernel->getContainer()->get('test.service_container');
        /** @var RequestStack $requestStack */
        $requestStack = $testContainer->get('request_stack');
        $request = new Request();
        $request->setLocale('ar_SA');
        $requestStack->push($request);

        self::assertSame('1,234.5', $this->render($kernel, '{{ 1234.5|ui_number }}'));
    }

    public function testTranslationsOfTheBundle(): void
    {
        self::assertSame(
            'Sombre|Dunkel',
            $this->render(new TestKernel(), "{{ 'crudit_ui.theme.dark'|trans(domain: 'LleCruditUiBundle', locale: 'fr') }}|{{ 'crudit_ui.theme.dark'|trans(domain: 'LleCruditUiBundle', locale: 'de') }}"),
        );
    }

    public function testRtlLanguageWithRegionIsRefused(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        (new TestKernel(['rtl_languages' => ['ar_MA']]))->boot();
    }

    public function testUnknownLibraryIsRefused(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        (new TestKernel(['icons' => ['library' => 'fontawesome4']]))->boot();
    }

    private function render(TestKernel $kernel, string $template): string
    {
        $kernel->boot();

        /** @var ContainerInterface $testContainer */
        $testContainer = $kernel->getContainer()->get('test.service_container');
        /** @var Environment $twig */
        $twig = $testContainer->get('twig');

        return $twig->createTemplate($template)->render();
    }
}
