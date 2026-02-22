<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symkit\MediaBundle\Form\DataTransformer\MediaToIdTransformer;

final class MediaType extends AbstractType
{
    public function __construct(
        private readonly MediaToIdTransformer $mediaToIdTransformer,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer($this->mediaToIdTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'compound' => false,
            'data_class' => null,
        ]);
    }

    public function getParent(): string
    {
        return HiddenType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'media';
    }
}
