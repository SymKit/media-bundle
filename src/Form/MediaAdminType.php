<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Form;

use Symkit\FormBundle\Form\Type\FormSectionType;
use Symkit\MediaBundle\Entity\Media;
use Symkit\MediaBundle\Form\Type\MediaUploadType;
use Symkit\MediaBundle\Service\MediaUrlGenerator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class MediaAdminType extends AbstractType
{
    public function __construct(
        private readonly MediaUrlGenerator $urlGenerator,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options, $builder): void {
            $media = $event->getData();
            $form = $event->getForm();

            $placeholderText = 'Drag and drop a file or click to upload';
            $dropzoneAttr = [
                'placeholder' => $placeholderText,
            ];

            // Pass preview info via data attributes for form theme to handle
            if ($media instanceof Media && $media->getId() && str_starts_with($media->getMimeType() ?? '', 'image/')) {
                $dropzoneAttr['data-preview-url'] = $this->urlGenerator->generateUrl($media);
                $dropzoneAttr['data-preview-filename'] = $media->getOriginalFilename() ?? $media->getFilename();
            }

            $form->add(
                $builder->create('file_section', FormSectionType::class, [
                    'inherit_data' => true,
                    'label' => 'File',
                    'section_icon' => 'heroicons:document-plus-20-solid',
                    'section_description' => 'Upload or replace the media file.',
                    'auto_initialize' => false,
                ])
                    ->add('file', MediaUploadType::class, [
                        'mapped' => false,
                        'required' => $options['is_new'],
                        'attr' => $dropzoneAttr,
                        'constraints' => $options['is_new'] ? [new NotBlank(['groups' => ['create']])] : [],
                    ])
                    ->getForm()
            );
        });

        $builder->add(
            $builder->create('general', FormSectionType::class, [
                'inherit_data' => true,
                'label' => 'General',
                'section_icon' => 'heroicons:information-circle-20-solid',
                'section_description' => 'Meta information about the media.',
            ])
                ->add('altText', TextType::class, [
                    'label' => 'Alternative Text',
                    'required' => false,
                    'help' => 'Description for accessibility (alt tag).',
                ])
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Media::class,
            'is_new' => false,
        ]);

        $resolver->setAllowedTypes('is_new', 'bool');
    }
}
