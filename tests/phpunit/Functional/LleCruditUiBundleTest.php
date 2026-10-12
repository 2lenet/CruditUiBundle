<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Tests\Functional;

use Lle\CruditUiBundle\LleCruditUiBundle;
use PHPUnit\Framework\TestCase;
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

        self::assertTrue($container->getParameter('lle_crudit_ui.icons.load_fontawesome'));
        self::assertSame([], $container->getParameter('lle_crudit_ui.icons.map'));
    }

    public function testIconConfiguration(): void
    {
        $kernel = new TestKernel([
            'icons' => [
                'load_fontawesome' => false,
                'map' => ['fa-solid fa-trash' => 'xx xx-trash'],
            ],
        ]);
        $kernel->boot();
        $container = $kernel->getContainer();

        self::assertFalse($container->getParameter('lle_crudit_ui.icons.load_fontawesome'));
        self::assertSame(['fa-solid fa-trash' => 'xx xx-trash'], $container->getParameter('lle_crudit_ui.icons.map'));
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
            '<i class="xx xx-trash" aria-hidden="true"></i><i class="fa-solid fa-pen" aria-hidden="true"></i>',
            $this->render(
                new TestKernel(['icons' => ['map' => ['fa-solid fa-trash' => 'xx xx-trash']]]),
                "{{ ui_icon('delete') }}{{ ui_icon('edit') }}",
            ),
        );
    }

    public function testLoadFontawesomeGlobal(): void
    {
        self::assertSame('1', $this->render(new TestKernel(), '{{ lle_crudit_ui_load_fontawesome }}'));
        self::assertSame(
            'no',
            $this->render(
                new TestKernel(['icons' => ['load_fontawesome' => false]]),
                '{{ lle_crudit_ui_load_fontawesome ? "yes" : "no" }}',
            ),
        );
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
