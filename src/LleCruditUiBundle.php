<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle;

use Lle\CruditUiBundle\Icon\IconCatalogue;
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

    /**
     * Name of an icon for Symfony UX Icons (rule of `Icon::isValidName()`, internal): an Iconify name such as
     * `tabler:trash-x`, or a local SVG of `assets/icons/` (`logo`, `flags:fr` in a sub-folder).
     *
     * @internal public for the test that compares it with the rule of UX Icons
     */
    public const string ICON_NAME = '/^[a-z0-9]+(-[a-z0-9]+)*(:[a-z0-9]+(-[a-z0-9]+)*)*$/';

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
                            ->scalarPrototype()
                                ->validate()
                                    ->ifTrue(static fn (mixed $icon): bool => !\is_string($icon) || 1 !== preg_match(self::ICON_NAME, $icon))
                                    ->thenInvalid('%s is not an icon name, such as "tabler:trash-x" (or "logo" for assets/icons/logo.svg).')
                                ->end()
                                ->validate()
                                    // taken for a local SVG (assets/icons/delete.svg), so nothing would be rendered
                                    ->ifTrue(static fn (string $icon): bool => isset(IconCatalogue::ICONS[$icon]))
                                    ->thenInvalid('%s is a logical name of the catalogue: give its Iconify name, such as "fa7-solid:trash", or put an SVG of the project in a subfolder.')
                                ->end()
                            ->end()
                            ->validate()
                                ->always(static function (array $map): array {
                                    $unknown = self::unknownIcons($map);
                                    if ([] === $unknown) {
                                        return $map;
                                    }

                                    throw new \InvalidArgumentException(sprintf(
                                        'Unknown icon(s) "%s": the keys are logical names of the catalogue (docs/icons.md), such as "delete", or Iconify names, such as "lucide:truck".',
                                        implode('", "', $unknown),
                                    ));
                                })
                            ->end()
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

    /**
     * Keys of `icons.map` that are neither a logical name of the catalogue nor an Iconify name (with a prefix, as
     * `ui_icon()` receives them from a project): a typo, or a class of the former icon font (`fa-solid fa-trash`),
     * which would otherwise be ignored without an error.
     *
     * @param array<array-key, mixed> $map
     *
     * @return list<string>
     */
    private static function unknownIcons(array $map): array
    {
        return array_values(array_filter(
            array_map(strval(...), array_keys($map)),
            static fn (string $icon): bool => !isset(IconCatalogue::ICONS[$icon])
                && (!str_contains($icon, ':') || 1 !== preg_match(self::ICON_NAME, $icon)),
        ));
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
