<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Twig;

use Symfony\Component\Form\FormView;
use Twig\Attribute\AsTwigFunction;

/**
 * Checks of the form theme (`@LleCruditUi/form/theme.html.twig`), in debug only: a mistake that would fail without a
 * message in the page throws instead.
 */
class FormThemeExtension
{
    public function __construct(
        protected bool $debug,
    ) {
    }

    /**
     * A collection inside a line of another one with the same prototype name: when a line of the outer collection is
     * added, the controller collection replaces this name everywhere in its prototype, also in the inner prototype, and
     * the inner button Add no longer works (docs/forms.md, "A collection inside a line").
     *
     * @throws \LogicException in debug, if a collection around this one has a prototype of the same name
     */
    #[AsTwigFunction('ui_form_check_collection')]
    public function checkCollection(FormView $collection): void
    {
        $prototype = $collection->vars['prototype'] ?? null;
        if (!$this->debug || !$prototype instanceof FormView) {
            return;
        }

        $name = $prototype->vars['name'];
        for ($parent = $collection->parent; null !== $parent; $parent = $parent->parent) {
            $around = $parent->vars['prototype'] ?? null;
            if ($around instanceof FormView && $around->vars['name'] === $name) {
                throw new \LogicException(\sprintf('The collection "%s" has the prototype name "%s" of the collection "%s" around it: give it a prototype_name of its own (\'__%s__\'), else its button Add does not work.', $collection->vars['full_name'], $name, $parent->vars['full_name'], $collection->vars['name']));
            }
        }
    }
}
