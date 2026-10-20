<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Turbo\TurboBundle;

/**
 * Pages of the components of the bundle under Turbo Drive (tests/apps/smoke-turbo.mjs): visits, cache and Back,
 * forms answered by a redirect (303), by their errors (422) or by a Turbo Stream, a Turbo Frame and a page refreshed
 * by a morph.
 */
#[Route('/turbo')]
class TurboController extends AbstractController
{
    #[Route('', name: 'turbo_home', methods: ['GET'])]
    #[Route('/{_locale}', name: 'turbo_home_locale', requirements: ['_locale' => 'fr|en|ar'], methods: ['GET'])]
    public function home(): Response
    {
        return $this->render('turbo/index.html.twig');
    }

    #[Route('/second', name: 'turbo_second', methods: ['GET'])]
    public function second(): Response
    {
        return $this->render('turbo/second.html.twig');
    }

    // Answered by a redirect: the flash message is shown as a toast by the next page.
    #[Route('/save', name: 'turbo_save', methods: ['POST'])]
    public function save(): Response
    {
        $this->addFlash('success', 'The visit V-0412 is saved.');

        return $this->redirectToRoute('turbo_second', status: Response::HTTP_SEE_OTHER);
    }

    // Answered by a Turbo Stream: a toast appended to the region, a fragment of the page replaced. With "slow", the
    // answer comes late: the button stays in loading meanwhile.
    #[Route('/stream', name: 'turbo_stream', methods: ['POST'])]
    public function sendStream(Request $request): Response
    {
        if ($request->request->has('slow')) {
            usleep(800_000);
        }
        if (TurboBundle::STREAM_FORMAT !== $request->getPreferredFormat()) {
            return $this->redirectToRoute('turbo_home', status: Response::HTTP_SEE_OTHER);
        }
        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
        $sent = $request->getSession()->get('turbo_streams', 0) + 1;
        $request->getSession()->set('turbo_streams', $sent);

        return $this->render('turbo/answer.stream.html.twig', ['sent' => $sent]);
    }

    // A form in a dialog: sent back with its errors (422), the dialog open; else a redirect to the home page.
    #[Route('/visit', name: 'turbo_visit', methods: ['POST'])]
    public function visit(Request $request): Response
    {
        if ('' === trim($request->request->getString('reason'))) {
            return $this->render('turbo/index.html.twig', ['invalid' => true], new Response(status: Response::HTTP_UNPROCESSABLE_ENTITY));
        }
        $this->addFlash('success', 'The visit V-0412 is postponed.');

        return $this->redirectToRoute('turbo_home', status: Response::HTTP_SEE_OTHER);
    }

    #[Route('/frame', name: 'turbo_frame', methods: ['GET'])]
    public function frame(): Response
    {
        return $this->render('turbo/frame.html.twig');
    }

    // Content of the frame: its first step loaded lazily, the next one by a link of the frame.
    #[Route('/frame/content', name: 'turbo_frame_content', methods: ['GET'])]
    public function frameContent(Request $request): Response
    {
        return $this->render('turbo/frame_content.html.twig', ['step' => max(1, $request->query->getInt('step', 1))]);
    }

    // Refreshed by a morph (turbo-refresh-method): a form sent back with its errors (422), or a redirect to itself.
    #[Route('/morph', name: 'turbo_morph', methods: ['GET', 'POST'])]
    public function morph(Request $request): Response
    {
        $session = $request->getSession();
        if ($request->isMethod('POST')) {
            if ('' === trim($request->request->getString('reason'))) {
                return $this->render('turbo/morph.html.twig', ['invalid' => true, 'refreshes' => $session->get('turbo_refreshes', 0)], new Response(status: Response::HTTP_UNPROCESSABLE_ENTITY));
            }
            $session->set('turbo_refreshes', $session->get('turbo_refreshes', 0) + 1);
            $this->addFlash('success', 'The visit V-0412 is postponed.');

            return $this->redirectToRoute('turbo_morph', status: Response::HTTP_SEE_OTHER);
        }

        return $this->render('turbo/morph.html.twig', ['refreshes' => $session->get('turbo_refreshes', 0)]);
    }
}
