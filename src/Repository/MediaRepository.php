<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Symkit\MediaBundle\Entity\Media;

/**
 * @extends ServiceEntityRepository<object>
 */
final class MediaRepository extends ServiceEntityRepository implements MediaRepositoryInterface
{
    public function __construct(ManagerRegistry $registry, string $entityClass)
    {
        parent::__construct($registry, $entityClass);
    }

    public function search(string $query, int $page = 1, int $limit = 24): Paginator
    {
        $qb = $this->createQueryBuilder('m')
            ->orderBy('m.createdAt', 'DESC')
        ;

        if ($query) {
            $qb->andWhere('m.filename LIKE :query OR m.altText LIKE :query OR m.originalFilename LIKE :query')
                ->setParameter('query', '%'.$query.'%')
            ;
        }

        $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
        ;

        /** @var Paginator<Media> $paginator */
        $paginator = new Paginator($qb);

        return $paginator;
    }

    /**
     * @return iterable<Media>
     */
    public function findForGlobalSearch(string $query, int $limit = 5): iterable
    {
        /** @var iterable<Media> $result */
        $result = $this->createQueryBuilder('m')
            ->where('m.filename LIKE :query OR m.altText LIKE :query OR m.originalFilename LIKE :query')
            ->setParameter('query', '%'.$query.'%')
            ->setMaxResults($limit)
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->toIterable();

        return $result;
    }
}
