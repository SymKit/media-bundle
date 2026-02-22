<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Controller\Admin;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Translation\TranslatableMessage;
use Symkit\CrudBundle\Controller\AbstractCrudController;
use Symkit\MediaBundle\Form\MediaAdminType;
use Symkit\MediaBundle\Repository\MediaRepositoryInterface;
use Symkit\MenuBundle\Attribute\ActiveMenu;
use Symkit\MetadataBundle\Attribute\Breadcrumb;
use Symkit\MetadataBundle\Attribute\Seo;

final class MediaController extends AbstractCrudController
{
    /**
     * @param class-string $entityClass
     */
    public function __construct(
        \Symkit\CrudBundle\Contract\CrudPersistenceManagerInterface $persistenceManager,
        \Symkit\MetadataBundle\Contract\PageContextBuilderInterface $pageContextBuilder,
        private readonly string $entityClass,
        private readonly MediaRepositoryInterface $mediaRepository,
    ) {
        parent::__construct($persistenceManager, $pageContextBuilder);
    }

    #[Seo(title: 'admin.list.title', description: 'admin.list.description')]
    #[Breadcrumb(context: 'admin')]
    #[ActiveMenu('content_cat', 'media')]
    public function list(Request $request): Response
    {
        return $this->renderIndex($request, [
            'page_title' => new TranslatableMessage('admin.list.title', [], 'SymkitMediaBundle'),
            'page_description' => new TranslatableMessage('admin.list.description', [], 'SymkitMediaBundle'),
        ]);
    }

    protected function getIndexTemplate(): string
    {
        return '@SymkitMedia/media/admin/index.html.twig';
    }

    protected function getEntityClass(): string
    {
        return $this->entityClass;
    }

    protected function getNewFormOptions(object $entity): array
    {
        return array_merge(parent::getNewFormOptions($entity), [
            'is_new' => true,
        ]);
    }

    protected function getEditFormOptions(object $entity): array
    {
        return array_merge(parent::getEditFormOptions($entity), [
            'is_new' => false,
        ]);
    }

    protected function getFormClass(): string
    {
        return MediaAdminType::class;
    }

    protected function getRoutePrefix(): string
    {
        return 'admin_media';
    }

    #[Seo(title: 'admin.create.title', description: 'admin.create.description')]
    #[Breadcrumb(context: 'admin')]
    #[ActiveMenu('content_cat', 'media')]
    public function create(Request $request): Response
    {
        $entity = new $this->entityClass();

        return $this->renderNew($entity, $request, [
            'page_title' => new TranslatableMessage('admin.create.title', [], 'SymkitMediaBundle'),
        ]);
    }

    #[Seo(title: 'admin.edit.title', description: 'admin.edit.description')]
    #[Breadcrumb(context: 'admin')]
    #[ActiveMenu('content_cat', 'media')]
    public function edit(int $id, Request $request): Response
    {
        $media = $this->mediaRepository->find($id);
        if (null === $media) {
            throw new NotFoundHttpException(\sprintf('Media with id "%d" not found.', $id));
        }

        return $this->renderEdit($media, $request, [
            'page_title' => new TranslatableMessage('admin.edit.title', [], 'SymkitMediaBundle'),
        ]);
    }

    public function delete(int $id, Request $request): Response
    {
        $media = $this->mediaRepository->find($id);
        if (null === $media) {
            throw new NotFoundHttpException(\sprintf('Media with id "%d" not found.', $id));
        }

        return $this->performDelete($media, $request);
    }
}
