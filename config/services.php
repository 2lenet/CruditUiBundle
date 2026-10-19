<?php

declare(strict_types=1);

use Lle\CruditUiBundle\Format\Formatter;
use Lle\CruditUiBundle\Icon\IconFinder;
use Lle\CruditUiBundle\Icon\IconLibrary;
use Lle\CruditUiBundle\Locale\RequestLocale;
use Lle\CruditUiBundle\Twig\FormatExtension;
use Lle\CruditUiBundle\Twig\IconExtension;
use Lle\CruditUiBundle\Twig\LocaleExtension;
use Lle\CruditUiBundle\Twig\ThemeExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\inline_service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure()
            ->private();

    // an enum is not autowired; one definition per service, the dumper does not share an inline service between two
    $library = static fn () => inline_service(IconLibrary::class)
        ->factory([IconLibrary::class, 'from'])
        ->args([param('lle_crudit_ui.icons.library')]);

    $services->set(IconExtension::class)
        ->arg('$library', $library())
        ->arg('$map', param('lle_crudit_ui.icons.map'));

    // tagged ux_icons.finder by autoconfiguration: read by ux:icons:lock and the cache warmer
    $services->set(IconFinder::class)
        ->arg('$library', $library())
        ->arg('$map', param('lle_crudit_ui.icons.map'));

    $services->set(ThemeExtension::class);

    $services->set(RequestLocale::class)
        ->arg('$defaultLocale', param('kernel.default_locale'));

    $services->set(Formatter::class);

    $services->set(FormatExtension::class);

    $services->set(LocaleExtension::class)
        ->arg('$rtlLanguages', param('lle_crudit_ui.rtl_languages'));
};
