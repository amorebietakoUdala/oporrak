<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\Status;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Event|null find($id, $lockMode = null, $lockVersion = null)
 * @method Event|null findOneBy(array $criteria, array $orderBy = null)
 * @method Event[]    findAll()
 * @method Event[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * @return Event[] Returns an array of Event objects
     */
    public function findUserEventsBeetweenDates($user, $startDate, $endDate = null)
    {
        $qb = $this->createQueryBuilder('e')
            ->andWhere('e.user = :user')
            ->setParameter('user', $user)
            ->andWhere('e.startDate >= :startDate')
            ->setParameter('startDate', $startDate);
        if (null !== $endDate) {
            $qb->andWhere('e.endDate < :endDate')
                ->setParameter('endDate', $endDate);
        }
        $qb->orderBy('e.id', 'ASC')
            //            ->setMaxResults(10)
        ;
        return $qb->getQuery()->getResult();
    }


    /**
     * @return Event[] Returns an array of Event objects
     */
    public function findByDepartmentAndUserAndStatusBeetweenDates($department = null, $user = null, $status = null, $startDate, $endDate = null)
    {
        $qb = $this->createQueryBuilder('e')
            ->innerJoin('e.user', 'u', 'WITH', 'e.user = u.id')
            ->andWhere('e.startDate >= :startDate')
            ->setParameter('startDate', $startDate);
        if (null !== $endDate) {
            $qb->andWhere('e.endDate < :endDate')
                ->setParameter('endDate', $endDate);
        }
        if (null !== $user) {
            $qb->andWhere('e.user = :user')
                ->setParameter('user', $user);
        }
        if (null !== $status) {
            $qb->andWhere('e.status = :status')
                ->setParameter('status', $status);
        }
        if (null !== $department) {
            $qb->andWhere('u.department = :department')
                ->setParameter('department', $department);
        }
        $qb->orderBy('e.id', 'ASC')
            //            ->setMaxResults(10)
        ;
        return $qb->getQuery()->getResult();
    }

    /**
     * @return Event[] Returns an array of Event objects
     */
    public function findAllByStatusBeetweenDates($status, $startDate, $endDate = null)
    {
        $qb = $this->createQueryBuilder('e')
            ->innerJoin('e.user', 'u', 'WITH', 'e.user = u.id')
            ->andWhere('e.startDate >= :startDate')
            ->setParameter('startDate', $startDate)
            ->andWhere('e.status = :status')
            ->setParameter('status', $status);
        if (null !== $endDate) {
            $qb->andWhere('e.endDate < :endDate')
                ->setParameter('endDate', $endDate);
        }
        $qb->orderBy('e.id', 'ASC')
            //            ->setMaxResults(10)
        ;
        return $qb->getQuery()->getResult();
    }

    /**
     * @return Event[] Returns an array of Event objects
     */
    public function findAllAprovedBeetweenDates($startDate, $endDate = null)
    {
        return $this->findAllByStatusBeetweenDates(Status::APPROVED, $startDate, $endDate);
    }

    /**
     * @return Event[] Returns an array of Event objects
     */
    public function findOverlapingEventsNotOfCurrentUser(Event $event): array
    {
        $qb = $this->createQueryBuilder('e')
            ->innerJoin('e.user', 'u', 'WITH', 'e.user = u.id')
            ->innerJoin('u.department', 'd', 'WITH', 'u.department = d.id')
            ->andWhere('u.department = :department')
            ->setParameter('department', $event->getUser()->getDepartment())
            // Exclude self events
            ->andWhere('e.user <> :user')
            ->setParameter('user', $event->getUser());

        $where = '(' .
            '(e.startDate >= :d2s and e.endDate <= :d2e ) or ' .
            '(e.startDate <= :d2s and e.endDate >= :d2e ) or ' .
            '(e.startDate >= :d2s and e.startDate <= :d2e ) or ' .
            '(e.endDate >= :d2s and e.endDate <= :d2e ))';
        $qb->andWhere($where)
            ->setParameter('d2s', $event->getStartDate())
            ->setParameter('d2e', $event->getEndDate());
        $qb->orderBy('e.id', 'ASC');
        return $qb->getQuery()->getResult();
    }
}
