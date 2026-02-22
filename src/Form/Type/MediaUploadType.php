<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\Dropzone\Form\DropzoneType;

final class MediaUploadType extends AbstractType
{
    private const TRANSLATION_DOMAIN = 'SymkitMediaBundle';

    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function getParent(): string
    {
        return DropzoneType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => $this->translator->trans('form.media_file.label', [], self::TRANSLATION_DOMAIN),
            'attr' => [
                'placeholder' => $this->translator->trans('form.placeholder', [], self::TRANSLATION_DOMAIN),
            ],
            'constraints' => [
                new File([
                    'maxSize' => '10M',
                    'mimeTypes' => [
                        'image/jpeg',
                        'image/png',
                        'image/webp',
                        'image/gif',
                        'image/svg+xml',
                        'application/pdf',
                    ],
                    'mimeTypesMessage' => $this->translator->trans('form.mime_types_message', [], self::TRANSLATION_DOMAIN),
                ]),
            ],
        ]);
    }
}
