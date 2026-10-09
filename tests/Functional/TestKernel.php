<?php

declare(strict_types=1);

namespace Lle\UiBundle\Tests\Functional;

use Lle\UiBundle\LleUiBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\UX\StimulusBundle\StimulusBundle;
use Symfony\UX\TwigComponent\TwigComponentBundle;

/**
 * Minimal application that boots the bundle, with or without a project configuration.
 */
class TestKernel extends Kernel
{
    use MicroKernelTrait;

    /**
     * @param array<string, mixed> $uiConfig lle_ui configuration of the project
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
        yield new LleUiBundle();
    }

    public function getProjectDir(): string
    {
        return \dirname(__DIR__, 2);
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . '/lle_ui_bundle/' . md5(serialize($this->uiConfig)) . '/cache';
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir() . '/lle_ui_bundle/logs';
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'test' => true,
            'secret' => 'test',
            'http_method_override' => false,
            'asset_mapper' => ['enabled' => true],
        ]);
        $container->extension('twig_component', ['anonymous_template_directory' => 'components/']);
        $container->extension('lle_ui', $this->uiConfig);
    }
}
