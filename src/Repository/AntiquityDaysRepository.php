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
}
