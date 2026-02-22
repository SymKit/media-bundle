<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symkit\FormBundle\Form\Type\FormSectionType;
use Symkit\MediaBundle\Entity\Media;
use Symkit\MediaBundle\Form\Type\MediaUploadType;
use Symkit\MediaBundle\Service\MediaUrlGenerator;

class MediaAdminType extends AbstractType
{
    private const TRANSLATION_DOMAIN = 'SymkitMediaBundle';

    /**
     * @param class-string $entityClass
     */
    public function __construct(
        private readonly MediaUrlGenerator $urlGenerator,
        private readonly string $entityClass,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options, $builder): void {
            $media = $event->getData();
            $form = $event->getForm();

            $placeholderText = $this->translator->trans('form.placeholder', [], self::TRANSLATION_DOMAIN);
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
                    'label' => $this->translator->trans('form.file_section.label', [], self::TRANSLATION_DOMAIN),
                    'section_icon' => 'heroicons:document-plus-20-solid',
                    'section_description' => $this->translator->trans('form.file_section.description', [], self::TRANSLATION_DOMAIN),
                    'auto_initialize' => false,
                ])
                    ->add('file', MediaUploadType::class, [
                        'mapped' => false,
                        'required' => $options['is_new'],
                        'attr' => $dropzoneAttr,
                        'constraints' => $options['is_new'] ? [new NotBlank(['groups' => ['create']])] : [],
                    ])
                    ->getForm(),
            );
        });

        $builder->add(
            $builder->create('general', FormSectionType::class, [
                'inherit_data' => true,
                'label' => $this->translator->trans('form.general.label', [], self::TRANSLATION_DOMAIN),
                'section_icon' => 'heroicons:information-circle-20-solid',
                'section_description' => $this->translator->trans('form.general.description', [], self::TRANSLATION_DOMAIN),
            ])
                ->add('altText', TextType::class, [
                    'label' => $this->translator->trans('form.alt_text.label', [], self::TRANSLATION_DOMAIN),
                    'required' => false,
                    'help' => $this->translator->trans('form.alt_text.help', [], self::TRANSLATION_DOMAIN),
                ]),
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => $this->entityClass,
            'is_new' => false,
        ]);

        $resolver->setAllowedTypes('is_new', 'bool');
    }
}
