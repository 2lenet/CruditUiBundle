<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Attribute\AsTwigFunction;

/**
 * Theme chosen by the user (light or dark), remembered in a cookie by the theme controller: the layout puts it in
 * the `data-theme` attribute of <html>, so the page is displayed in the right theme from the first paint, without
 * an inline script. No choice (null) follows the system preference, in CSS.
 */
class ThemeExtension
{
    public const string COOKIE = 'crudit_ui_theme';

    public const array THEMES = ['light', 'dark'];

    public function __construct(
        protected RequestStack $requestStack,
    ) {
    }

    #[AsTwigFunction('ui_theme')]
    public function theme(): ?string
    {
        $theme = $this->requestStack->getCurrentRequest()?->cookies->get(self::COOKIE);

        return \in_array($theme, self::THEMES, true) ? $theme : null;
    }
}
