<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Tests\Functional;

use Lle\CruditUiBundle\Format\Formatter;
use Lle\CruditUiBundle\LleCruditUiBundle;
use Lle\CruditUiBundle\Twig\ThemeExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\AssetMapper\AssetMapperRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\Icons\IconFinderInterface;
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

        // the service used outside the templates gives the same result as the filters
        /** @var Formatter $formatter */
        $formatter = $testContainer->get(Formatter::class);
        self::assertSame(
            $formatter->datetime('2026-10-13 14:05', 'short', 'short'),
            $this->render($kernel, "{{ '2026-10-13 14:05'|ui_datetime('short', 'short') }}"),
        );
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

    public function testIconsOfTheBundleAreFoundForTheLock(): void
    {
        $kernel = new TestKernel(['icons' => ['library' => 'lucide', 'map' => ['delete' => 'tabler:trash-x']]]);
        $kernel->boot();

        /** @var IconFinderInterface $finder */
        $finder = $kernel->getContainer()->get('.ux_icons.icon_finder');
        $icons = [...$finder->icons()];

        self::assertContains('lucide:pen', $icons);
        self::assertContains('tabler:trash-x', $icons);
        self::assertNotContains('lucide:trash', $icons);
    }

    /**
     * @return iterable<string, array{array<string, string>, string}>
     */
    public static function invalidMaps(): iterable
    {
        yield 'class of the former icon font' => [['fa-solid fa-trash' => 'tabler:trash-x'], 'Unknown icon(s) "fa-solid fa-trash"'];
        yield 'typo in a logical name' => [['delet' => 'tabler:trash-x'], 'Unknown icon(s) "delet"'];
        yield 'replacement that is not an icon name' => [['delete' => 'fa fa-trash'], 'is not an icon name'];
        yield 'logical name used as an Iconify name' => [['trash' => 'tabler:trash-x'], 'Unknown icon(s) "trash"'];
    }

    /**
     * @param array<string, string> $map
     */
    #[DataProvider('invalidMaps')]
    public function testInvalidMapIsRefused(array $map, string $message): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage($message);

        (new TestKernel(['icons' => ['map' => $map]]))->boot();
    }

    public function testLocalIconsAreAcceptedInTheMap(): void
    {
        $kernel = new TestKernel(['icons' => ['map' => ['delete' => 'logo', 'edit' => 'flags:fr', 'lucide:truck' => 'logo']]]);
        $kernel->boot();

        self::assertSame(['delete' => 'logo', 'edit' => 'flags:fr', 'lucide:truck' => 'logo'], $kernel->getContainer()->getParameter('lle_crudit_ui.icons.map'));
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
