<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Repository;

use Doctrine\ORM\Tools\Pagination\Paginator;

interface MediaRepositoryInterface
{
    /**
     * @param int|string $id
     *
     * @return object|null
     */
    public function find($id);

    public function search(string $query, int $page = 1, int $limit = 24): Paginator;

    /**
     * @return iterable<object>
     */
    public function findForGlobalSearch(string $query, int $limit = 5): iterable;
}
