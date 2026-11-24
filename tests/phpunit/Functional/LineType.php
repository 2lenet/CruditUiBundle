<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Tests\Functional;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * A line of a collection in FormThemeTest: a part, a quantity and the hidden field of its position.
 *
 * @extends AbstractType<array<string, mixed>>
 */
class LineType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('part', TextType::class, ['label' => 'Part', 'required' => false])
            ->add('quantity', IntegerType::class, ['label' => 'Quantity', 'required' => false])
            ->add('position', HiddenType::class);
    }
}
