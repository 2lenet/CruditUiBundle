<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Twig;

use Lle\CruditUiBundle\Filter\PeriodPreset;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Attribute\AsTwigFunction;

/**
 * Function `ui_period_presets()`: the ready-made periods of `Filter:Period` (id, label, from, to), from today.
 */
class FilterExtension
{
    public function __construct(
        protected TranslatorInterface $translator,
    ) {
    }

    /**
     * @return list<array{id: string, label: string, from: string, to: string}>
     */
    #[AsTwigFunction('ui_period_presets')]
    public function periodPresets(?\DateTimeInterface $now = null): array
    {
        $now ??= new \DateTimeImmutable('today');
        $periods = [];
        foreach (PeriodPreset::cases() as $preset) {
            $periods[] = [
                'id' => $preset->value,
                'label' => $this->translator->trans('crudit_ui.filter.period.' . $preset->value, [], 'LleCruditUiBundle'),
            ] + $preset->range($now);
        }

        return $periods;
    }
}
