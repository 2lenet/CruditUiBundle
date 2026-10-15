<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PageController extends AbstractController
{
    #[Route('/', name: 'home')]
    #[Route('/{_locale}', name: 'home_locale', requirements: ['_locale' => 'fr|en|de|it|ar|ar_SA|ar_MA|fa'])]
    public function home(): Response
    {
        return $this->render('page.html.twig');
    }
}
