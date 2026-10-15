<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Twig;

use Lle\CruditUiBundle\Locale\RequestLocale;
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
        protected RequestLocale $requestLocale,
        protected array $rtlLanguages,
    ) {
    }

    /**
     * Locale of the request in the BCP 47 form of the `lang` attribute: `ar_MA` gives `ar-MA`, the keywords of ICU
     * are left out (`ar_SA@numbers=arab` gives `ar-SA`).
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

    /**
     * Locale of the request without the keywords of ICU (`@numbers=arab`), which have no place in `lang`.
     */
    private function locale(): string
    {
        return explode('@', $this->requestLocale->get(), 2)[0];
    }
}
