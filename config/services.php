<?php

declare(strict_types=1);

use Lle\CruditUiBundle\Twig\IconExtension;
use Lle\CruditUiBundle\Twig\IconRuntime;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure()
            ->private();

    $services->set(IconExtension::class)
        ->arg('$loadFontawesome', param('lle_crudit_ui.icons.load_fontawesome'));
    $services->set(IconRuntime::class)
        ->arg('$map', param('lle_crudit_ui.icons.map'));
};
