<?php

namespace App\Controller;

use Lle\CruditUiBundle\Icon\IconCatalogue;
use Lle\CruditUiBundle\Icon\IconLibrary;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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

    #[Route('/vide', name: 'empty')]
    public function empty(): Response
    {
        return $this->render('empty.html.twig');
    }

    #[Route('/outils', name: 'tools')]
    public function tools(): Response
    {
        return $this->render('outils.html.twig');
    }

    #[Route('/icones', name: 'icons')]
    public function icons(Request $request): Response
    {
        $library = IconLibrary::tryFrom($request->query->getString('library')) ?? IconLibrary::FontAwesome;

        return $this->render('icones.html.twig', [
            'library' => $library,
            'libraries' => IconLibrary::cases(),
            'icons' => $library->icons(),
            'mirrored' => IconCatalogue::MIRRORED,
        ]);
    }
}
