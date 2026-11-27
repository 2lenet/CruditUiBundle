<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Tests\Functional;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A line of a collection in FormThemeTest holding a collection of its own (parts), whose prototype name is an option.
 *
 * @extends AbstractType<array<string, mixed>>
 */
class GroupType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Name', 'required' => false])
            ->add('parts', CollectionType::class, [
                'label' => 'Parts',
                'entry_type' => TextType::class,
                'allow_add' => true,
                'prototype_name' => $options['parts_prototype_name'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('parts_prototype_name', '__part__');
        $resolver->setAllowedTypes('parts_prototype_name', 'string');
    }
}
