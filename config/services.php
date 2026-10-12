<?php

declare(strict_types=1);

use Lle\CruditUiBundle\Icon\IconLibrary;
use Lle\CruditUiBundle\Twig\IconExtension;
use Lle\CruditUiBundle\Twig\IconRuntime;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\inline_service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure()
            ->private();

    $services->set(IconExtension::class);
    $services->set(IconRuntime::class)
        ->arg('$library', inline_service(IconLibrary::class)
            ->factory([IconLibrary::class, 'from'])
            ->args([param('lle_crudit_ui.icons.library')]))
        ->arg('$map', param('lle_crudit_ui.icons.map'));
};
