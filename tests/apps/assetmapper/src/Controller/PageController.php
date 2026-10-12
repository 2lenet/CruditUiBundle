<?php

namespace App\Controller;

use Lle\CruditUiBundle\Icon\IconCatalogue;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PageController extends AbstractController
{
    #[Route('/', name: 'home')]
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
    public function icons(): Response
    {
        return $this->render('icones.html.twig', ['names' => array_keys(IconCatalogue::ICONS)]);
    }
}
