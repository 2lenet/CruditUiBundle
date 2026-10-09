<?php

declare(strict_types=1);

namespace Lle\UiBundle\Tests\Functional;

use Lle\UiBundle\LleUiBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\AssetMapper\AssetMapperRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

class LleUiBundleTest extends TestCase
{
    protected function tearDown(): void
    {
        (new Filesystem())->remove(sys_get_temp_dir() . '/lle_ui_bundle');
    }

    public function testDefaultConfiguration(): void
    {
        $kernel = new TestKernel();
        $kernel->boot();
        $container = $kernel->getContainer();

        self::assertTrue($container->getParameter('lle_ui.icons.load_fontawesome'));
        self::assertSame([], $container->getParameter('lle_ui.icons.map'));
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

        self::assertFalse($container->getParameter('lle_ui.icons.load_fontawesome'));
        self::assertSame(['fa-solid fa-trash' => 'xx xx-trash'], $container->getParameter('lle_ui.icons.map'));
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

        self::assertSame(LleUiBundle::ASSETS_PACKAGE, $paths[\dirname(__DIR__, 3) . '/assets/dist'] ?? null);
    }
}
