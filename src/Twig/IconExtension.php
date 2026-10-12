<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class IconExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('ui_icon', [IconRuntime::class, 'icon'], ['is_safe' => ['html']]),
        ];
    }
}
