<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\UX\Dropzone\Form\DropzoneType;

class MediaUploadType extends AbstractType
{
    public function getParent(): string
    {
        return DropzoneType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => 'Media File',
            'attr' => [
                'placeholder' => 'Drag and drop a file or click to upload',
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
                    'mimeTypesMessage' => 'Please upload a valid file',
                ]),
            ],
        ]);
    }
}
