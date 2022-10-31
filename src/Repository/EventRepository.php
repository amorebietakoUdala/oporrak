<?php

namespace App\Repository;

use App\Entity\Department;
use App\Entity\Event;
use App\Entity\EventType;
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
    public function findUserEventsCurrentYearAndType(User $user, int $year, $type = null, $onlyHalfDays = false, bool $activated = true)
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
            ->andWhere('u.activated = :activated')
            ->setParameter('activated', $activated)
            //            ->setMaxResults(10)
        ;
        return $qb->getQuery()->getResult();
    }

    /**
     * @return Event[] Returns an array of Event objects
     */
    public function findUserEventsBeetweenDates($user, $startDate, $endDate = null, bool $activated = true)
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
            ->andWhere('u.activated = :activated')
            ->setParameter('activated', $activated)
            //            ->setMaxResults(10)
        ;
        return $qb->getQuery()->getResult();
    }

    /**
     * Find all the effective events of the year for the users, including next year events with previous year days.
     * 
     * @param $users --------------- Users to look for his/her events
     * @param $year --------------- Effective year of the events. 
     * @param $eventType ---------- Filter for EventType.
     * @param $includeNotApproved - Include not approved events.
     * @param $activated - Show activated or not activated.
     * 
     * @return Event[] Returns an array of Event objects
     */
    public function findEffectiveEventsOfTheYearForUsers(array $users, int $year, EventType $eventType = null, $includeNotApproved = true, bool $activated = true)
    {
        $thisYearStart = new DateTime("${year}-01-01");
        $thisYearEnd = new DateTime("${year}-12-31");
        $nextYear = $year + 1;
        $nextYearStart = new DateTime("${nextYear}-01-01");
        $nextYearEnd = new DateTime("${nextYear}-12-31");
        $condition = "(
            ( e.startDate >= :startDate AND e.endDate < :endDate AND ( e.usePreviousYearDays = :false OR e.usePreviousYearDays IS NULL ) ) 
            OR ( e.startDate >= :nextYearStartDate AND e.endDate < :nextYearEndDate AND e.usePreviousYearDays = :true )
            OR ( e.startDate < :nextYearStartDate AND e.endDate >= :nextYearStartDate AND ( e.usePreviousYearDays = :false OR e.usePreviousYearDays IS NULL ) )
            )";
            $qb = $this->createQueryBuilder('e')
                ->innerJoin('e.user', 'u', 'WITH', 'e.user = u.id')
                ->andWhere($condition)
                ->setParameter('startDate', $thisYearStart)
                ->setParameter('endDate', $thisYearEnd)
                ->setParameter('false', false)
                ->setParameter('nextYearStartDate', $nextYearStart)
                ->setParameter('nextYearEndDate', $nextYearEnd)
                ->setParameter('true', true);
            if (null !== $users) {
                $qb->andWhere('e.user in (:users)')
                    ->setParameter('users', $users);
            }
            if (null !== $eventType) {
                $qb->andWhere('e.type = :type')
                    ->setParameter('type', $eventType);
            }
            if (!$includeNotApproved) {
                $qb->andWhere('e.status != :status')
                    ->setParameter('status', Status::NOT_APPROVED);
            }
            $qb->orderBy('e.id', 'ASC')
                ->andWhere('u.activated = :activated')
                ->setParameter('activated', $activated)
            ;
        return $qb->getQuery()->getResult();
    }

    /**
     * Find all the effective events of the year for the user, including next year events with previous year days.
     * 
     * @param $user --------------- User to look for his/her events
     * @param $year --------------- Effective year of the events. 
     * @param $eventType ---------- Filter for EventType.
     * @param $includeNotApproved - Include not approved events.
     * 
     * @return Event[] Returns an array of Event objects
     */
    public function findEffectiveUserEventsOfTheYear(User $user, int $year, EventType $eventType = null, $includeNotApproved = true)
    {
        return $this->findEffectiveEventsOfTheYearForUsers([$user],$year,$eventType,$includeNotApproved);
    }

    /**
     * @return Event[] Returns an array of Event objects
     */
    public function findUserEventsOfTheYearWithPreviousYearDays(User $user, int $year, bool $previousYearDays = false, bool $activated = true)
    {
        $thisYearStart = new DateTime("${year}-01-01");
        $nextYear = $year+1;
        $nextYearStart = new DateTime("{$nextYear}-01-01");
        $thisYearEnd = new DateTime("${year}-12-31");
        $qb = $this->createQueryBuilder('e')
            ->innerJoin('e.user', 'u', 'WITH', 'e.user = u.id');
        if ($previousYearDays) {
            $condition = "
                e.startDate >= :startDate AND e.endDate < :endDate AND ( e.usePreviousYearDays = :true  ) 
                OR ( e.startDate < :startDate AND e.endDate >= :startDate )
                ";
            $qb->andWhere($condition)
                ->setParameter('true', true);
        } else {
            $condition = " 
                ( e.startDate >= :startDate AND e.endDate < :endDate AND ( e.usePreviousYearDays = :false OR e.usePreviousYearDays IS NULL ) ) 
                OR ( e.startDate <= :endDate AND e.endDate >= :nextYearStart )
                ";
            $qb->andWhere($condition)
                ->setParameter('false', false)
                ->setParameter('nextYearStart', $nextYearStart);
        }
        $qb->setParameter('startDate', $thisYearStart)
            ->setParameter('endDate', $thisYearEnd)
            ->andWhere('e.user = :user')
            ->setParameter('user', $user);
            
        $qb->orderBy('e.id', 'ASC')
            ->andWhere('u.activated = :activated')
            ->setParameter('activated', $activated)
        ;
        return $qb->getQuery()->getResult();
    }

    /**
     * @return Event[] Returns an array of Event objects
     */
    public function findByUsernamesAndBeetweenDates(array $usernames = null, $startDate, $endDate = null, bool $previousYearDays = false, bool $activated = true)
    {
        $qb = $this->createQueryBuilder('e')
            ->innerJoin('e.user', 'u', 'WITH', 'e.user = u.id');
        if ($previousYearDays) {
            $condition = "
                e.startDate >= :startDate AND e.endDate < :endDate AND ( e.usePreviousYearDays = :true  ) 
                ";
            $qb->andWhere($condition)
                ->setParameter('true', true);
        } else {
            $condition = " 
                ( e.startDate >= :startDate AND e.endDate < :endDate AND ( e.usePreviousYearDays = :false OR e.usePreviousYearDays IS NULL ) )
                OR ( e.startDate <= :endDate AND e.endDate > :endDate AND ( e.usePreviousYearDays = :false OR e.usePreviousYearDays IS NULL ) )
                ";
            $qb->andWhere($condition)
                ->setParameter('false', false);
        }            
        if (null !== $endDate) {
             $qb->setParameter('endDate', $endDate);
        }
        if (null !== $startDate) {
            $qb->setParameter('startDate', $startDate);
        }
        if (null !== $usernames) {
            $qb->andWhere('u.username in (:usernames)')
                ->setParameter('usernames', $usernames);
        }
        $qb->orderBy('e.id', 'ASC')
            ->andWhere('u.activated = :activated')
            ->setParameter('activated', $activated)
            //            ->setMaxResults(10)
        ;
        return $qb->getQuery()->getResult();
    }

    /**
     * @return Event[] Returns an array of Event objects
     */
    public function findByDepartmentAndUsersAndStatusBeetweenDates($startDate, $endDate, $department = null, array $users = null, $status = null, bool $activated = true)
    {
        $qb = $this->createQueryBuilder('e')
            ->innerJoin('e.user', 'u', 'WITH', 'e.user = u.id');
        $condition = " 
            ( e.startDate >= :startDate AND e.endDate < :endDate AND ( e.usePreviousYearDays = :false OR e.usePreviousYearDays IS NULL ) )
            OR ( e.startDate <= :endDate AND e.endDate > :endDate AND ( e.usePreviousYearDays = :false OR e.usePreviousYearDays IS NULL ) )
            OR ( e.startDate >= :startDate AND e.endDate < :endDate AND ( e.usePreviousYearDays = :true  ) )
            ";
        $qb->andWhere($condition)
            ->setParameter('false', false)
            ->setParameter('true', true)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);
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
            ->andWhere('u.activated = :activated')
            ->setParameter('activated', $activated)
        ;
        return $qb->getQuery()->getResult();
    }

    /**
     * @return Event[] Returns an array of Event objects
     */
    public function findByBossAndStatusBeetweenDates($boss = null, $excludedDepartment, $status = null, $startDate, $endDate = null, bool $activated = true)
    {
        $qb = $this->createQueryBuilder('e')
            ->innerJoin('e.user', 'u', 'WITH', 'e.user = u.id')
            ->andWhere('e.startDate >= :startDate')
            ->setParameter('startDate', $startDate)
            ->andWhere('u.activated = :activated')
            ->setParameter('activated', $activated)
        ;
        if (null !== $endDate) {
            $qb->andWhere('e.endDate < :endDate')
                ->setParameter('endDate', $endDate);
        }
        if (null !== $status) {
            $qb->andWhere('e.status = :status')
                ->setParameter('status', $status);
        }
        if (null !== $excludedDepartment) {
            $qb->andWhere('u.department != :department')
                ->setParameter('department', $excludedDepartment);
        }
        if (null !== $boss) {
            $qb->andWhere('u.boss = :boss')
                ->setParameter('boss', $boss);
        }
        $qb->orderBy('e.id', 'ASC')
            //            ->setMaxResults(10)
        ;
        return $qb->getQuery()->getResult();
    }

    /**
     * @return Event[] Returns an array of Event objects
     */
    public function findAllByStatusBeetweenDates($status, $startDate, $endDate = null, bool $activated = true)
    {
        $qb = $this->createQueryBuilder('e')
            ->innerJoin('e.user', 'u', 'WITH', 'e.user = u.id')
            ->andWhere('e.startDate >= :startDate')
            ->setParameter('startDate', $startDate)
            ->andWhere('e.status = :status')
            ->setParameter('status', $status)
            ->andWhere('u.activated = :activated')
            ->setParameter('activated', $activated);
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
    public function findOverlapingEventsNotOfCurrentUser(Event $event, bool $activated = true): array
    {
        $qb = $this->createQueryBuilder('e')
            ->innerJoin('e.user', 'u', 'WITH', 'e.user = u.id')
            ->innerJoin('u.department', 'd', 'WITH', 'u.department = d.id')
            ->andWhere('u.department = :department')
            ->setParameter('department', $event->getUser()->getDepartment())
            // Exclude self events
            ->andWhere('e.user <> :user')
            ->setParameter('user', $event->getUser())
            ->andWhere('u.activated = :activated')
            ->setParameter('activated', $activated);

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
    public function findApprovedEventsByDateUserAndDepartment(\Datetime $startDate = null, \DateTime $endDate = null, User $user = null, Department $department = null, bool $activated=true): array
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
           ->setParameter('status', Status::APPROVED )
           ->andWhere('u.activated = :activated')
           ->setParameter('activated', $activated);

        $qb->orderBy('e.id', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Find all the events of the usernames of that year including next year events with previous year days.
     * 
     * @param array $usernames Array of usernames to look for their events
     * @param $year Effective year of the events. 
     * 
     * @return Event[] Returns an array of Event objects
     */
    public function findEffectiveEventsOfTheYearByUsernames($year, array $usernames) {
        if (count($usernames) === 0) {
            return [];
        }
        $events = $this->findByUsernamesAndBeetweenDates($usernames,new \DateTime("${year}-01-01"), new \DateTime("${year}-12-31"));
        $nextYear = intval($year)+1;
        $eventsNextYearWithPreviousYearDays = $this->findByUsernamesAndBeetweenDates($usernames,new \DateTime("${nextYear}-01-01"), new \DateTime("${nextYear}-12-31"), true);
//        dd($events, $eventsNextYearWithPreviousYearDays);
        $events = array_merge($events, $eventsNextYearWithPreviousYearDays);

        return $events;
    }

}
