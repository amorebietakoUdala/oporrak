<?php

namespace App\Repository;

use App\Entity\Holiday;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Holiday|null find($id, $lockMode = null, $lockVersion = null)
 * @method Holiday|null findOneBy(array $criteria, array $orderBy = null)
 * @method Holiday[]    findAll()
 * @method Holiday[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HolidayRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Holiday::class);
    }

    /**
     * @return Holiday[] Returns an array of Holiday objects
     */

    public function findHolidaysBetween(\DateTime $startDate, \DateTime $endDate = null)
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.date >= :startDate')
            ->andWhere('h.date <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('h.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
