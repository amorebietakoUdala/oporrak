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

    // /**
    //  * @return AntiquityDays[] Returns an array of AntiquityDays objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?AntiquityDays
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
