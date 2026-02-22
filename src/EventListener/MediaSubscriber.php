<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symkit\CrudBundle\Enum\CrudEvents;
use Symkit\CrudBundle\Event\CrudEvent;
use Symkit\MediaBundle\Entity\Media;
use Symkit\MediaBundle\Form\Type\MediaUploadType;
use Symkit\MediaBundle\Service\MediaManager;

final readonly class MediaSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly MediaManager $mediaManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CrudEvents::PRE_PERSIST->value => 'onPrePersist',
            CrudEvents::PRE_UPDATE->value => 'onPreUpdate',
            CrudEvents::PRE_DELETE->value => 'onPreDelete',
        ];
    }

    public function onPrePersist(CrudEvent $event): void
    {
        $this->handleEvent($event);
    }

    public function onPreUpdate(CrudEvent $event): void
    {
        $this->handleEvent($event);
    }

    private function handleEvent(CrudEvent $event): void
    {
        $entity = $event->getEntity();
        if (!$entity instanceof Media) {
            return;
        }

        $form = $event->getForm();
        if (null === $form) {
            return;
        }

        $file = $this->getFileFromForm($form);

        if ($file) {
            $this->mediaManager->handleUpload($entity, $file);
        }
    }

    private function getFileFromForm(FormInterface $form): ?UploadedFile
    {
        foreach ($form as $child) {
            $config = $child->getConfig();
            $type = $config->getType()->getInnerType();

            if ($type instanceof MediaUploadType) {
                $data = $child->getData();

                return $data instanceof UploadedFile ? $data : null;
            }

            if ($child->count() > 0) {
                $file = $this->getFileFromForm($child);
                if ($file) {
                    return $file;
                }
            }
        }

        return null;
    }

    public function onPreDelete(CrudEvent $event): void
    {
        $entity = $event->getEntity();
        if (!$entity instanceof Media) {
            return;
        }

        $this->mediaManager->deleteMediaFiles($entity);
    }
}
