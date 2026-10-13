<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Tests\Functional;

use Lle\CruditUiBundle\LleCruditUiBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\AssetMapper\AssetMapperRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
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
