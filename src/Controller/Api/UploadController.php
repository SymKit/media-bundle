<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Controller\Api;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symkit\CrudBundle\Enum\CrudEvents;
use Symkit\CrudBundle\Event\CrudEvent;
use Symkit\MediaBundle\Entity\Media;
use Symkit\MediaBundle\Form\Type\MediaUploadType;
use Symkit\MediaBundle\Service\MediaManager;
use Symkit\MediaBundle\Service\MediaUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/admin/medias/api')]
class UploadController extends AbstractController
{
    public function __construct(
        private readonly MediaManager $mediaManager,
        private readonly ValidatorInterface $validator,
        private readonly MediaUrlGenerator $urlGenerator,
        private readonly EntityManagerInterface $entityManager,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    #[Route('/upload', name: 'api_media_upload_async', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        $file = $request->files->get('file');

        if (!$file) {
            return $this->json(['error' => 'No file uploaded'], Response::HTTP_BAD_REQUEST);
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
            $media = new Media();

            $this->eventDispatcher->dispatch(new CrudEvent($media, $form, $request), CrudEvents::PRE_PERSIST->value);

            $this->entityManager->persist($media);
            $this->entityManager->flush();

            $this->eventDispatcher->dispatch(new CrudEvent($media, $form, $request), CrudEvents::POST_PERSIST->value);

            return $this->json([
                'id' => $media->getId(),
                'url' => $this->urlGenerator->generateUrl($media),
                'filename' => $media->getOriginalFilename(),
            ]);
        } catch (Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
