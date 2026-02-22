<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symkit\MediaBundle\Repository\MediaRepositoryInterface;

class MediaType extends AbstractType
{
    public function __construct(
        private readonly MediaRepositoryInterface $mediaRepository,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new DataTransformer\MediaToIdTransformer($this->mediaRepository, $this->translator));
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
