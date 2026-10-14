<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Attribute\AsTwigFunction;

/**
 * Language and direction of the page, for the `lang` and `dir` attributes of <html>: the CSS of the bundle mirrors
 * the layout from `dir`.
 */
class LocaleExtension
{
    /**
     * @param list<string> $rtlLanguages languages written from right to left, without region (`ar`)
     */
    public function __construct(
        protected RequestStack $requestStack,
        protected string $defaultLocale,
        protected array $rtlLanguages,
    ) {
    }

    /**
     * Locale of the request in the BCP 47 form of the `lang` attribute: `ar_MA` gives `ar-MA`.
     */
    #[AsTwigFunction('ui_lang')]
    public function lang(): string
    {
        return str_replace('_', '-', $this->locale());
    }

    /**
     * `rtl` when the language of the request (`ar` for `ar_MA`) is written from right to left, otherwise `ltr`.
     */
    #[AsTwigFunction('ui_dir')]
    public function dir(): string
    {
        $language = strtolower(explode('_', str_replace('-', '_', $this->locale()))[0]);

        return \in_array($language, $this->rtlLanguages, true) ? 'rtl' : 'ltr';
    }

    private function locale(): string
    {
        return $this->requestStack->getCurrentRequest()?->getLocale() ?? $this->defaultLocale;
    }
}
