<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle;

use Lle\CruditUiBundle\Icon\IconLibrary;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * 2LE design system: tokens, base CSS, Twig components and Stimulus controllers.
 *
 * The bundle knows nothing about Crudit (CrudConfig, bricks, menu): it is used by
 * CruditBundle and DashboardBundle, which do not depend on each other.
 */
class LleCruditUiBundle extends AbstractBundle
{
    /** Name of the assets package (assets/package.json), also the AssetMapper namespace. */
    public const string ASSETS_PACKAGE = '@2lenet/crudit-ui-bundle';

    protected string $extensionAlias = 'lle_crudit_ui';

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->arrayNode('icons')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->enumNode('library')
                            ->info('Icon library of the project: the bundle builds the Iconify name of each icon from it.')
                            ->values(array_column(IconLibrary::cases(), 'value'))
                            ->defaultValue(IconLibrary::FontAwesome->value)
                        ->end()
                        ->arrayNode('map')
                            ->info('Icon replacements, keyed by logical name or Iconify name, for example "delete": "tabler:trash-x".')
                            ->normalizeKeys(false)
                            ->useAttributeAsKey('icon')
                            ->scalarPrototype()->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('rtl_languages')
                    ->info('Languages written from right to left, without region: "ar" also covers "ar_MA".')
                    ->scalarPrototype()
                        ->validate()
                            ->ifTrue(static fn (mixed $language): bool => !\is_string($language) || 1 !== preg_match('/^[a-z]{2,3}$/', $language))
                            ->thenInvalid('%s is not a language code without region, such as "ar".')
                        ->end()
                    ->end()
                    ->defaultValue(['ar', 'fa', 'he', 'ur'])
                ->end()
            ->end();
    }

    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        /** @var array{library: string, map: array<string, string>} $icons */
        $icons = $config['icons'];

        $container->parameters()
            ->set('lle_crudit_ui.icons.library', $icons['library'])
            ->set('lle_crudit_ui.icons.map', $icons['map'])
            ->set('lle_crudit_ui.rtl_languages', $config['rtl_languages']);

        $container->import('../config/services.php');
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        if ($this->isAssetMapperAvailable($builder)) {
            $builder->prependExtensionConfig('framework', [
                'asset_mapper' => [
                    'paths' => [
                        $this->getPath() . '/assets/dist' => self::ASSETS_PACKAGE,
                    ],
                ],
            ]);
        }
    }

    private function isAssetMapperAvailable(ContainerBuilder $builder): bool
    {
        if (!interface_exists(AssetMapperInterface::class)) {
            return false;
        }

        /** @var array<string, array{path: string}> $bundlesMetadata */
        $bundlesMetadata = $builder->getParameter('kernel.bundles_metadata');
        if (!isset($bundlesMetadata['FrameworkBundle'])) {
            return false;
        }

        return is_file($bundlesMetadata['FrameworkBundle']['path'] . '/Resources/config/asset_mapper.php');
    }
}
