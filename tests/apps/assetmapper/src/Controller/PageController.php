<?php

namespace App\Controller;

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

    #[Route('/crudit', name: 'crudit')]
    public function crudit(): Response
    {
        return $this->render('crudit.html.twig');
    }
}
