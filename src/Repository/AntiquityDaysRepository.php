<?php

namespace App\Repository;

use App\Entity\AntiquityDays;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AntiquityDays|null find($id, $lockMode = null, $lockVersion = null)
 * @method AntiquityDays|null findOneBy(array $criteria, array $orderBy = null)
 * @method AntiquityDays[]    findAll()
 * @method AntiquityDays[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AntiquityDaysRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AntiquityDays::class);
    }

    /**
     * @param $yearWorked The yearsWorked to look for the AntiquityDays
     * 
     * @return AntiquityDays Returns an AntiquityDays object for the yearsWorked parameter
     */
    public function findAntiquityDaysForYearsWorked(?int $yearWorked): ?AntiquityDays {
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
