<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Tests\Functional;

use Lle\CruditUiBundle\LleCruditUiBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\UX\Icons\UXIconsBundle;
use Symfony\UX\StimulusBundle\StimulusBundle;
use Symfony\UX\TwigComponent\TwigComponentBundle;

/**
 * Minimal application that boots the bundle, with or without a project configuration.
 */
class TestKernel extends Kernel
{
    use MicroKernelTrait;

    /**
     * @param array<string, mixed> $uiConfig lle_crudit_ui configuration of the project
     */
    public function __construct(private readonly array $uiConfig = [])
    {
        parent::__construct('test', true);
    }

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new TwigBundle();
        yield new TwigComponentBundle();
        yield new StimulusBundle();
        yield new UXIconsBundle();
        yield new LleCruditUiBundle();
    }

    public function getProjectDir(): string
    {
        return \dirname(__DIR__, 3);
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . '/lle_crudit_ui_bundle/' . md5(serialize($this->uiConfig)) . '/cache';
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir() . '/lle_crudit_ui_bundle/logs';
    }

    /**
     * The finder of UX Icons, private and removed when nothing uses it (no Iconify command here), is kept for the
     * tests of the lock.
     */
    protected function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new class implements CompilerPassInterface {
            public function process(ContainerBuilder $container): void
            {
                $container->getDefinition('.ux_icons.icon_finder')->setPublic(true);
            }
        }, PassConfig::TYPE_BEFORE_REMOVING);
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'test' => true,
            'secret' => 'test',
            'http_method_override' => false,
            'asset_mapper' => ['enabled' => true],
            'translator' => ['enabled' => true],
        ]);
        $container->extension('twig_component', ['defaults' => [], 'anonymous_template_directory' => 'components/']);
        // Icons of the tests, without calling Iconify
        $container->extension('ux_icons', [
            'icon_dir' => __DIR__ . '/icons',
            'iconify' => ['on_demand' => false],
        ]);
        $container->extension('lle_crudit_ui', $this->uiConfig);
    }
}
