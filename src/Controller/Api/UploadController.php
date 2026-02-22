<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Controller\Api;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symkit\CrudBundle\Enum\CrudEvents;
use Symkit\CrudBundle\Event\CrudEvent;
use Symkit\MediaBundle\Form\Type\MediaUploadType;
use Symkit\MediaBundle\Security\SecurityException;
use Symkit\MediaBundle\Service\MediaManager;
use Symkit\MediaBundle\Service\MediaUrlGenerator;

class UploadController extends AbstractController
{
    /**
     * @param class-string $entityClass
     */
    public function __construct(
        private readonly MediaManager $mediaManager,
        private readonly ValidatorInterface $validator,
        private readonly MediaUrlGenerator $urlGenerator,
        private readonly EntityManagerInterface $entityManager,
        private readonly EventDispatcherInterface $eventDispatcher,
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

            return $this->json(['error' => $message], Response::HTTP_BAD_REQUEST);
        }

        $form = $this->createFormBuilder(null, [
            'csrf_protection' => false,
        ])
            ->add('file', MediaUploadType::class)
            ->getForm()
        ;
        $form->submit(['file' => $file]);

        if (!$form->isValid()) {
            return $this->json(['error' => (string) $form->getErrors(true)], Response::HTTP_BAD_REQUEST);
        }

        try {
            $media = new $this->entityClass();

            $this->eventDispatcher->dispatch(new CrudEvent($media, $form, $request), CrudEvents::PRE_PERSIST->value);

            $this->entityManager->persist($media);
            $this->entityManager->flush();

            $this->eventDispatcher->dispatch(new CrudEvent($media, $form, $request), CrudEvents::POST_PERSIST->value);

            return $this->json([
                'id' => $media->getId(),
                'url' => $this->urlGenerator->generateUrl($media),
                'filename' => $media->getOriginalFilename(),
            ]);
        } catch (SecurityException $e) {
            $message = $this->translator->trans('api.error.security', [], 'SymkitMediaBundle');

            return $this->json(['error' => $message], Response::HTTP_BAD_REQUEST);
        } catch (TransformationFailedException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            $this->logger?->error('Media upload failed', ['exception' => $e]);
            $message = $this->translator->trans('api.error.upload_failed', [], 'SymkitMediaBundle');

            return $this->json(['error' => $message], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
