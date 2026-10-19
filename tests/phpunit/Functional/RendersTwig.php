<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Tests\Functional;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Twig\Environment;

/**
 * Renders Twig, a component (`<twig:LleCruditUi:Button>`) or a page of a TestKernel, and parses the HTML for the assertions.
 */
trait RendersTwig
{
    /**
     * @param array<string, mixed> $context
     */
    private function render(TestKernel $kernel, string $template, array $context = []): string
    {
        $kernel->boot();

        /** @var ContainerInterface $testContainer */
        $testContainer = $kernel->getContainer()->get('test.service_container');
        /** @var Environment $twig */
        $twig = $testContainer->get('twig');

        return $twig->createTemplate($template)->render($context);
    }

    /**
     * Response to a GET of the path; an exception is thrown instead of being rendered as an error page.
     */
    private function get(TestKernel $kernel, string $path): string
    {
        $response = $kernel->handle(Request::create($path), HttpKernelInterface::MAIN_REQUEST, false);
        self::assertSame(200, $response->getStatusCode(), $path);

        return (string) $response->getContent();
    }

    /**
     * The element of a component rendered alone, found by its tag (`button`, `span`…).
     */
    /**
     * @param array<string, mixed> $context
     */
    private function element(string $template, string $selector, array $context = []): \Dom\Element
    {
        $element = $this->html($this->render(new TestKernel(), $template, $context))->querySelector('body > ' . $selector);
        self::assertNotNull($element, $template);

        return $element;
    }

    /**
     * HTML of a page or of a fragment, to query with CSS selectors (`querySelector()`).
     */
    private function html(string $html): \Dom\HTMLDocument
    {
        return \Dom\HTMLDocument::createFromString($html, LIBXML_NOERROR, 'UTF-8');
    }
}
