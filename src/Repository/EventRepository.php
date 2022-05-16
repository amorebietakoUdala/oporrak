<?php

namespace App\Repository;

use App\Entity\Department;
use App\Entity\Event;
use App\Entity\Status;
use App\Entity\User;
use DateTime;
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
    public function findUserEventsCurrentYearAndType(User $user, int $year, $type = null, $onlyHalfDays = false)
    {
        $startDate = new \DateTime($year . '-01-01');
        $endDate = new \DateTime($year + 1 . '-01-01');
        $qb = $this->createQueryBuilder('e')
            ->andWhere('e.user = :user')
            ->setParameter('user', $user)
            ->andWhere('e.startDate >= :startDate')
            ->setParameter('startDate', $startDate)
            ->andWhere('e.endDate < :endDate')
            ->setParameter('endDate', $endDate);
        if ($onlyHalfDays) {
            $qb->andWhere('e.halfDay = :halfDay')
                ->setParameter('halfDay', $onlyHalfDays);
        }
        if (null !== $type) {
            $qb->andWhere('e.type = :type')
                ->setParameter('type', $type);
        }
        $qb->orderBy('e.id', 'ASC')
            //            ->setMaxResults(10)
        ;
        return $qb->getQuery()->getResult();
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
    public function findEffectiveUserEventsOfTheYear(User $user, int $year)
    {
        $thisYearStart = new DateTime("${year}-01-01");
        $thisYearEnd = new DateTime("${year}-12-31");
        $nextYear = $year + 1;
        $nextYearStart = new DateTime("${nextYear}-01-01");
        $nextYearEnd = new DateTime("${nextYear}-12-31");
        $condition = "(
            (e.startDate >= :startDate AND e.endDate < :endDate AND ( e.usePreviousYearDays = :false OR e.usePreviousYearDays IS NULL )) 
            OR (e.startDate >= :nextYearStartDate AND e.endDate < :nextYearEndDate AND e.usePreviousYearDays = :true)
            )";
        $qb = $this->createQueryBuilder('e')
            ->andWhere($condition)
            ->setParameter('startDate', $thisYearStart)
            ->setParameter('endDate', $thisYearEnd)
            ->setParameter('false', false)
            ->setParameter('nextYearStartDate', $nextYearStart)
            ->setParameter('nextYearEndDate', $nextYearEnd)
            ->setParameter('true', true)
            ->andWhere('e.user = :user')
            ->setParameter('user', $user);
        $qb->orderBy('e.id', 'ASC');
        return $qb->getQuery()->getResult();
    }

    /**
     * @return Event[] Returns an array of Event objects
     */
    public function findUserEventsOfTheYearWithPreviousYearDays(User $user, int $year, bool $previousYearDays = false)
    {
        $thisYearStart = new DateTime("${year}-01-01");
        $thisYearEnd = new DateTime("${year}-12-31");
        $qb = $this->createQueryBuilder('e');
        if ($previousYearDays) {
            $condition = "
                e.startDate >= :startDate AND e.endDate < :endDate AND ( e.usePreviousYearDays = :true  ) 
                ";
            $qb->andWhere($condition)
                ->setParameter('true', true);
        } else {
            $condition = " 
                e.startDate >= :startDate AND e.endDate < :endDate AND ( e.usePreviousYearDays = :false OR e.usePreviousYearDays IS NULL ) 
                ";
            $qb->andWhere($condition)
                ->setParameter('false', false);
        }
        $qb->setParameter('startDate', $thisYearStart)
            ->setParameter('endDate', $thisYearEnd)
            ->andWhere('e.user = :user')
            ->setParameter('user', $user);
        $qb->orderBy('e.id', 'ASC');
        return $qb->getQuery()->getResult();
    }


    /**
     * @return Event[] Returns an array of Event objects
     */
    public function findByUsernamesAndBeetweenDates(array $users = null, $startDate, $endDate = null, bool $previousYearDays = false)
    {
        $qb = $this->createQueryBuilder('e')
            ->innerJoin('e.user', 'u', 'WITH', 'e.user = u.id')
            ->andWhere('e.startDate >= :startDate')
            ->setParameter('startDate', $startDate);
        if ($previousYearDays) {
            $condition = "
                e.startDate >= :startDate AND e.endDate < :endDate AND ( e.usePreviousYearDays = :true  ) 
                ";
            $qb->andWhere($condition)
                ->setParameter('true', true);
        } else {
            $condition = " 
                e.startDate >= :startDate AND e.endDate < :endDate AND ( e.usePreviousYearDays = :false OR e.usePreviousYearDays IS NULL ) 
                ";
            $qb->andWhere($condition)
                ->setParameter('false', false);
        }            
        if (null !== $endDate) {
            $qb->andWhere('e.endDate < :endDate')
                ->setParameter('endDate', $endDate);
        }
        if (null !== $users) {
            $qb->andWhere('u.username in (:users)')
                ->setParameter('users', $users);
        }
        $qb->orderBy('e.id', 'ASC')
            //            ->setMaxResults(10)
        ;
        return $qb->getQuery()->getResult();
    }

    /**
     * @return Event[] Returns an array of Event objects
     */
    public function findByDepartmentAndUsersAndStatusBeetweenDates($department = null, array $users = null, $status = null, $startDate, $endDate = null)
    {
        $qb = $this->createQueryBuilder('e')
            ->innerJoin('e.user', 'u', 'WITH', 'e.user = u.id')
            ->andWhere('e.startDate >= :startDate')
            ->setParameter('startDate', $startDate);
        if (null !== $endDate) {
            $qb->andWhere('e.endDate < :endDate')
                ->setParameter('endDate', $endDate);
        }
        if (null !== $users) {
            $qb->andWhere('e.user in (:users)')
                ->setParameter('users', $users);
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

    /**
     * @return Event[] Returns an array of Event objects
     */
    public function findApprovedEventsByDateUserAndDepartment(\Datetime $startDate = null, \DateTime $endDate = null, User $user = null, Department $department = null): array
    {
        $qb = $this->createQueryBuilder('e');
        if ( null !== $user ) {
            $qb->innerJoin('e.user', 'u', 'WITH', 'e.user = u.id')
               ->andWhere('e.user  = :user')
               ->setParameter('user', $user);
        }
        if ( null !== $department ) {
            $qb->innerJoin('e.user', 'u', 'WITH', 'e.user = u.id')
               ->andWhere('u.department = :department')
               ->setParameter('department', $department);
        }
        if ( null !== $startDate   ) {
            $qb->andWhere('e.startDate >= :startDate')
               ->setParameter('startDate', $startDate);
        }
        if ( null !== $endDate ) {
            $qb->andWhere('e.endDate < :endDate')
                ->setParameter('endDate', $endDate);
        }
        $qb->andWhere('e.status = :status')
           ->setParameter('status', Status::APPROVED );
        $qb->orderBy('e.id', 'ASC');

        return $qb->getQuery()->getResult();
    }
}
