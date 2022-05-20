<?php

namespace App\Repository;

use App\Entity\Status;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Status|null find($id, $lockMode = null, $lockVersion = null)
 * @method Status|null findOneBy(array $criteria, array $orderBy = null)
 * @method Status[]    findAll()
 * @method Status[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StatusRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Status::class);
    }

    /**
     * @return [] Returns an array ids and colors
     */
    public function getArrayOfColors()
    {
        $statuses = $this->createQueryBuilder('s')
            ->getQuery()
            ->getResult();
        $colorArray = [];
        foreach ($statuses as $status) {
            $colorArray[$status->getId()] = $status->getColor();
        }
        return $colorArray;
    }

}
