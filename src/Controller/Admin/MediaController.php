<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Controller\Admin;

use Symkit\CrudBundle\Controller\AbstractCrudController;
use Symkit\MediaBundle\Entity\Media;
use Symkit\MediaBundle\Form\MediaAdminType;
use Symkit\MenuBundle\Attribute\ActiveMenu;
use Symkit\MetadataBundle\Attribute\Breadcrumb;
use Symkit\MetadataBundle\Attribute\Seo;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/media')]
final class MediaController extends AbstractCrudController
{
    #[Route('', name: 'admin_media_list')]
    #[Seo(title: 'Media Library', description: 'Manage your uploaded files.')]
    #[Breadcrumb(context: 'admin')]
    #[ActiveMenu('content_cat', 'media')]
    public function list(Request $request): Response
    {
        return $this->renderIndex($request, [
            'page_title' => 'Media Library',
            'page_description' => 'Manage your uploaded files.',
        ]);
    }

    protected function getIndexTemplate(): string
    {
        return '@SymkitMedia/media/admin/index.html.twig';
    }

    protected function getEntityClass(): string
    {
        return Media::class;
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

    #[Route('/create', name: 'admin_media_create')]
    #[Seo(title: 'Upload Media', description: 'Add a new file to the library.')]
    #[Breadcrumb(context: 'admin')]
    #[ActiveMenu('content_cat', 'media')]
    public function create(Request $request): Response
    {
        return $this->renderNew(new Media(), $request, [
            'page_title' => 'Upload Media',
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_media_edit')]
    #[Seo(title: 'Edit Media', description: 'Update file information.')]
    #[Breadcrumb(context: 'admin')]
    #[ActiveMenu('content_cat', 'media')]
    public function edit(Media $media, Request $request): Response
    {
        return $this->renderEdit($media, $request, [
            'page_title' => 'Edit Media',
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_media_delete', methods: ['POST'])]
    public function delete(Media $media, Request $request): Response
    {
        return $this->performDelete($media, $request);
    }
}
