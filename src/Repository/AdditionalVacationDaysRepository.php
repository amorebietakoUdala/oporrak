<?php

namespace App\Repository;

use App\Entity\AdditionalVacationDays;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AdditionalVacationDays|null find($id, $lockMode = null, $lockVersion = null)
 * @method AdditionalVacationDays|null findOneBy(array $criteria, array $orderBy = null)
 * @method AdditionalVacationDays[]    findAll()
 * @method AdditionalVacationDays[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AdditionalVacationDaysRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdditionalVacationDays::class);
    }

    /**
     * @param $yearWorked The yearsWorked to look for the AdditionalVacationDays
     * 
     * @return AdditionalVacationDays Returns an AdditionalVacationDays object for the yearsWorked parameter
     */
    public function findAdditionalVacationDaysForYearsWorked(?int $yearWorked): ?AdditionalVacationDays {
        if ( $yearWorked === null ) {
            return null;
        }
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.yearsWorked <= :yearsWorked')
            ->setParameter('yearsWorked', $yearWorked)
            ->orderBy('a.yearsWorked', 'DESC')
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
