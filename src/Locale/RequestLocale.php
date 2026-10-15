<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Locale;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Locale of the current request, `kernel.default_locale` outside a request (command, message handler). Shared by the
 * Twig extensions of the languages and of the formats.
 */
class RequestLocale
{
    public function __construct(
        protected RequestStack $requestStack,
        protected string $defaultLocale,
    ) {
    }

    public function get(): string
    {
        return $this->requestStack->getCurrentRequest()?->getLocale() ?? $this->defaultLocale;
    }
}
