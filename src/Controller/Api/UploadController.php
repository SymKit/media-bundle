<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Controller\Api;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symkit\CrudBundle\Enum\CrudEvents;
use Symkit\CrudBundle\Event\CrudEvent;
use Symkit\MediaBundle\Entity\Media;
use Symkit\MediaBundle\Form\Type\MediaUploadType;
use Symkit\MediaBundle\Security\SecurityException;
use Symkit\MediaBundle\Service\MediaUrlGenerator;

final readonly class UploadController
{
    /**
     * @param class-string<Media> $entityClass
     */
    public function __construct(
        private readonly MediaUrlGenerator $urlGenerator,
        private readonly EntityManagerInterface $entityManager,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly FormFactoryInterface $formFactory,
        private readonly string $entityClass,
        private readonly TranslatorInterface $translator,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    public function upload(Request $request): JsonResponse
    {
        $file = $request->files->get('file');

        if (!$file) {
            $message = $this->translator->trans('api.error.no_file', [], 'SymkitMediaBundle');

            return new JsonResponse(['error' => $message], Response::HTTP_BAD_REQUEST);
        }

        $form = $this->formFactory->createBuilder(\Symfony\Component\Form\Extension\Core\Type\FormType::class, null, [
            'csrf_protection' => false,
        ])
            ->add('file', MediaUploadType::class)
            ->getForm();
        $form->submit(['file' => $file]);

        if (!$form->isValid()) {
            return new JsonResponse(['error' => (string) $form->getErrors(true)], Response::HTTP_BAD_REQUEST);
        }

        try {
            /** @var Media $media */
            $media = new $this->entityClass();

            $this->eventDispatcher->dispatch(new CrudEvent($media, $form, $request), CrudEvents::PRE_PERSIST->value);

            $this->entityManager->persist($media);
            $this->entityManager->flush();

            $this->eventDispatcher->dispatch(new CrudEvent($media, $form, $request), CrudEvents::POST_PERSIST->value);

            return new JsonResponse([
                'id' => $media->getId(),
                'url' => $this->urlGenerator->generateUrl($media),
                'filename' => $media->getOriginalFilename(),
            ]);
        } catch (SecurityException $e) {
            $message = $this->translator->trans('api.error.security', [], 'SymkitMediaBundle');

            return new JsonResponse(['error' => $message], Response::HTTP_BAD_REQUEST);
        } catch (TransformationFailedException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            $this->logger?->error('Media upload failed', ['exception' => $e]);
            $message = $this->translator->trans('api.error.upload_failed', [], 'SymkitMediaBundle');

            return new JsonResponse(['error' => $message], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
