<?php

declare(strict_types=1);

use Symfony\Bundle\FrameworkBundle\Controller\TemplateController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

/*
 * Design kit page, for development only: the project imports this file under `when@dev` (docs/components.md).
 * The optional locale shows the kit in another language and direction (`/crudit-ui/kit/ar`).
 */
return static function (RoutingConfigurator $routes): void {
    $routes->add('crudit_ui_kit', '/crudit-ui/kit/{_locale}')
        ->controller(TemplateController::class)
        ->defaults([
            'template' => '@LleCruditUi/kit/index.html.twig',
            '_locale' => null,
        ])
        ->requirements(['_locale' => '[a-z]{2,3}(_[A-Z]{2})?'])
        ->methods(['GET']);
};
