<?php

namespace App\Repository;

use App\Entity\EventType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method EventType|null find($id, $lockMode = null, $lockVersion = null)
 * @method EventType|null findOneBy(array $criteria, array $orderBy = null)
 * @method EventType[]    findAll()
 * @method EventType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventType::class);
    }

    public function findByOnlyForUnionDelegatesQB (bool $onlyForUnionDelegates)
    {
        $qb = $this->createQueryBuilder('t')
            ->andWhere('t.onlyForUnionDelegates <= :onlyForUnionDelegates')
            ->setParameter('onlyForUnionDelegates', $onlyForUnionDelegates);
        return $qb;
    }
}
