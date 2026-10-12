<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFunction;

class IconExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(private readonly bool $loadFontawesome)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('ui_icon', [IconRuntime::class, 'icon'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * lle_crudit_ui_load_fontawesome: the layouts of the bundles link fontawesome.css only when it is true.
     */
    public function getGlobals(): array
    {
        return [
            'lle_crudit_ui_load_fontawesome' => $this->loadFontawesome,
        ];
    }
}
