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

    // Target of the forms of the kit that show a loading button: 204, the browser stays on the page.
    $routes->add('crudit_ui_kit_sent', '/crudit-ui/kit-sent')
        ->controller(TemplateController::class)
        ->defaults([
            'template' => '@LleCruditUi/kit/_sent.html.twig',
            'statusCode' => 204,
        ])
        ->methods(['GET']);
};
