<?php

namespace App\Repository;

use App\Entity\Department;
use App\Entity\Event;
use App\Entity\EventType;
use App\Entity\Status;
use App\Entity\User;
use DateInterval;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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
        return $this->findUserEventsBeetweenDatesAndType($user, $startDate, $endDate, $type, $onlyHalfDays, $activated);
    }

    public function findUserEventsBeetweenDatesAndType (User $user, DateTime $startDate, DateTime $endDate, $type = null, $onlyHalfDays = false, bool $activated = true) {
        $qb = $this->createQueryBuilder('e')
            ->innerJoin('e.user', 'u', 'WITH', 'e.user = u.id');
        $qb = $this->andWhereStartDateGTE($qb, $startDate);
        $qb = $this->andWhereEndDateLTE($qb, $endDate);
        $qb = $this->andWhereUser($qb, $user);
        if ($onlyHalfDays) {
            $qb = $this->andWhereHalfDaysEqual($qb, $onlyHalfDays);
        }
        if (null !== $type) {
            $qb = $this->andWhereEventTypeEqual($qb, $type);
        }
        $qb = $this->andWhereActivated($qb, $activated);
        $qb = $this->orderByIdAsc($qb);
        ;
        return $qb->getQuery()->getResult();
    }

    /**
     * Return the events of the user filter by start and endDate and the activation status of the user
     * 
     * @param User $user ---------------- User to look for his/her events
     * @param DateTime $startDate ------- The start date to find events from (included) 
     * @param DateTime $endDate --------- The end date to find events to (NOT included) 
     * @param bool $activated ----------- If activated true filters only activated users. If false only not activated users
     * 
     * @return Event[] Returns an array of Event objects
     */
    public function findUserEventsBeetweenDates(User $user, DateTime $startDate, DateTime $endDate = null, bool $activated = true): array
    {
        $qb = $this->createQueryBuilder('e')->innerJoin('e.user', 'u', 'WITH', 'e.user = u.id');
        $qb = $this->andWhereUser($qb, $user);
        $qb = $this->andWhereStartDateGTE($qb, $startDate);
        if (null !== $endDate) {
            $qb = $this->andWhereEndDateLTE($qb, $endDate);
        }
        $qb = $this->andWhereActivated($qb, $activated);
        $qb = $this->orderByIdAsc($qb);
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
        $qb = $this->findEffectiveEventsOfTheYearForUsersQB( $year, $users, null, $eventType, null, $includeNotApproved, $activated);
        return $qb->getQuery()->getResult();
    }

    public function findEffectiveEventsOfTheYearForUsersStartingFromDate(array $users, int $year, DateTime $startingFromDate, EventType $eventType = null, $includeNotApproved = true, bool $activated = true)
    {
        $qb = $this->findEffectiveEventsOfTheYearForUsersQB($year, $users, null, $eventType, null, $includeNotApproved, $activated);
        $qb = $this->andWhereStartingDateGTE($qb, $startingFromDate);
        return $qb->getQuery()->getResult();
    }

    public function findEffectiveEventsOfTheYearForUsersEndingAtDate(array $users, int $year, DateTime $endingFromDate, EventType $eventType = null, $includeNotApproved = true, bool $activated = true)
    {
        $qb = $this->findEffectiveEventsOfTheYearForUsersQB($year, $users, null, $eventType,  null, $includeNotApproved, $activated);
        $qb = $this->andWhereEndingDateLTE($qb, $endingFromDate);
        return $qb->getQuery()->getResult();
    }

    private function findEffectiveEventsOfTheYearForUsersQB(int $year, ?array $users = null, Department $department = null, EventType $eventType = null, int $status = null, $includeNotApproved = true, bool $activated = true): QueryBuilder
    {
        $qb = $this->createQueryBuilder('e')
            ->innerJoin('e.user', 'u', 'WITH', 'e.user = u.id');
        $qb = $this->andWhereIncludeEffectiveEventsOfTheYear($qb, $year);
        if (null !== $users) {
            $qb = $this->andWhereUsersIn($qb, $users);
        }
        if (null !== $department) {
            $qb = $this->andWhereDepartmentEqual($qb, $department);
        }
        if (null !== $eventType) {
            $qb = $this->andWhereEventTypeEqual($qb, $eventType);
        }
        if (null !== $status) {
            $qb = $this->andWhereStatusEqual($qb, $status);
        } else if (!$includeNotApproved) {
            $qb = $this->andWhereStatusNotEqual($qb, Status::NOT_APPROVED);
        }
        $qb = $this->andWhereActivated($qb, $activated);
        $qb = $this->orderByIdAsc($qb);
        return $qb;
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
        if (null !== $user->getStartDate() && intval($user->getStartDate()->format('Y')) === $year ) {
            return $this->findEffectiveEventsOfTheYearForUsersStartingFromDate([$user],$year, $user->getStartDate(),$eventType,$includeNotApproved);    
        }
        if (null !== $user->getEndDate() && intval($user->getEndDate()->format('Y')) === $year) {
            return $this->findEffectiveEventsOfTheYearForUsersEndingAtDate([$user],$year, $user->getEndDate(),$eventType,$includeNotApproved);    
        }
        return $this->findEffectiveEventsOfTheYearForUsers([$user],$year,$eventType,$includeNotApproved);
    }

    /**
     * @return Event[] Returns an array of Event objects
     */
    public function findUserEventsOfTheYearWithPreviousYearDays(User $user, int $year, bool $previousYearDays = false, bool $activated = true)
    {
        $qb = $this->findUserEventsOfTheYearWithPreviousYearDaysQB($user,$year, $previousYearDays, $activated);
        // TODO add filters for users start date and endDate

        return $qb->getQuery()->getResult();
    }

    public function findUserEventsOfTheYearWithPreviousYearDaysQB(User $user, int $year, bool $previousYearDays = false, bool $activated = true) {
        $qb = $this->createQueryBuilder('e')
            ->innerJoin('e.user', 'u', 'WITH', 'e.user = u.id');
        $qb = $this->andWhereUser($qb, $user);
        $thisYearStart = new DateTime("$year-01-01");
        $thisYearEnd = new DateTime("$year-12-31");
        /** We ignore events before user start date and after end date */
        if ( $user->isThisYearFirst($year) ) {
            $thisYearStart = $user->getStartDate();
        }
        if ( $user->isThisYearLast($year) ) {
            $thisYearEnd = $user->getEndDate();
        }
        $qb = $this->includeEventsWithPreviousYearDays($qb, $year, $previousYearDays, $thisYearStart, $thisYearEnd);
        $qb = $this->andWhereActivated($qb,$activated);
        $qb = $this->orderByIdAsc($qb);
        return $qb;
    }

    /**
     * @return Event[] Returns an array of Event objects
     */
    public function findByUsernamesAndBeetweenDates($startDate, array $usernames = null, $endDate = null, bool $previousYearDays = false, bool $activated = true)
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
                ( e.startDate >= :startDate AND e.endDate <= :endDate AND ( e.usePreviousYearDays = :false OR e.usePreviousYearDays IS NULL ) )
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
            $qb = $this->andWhereUsernamesIn($qb, $usernames);
        }
        $qb = $this->andExcludeBeforeUserStartAndEndDate($qb);
        $qb = $this->andWhereActivated($qb,$activated);
        $qb = $this->orderByIdAsc($qb);

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
        // If user has start date or end date we 
        $qb = $this->andExcludeBeforeUserStartAndEndDate($qb);
        if (null !== $users) {
            $qb = $this->andWhereUsersIn($qb,$users);
        }
        if (null !== $status) {
            $qb = $this->andWhereStatusEqual($qb, $status);
        }
        if (null !== $department) {
            $qb = $this->andWhereDepartmentEqual($qb, $department);
        }
        $qb = $this->andWhereActivated($qb,$activated);
        $qb = $this->orderByIdAsc($qb);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Event[] Returns an array of Event objects
     */
    public function findByBossAndStatusBeetweenDates($excludedDepartment, $startDate, $boss = null, $status = null, $endDate = null, bool $activated = true)
    {
        $qb = $this->createQueryBuilder('e')
            ->innerJoin('e.user', 'u', 'WITH', 'e.user = u.id');
        $qb = $this->andWhereStartDateGTE($qb, $startDate);
        if (null !== $endDate) {
            $qb = $this->andWhereEndDateLTE($qb, $endDate);
        }
        if (null !== $status) {
            $qb = $this->andWhereStatusEqual($qb, $status);
        }
        if (null !== $excludedDepartment) {
            $qb = $this->andWhereDepartmentNotEqual($qb, $excludedDepartment);
        }
        if (null !== $boss) {
            $qb = $this->andWhereBossEqual($qb, $boss);
        }
        $qb = $this->andExcludeBeforeUserStartAndEndDate($qb);
        $qb = $this->andWhereActivated($qb,$activated);
        $qb = $this->orderByIdAsc($qb);
        ;
        return $qb->getQuery()->getResult();
    }

    /**
     * @return Event[] Returns an array of Event objects
     */
    public function findAllByStatusBeetweenDates($status, $startDate, $endDate = null, bool $activated = true)
    {
        $qb = $this->createQueryBuilder('e')
            ->innerJoin('e.user', 'u', 'WITH', 'e.user = u.id');
        $qb = $this->andWhereStartDateGTE($qb, $startDate);
        $qb = $this->andWhereStatusEqual($qb, $status);
        $qb = $this->andWhereActivated($qb, $activated);
        if (null !== $endDate) {
            $qb = $this->andWhereEndDateLTE($qb, $endDate);
        }
        $qb = $this->orderByIdAsc($qb);
        ;
        return $qb->getQuery()->getResult();
    }

    /**
     * Find all the events of the specified status before the given date.
     * 
     * @return Event[] Returns an array of Event objects
     */
    public function findAllByStatusAskedBeforeDate($status, \DateTime $date)
    {
        $qb = $this->createQueryBuilder('e')
            ->andWhere('e.askedAt < :date')
            ->setParameter('date', $date);
        $qb = $this->andWhereStatusEqual($qb, $status);
        $qb = $this->orderByIdAsc($qb);
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
            ->innerJoin('u.department', 'd', 'WITH', 'u.department = d.id');
        $qb = $this->andWhereDepartmentEqual($qb, $event->getUser()->getDepartment());
        // Exclude self events
        $qb = $this->andWhereUserNotEqual($qb, $event->getUser());
        $where = '(' .
            '(e.startDate >= :d2s and e.endDate <= :d2e ) or ' .
            '(e.startDate <= :d2s and e.endDate >= :d2e ) or ' .
            '(e.startDate >= :d2s and e.startDate <= :d2e ) or ' .
            '(e.endDate >= :d2s and e.endDate <= :d2e ))';
        $qb->andWhere($where)
            ->setParameter('d2s', $event->getStartDate())
            ->setParameter('d2e', $event->getEndDate());
        $qb = $this->andExcludeBeforeUserStartAndEndDate($qb);
        $qb = $this->andWhereActivated($qb, $activated);
        $qb = $this->orderByIdAsc($qb);
        return $qb->getQuery()->getResult();
    }

    /**
     * @return Event[] Returns an array of Event objects
     */
    public function findApprovedEventsByDateUserAndDepartment(\Datetime $startDate = null, \DateTime $endDate = null, User $user = null, Department $department = null, bool $activated=true): array
    {
        $qb = $this->createQueryBuilder('e');
        if ( null !== $user ) {
            $qb->innerJoin('e.user', 'u', 'WITH', 'e.user = u.id');
            $qb = $this->andWhereUser($qb, $user);
        }
        if ( null !== $department ) {
            $qb->innerJoin('e.user', 'u', 'WITH', 'e.user = u.id');
            $qb = $this->andWhereDepartmentEqual($qb, $department);
        }
        if ( null !== $startDate   ) {
            $qb = $this->andWhereStartDateGTE($qb, $startDate);
        }
        if ( null !== $endDate ) {
            $qb = $this->andWhereEndDateLTE($qb, $endDate);
        }
        $qb = $this->andWhereStatusEqual($qb, Status::APPROVED);
        $qb = $this->andWhereActivated($qb, $activated);
        $qb = $this->orderByIdAsc($qb);
        return $qb->getQuery()->getResult();
    }

    public function findEventsByYearUserAndDepartment(int $year, User $user = null, Department $department = null, int $status = null, bool $activated=true): array
    {
        $users = ( null !== $user ) ? [$user] : null;
        $qb = $this->findEffectiveEventsOfTheYearForUsersQB($year, $users, $department, null, $status, false, $activated);
        $qb = $this->orderByIdAsc($qb);
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
        $events = $this->findByUsernamesAndBeetweenDates(new \DateTime("$year-01-01"), $usernames, new \DateTime("$year-12-31"));
        $nextYear = intval($year)+1;
        $eventsNextYearWithPreviousYearDays = $this->findByUsernamesAndBeetweenDates(new \DateTime("$nextYear-01-01"), $usernames, new \DateTime("$nextYear-12-31"), true);
        $events = array_merge($events, $eventsNextYearWithPreviousYearDays);

        return $events;
    }

    public function findAllReservedAndAskedDaysAgo($daysAgo) {
        $events = null;
        $beforeDate = (new \DateTime())->sub(new DateInterval('P'.$daysAgo.'D'));
        $events = $this->findAllByStatusAskedBeforeDate(Status::RESERVED, $beforeDate);
        return $events;

    }

    private function orderByIdAsc(QueryBuilder $qb): QueryBuilder {
        return $qb->orderBy('e.id', 'ASC');
    }

    private function andWhereUser(QueryBuilder $qb, User $user): QueryBuilder {
        return $qb->andWhere('e.user = :user')
            ->setParameter('user', $user);
    }

    private function andWhereUserNotEqual(QueryBuilder $qb, User $user): QueryBuilder {
        return $qb->andWhere('e.user <> :user')
            ->setParameter('user', $user);
    }

    private function andWhereActivated(QueryBuilder $qb, bool $activated): QueryBuilder {
        return $qb->andWhere('u.activated = :activated')
            ->setParameter('activated', $activated);
    }

    private function andWhereStatusEqual(QueryBuilder $qb, int $status): QueryBuilder {
        return $qb->andWhere('e.status = :status')
            ->setParameter('status', $status);
    }

    private function andWhereStatusNotEqual(QueryBuilder $qb, int $status): QueryBuilder {
        return $qb->andWhere('e.status != :status')
            ->setParameter('status', $status);
    }

    private function andWhereEventTypeEqual(QueryBuilder $qb, EventType $eventType): QueryBuilder {
        return $qb->andWhere('e.type = :type')
            ->setParameter('type', $eventType);
    }

    private function andWhereUsersIn(QueryBuilder $qb, array $users): QueryBuilder {
        return $qb->andWhere('e.user in (:users)')
            ->setParameter('users', $users);
    }

    private function andWhereStartDateGTE(QueryBuilder $qb, DateTime $startDate): QueryBuilder {
        return $qb->andWhere('e.startDate >= :startDate')
            ->setParameter('startDate', $startDate);
    }

    private function andWhereStartingDateGTE(QueryBuilder $qb, DateTime $startingDate): QueryBuilder {
        return $qb->andWhere('e.startDate >= :startingDate')
            ->setParameter('startingDate', $startingDate);
    }

    private function andWhereEndDateLTE(QueryBuilder $qb, DateTime $endDate): QueryBuilder {
        return $qb->andWhere('e.endDate <= :endDate')
            ->setParameter('endDate', $endDate);
    }

    private function andWhereEndingDateLTE(QueryBuilder $qb, DateTime $endingDate): QueryBuilder {
        return $qb->andWhere('e.endDate <= :endingDate')
            ->setParameter('endingDate', $endingDate);
    }

    private function andWhereIncludeEffectiveEventsOfTheYear(QueryBuilder $qb, int $year) {
        $thisYearStart = new DateTime("$year-01-01");
        $thisYearEnd = new DateTime("$year-12-31");
        $nextYear = $year + 1;
        $nextYearStart = new DateTime("$nextYear-01-01");
        $nextYearEnd = new DateTime("$nextYear-12-31");
        $condition = "(
            ( e.startDate >= :startDate AND e.endDate <= :endDate AND ( e.usePreviousYearDays = :false OR e.usePreviousYearDays IS NULL ) ) 
            OR ( e.startDate >= :nextYearStartDate AND e.endDate <= :nextYearEndDate AND e.usePreviousYearDays = :true )
            OR ( e.startDate < :nextYearStartDate AND e.endDate >= :nextYearStartDate AND ( e.usePreviousYearDays = :false OR e.usePreviousYearDays IS NULL ) )
            )";
        $qb->andWhere($condition);
        $qb->setParameter('startDate', $thisYearStart)
        ->setParameter('endDate', $thisYearEnd)
        ->setParameter('false', false)
        ->setParameter('nextYearStartDate', $nextYearStart)
        ->setParameter('nextYearEndDate', $nextYearEnd)
        ->setParameter('true', true);
        return $qb;        
    }

    private function includeEventsWithPreviousYearDays(QueryBuilder $qb, int $year, bool $previousYearDays, DateTime $startDate, DateTime $endDate): QueryBuilder {
        $nextYear = $year+1;
        $nextYearStart = new DateTime("$nextYear-01-01");
        if ($previousYearDays) {
            $condition = "
                e.startDate >= :startDate AND e.endDate < :endDate AND ( e.usePreviousYearDays = :true  ) 
                OR ( e.startDate < :startDate AND e.endDate >= :startDate )
                ";
            $qb->andWhere($condition)
                ->setParameter('true', true);

        } else {
            $condition = " 
                ( e.startDate >= :startDate AND e.endDate <= :endDate AND ( e.usePreviousYearDays = :false OR e.usePreviousYearDays IS NULL ) ) 
                OR ( e.startDate <= :endDate AND e.endDate >= :nextYearStart )
                ";
            $qb->andWhere($condition)
                ->setParameter('false', false)
                ->setParameter('nextYearStart', $nextYearStart);
        }
        $qb->setParameter('startDate', $startDate)
           ->setParameter('endDate', $endDate);
        return $qb;
    }

    private function andWhereHalfDaysEqual(QueryBuilder $qb, $halfDays): QueryBuilder {
        return             $qb->andWhere('e.halfDay = :halfDay')
        ->setParameter('halfDay', $halfDays);

    }

    private function andWhereUsernamesIn(QueryBuilder $qb, array $usernames): QueryBuilder {
        $qb->andWhere('u.username in (:usernames)')
            ->setParameter('usernames', $usernames);
        return $qb;
    }

    private function andWhereDepartmentEqual(QueryBuilder $qb, $department): QueryBuilder {
        return $qb->andWhere('u.department = :department')
            ->setParameter('department', $department);
    }

    private function andWhereDepartmentNotEqual(QueryBuilder $qb, $department): QueryBuilder {
        return $qb->andWhere('u.department != :department')
            ->setParameter('department', $department);
    }

    private function andWhereBossEqual (QueryBuilder $qb, User $boss): QueryBuilder {
        return $qb->andWhere('u.boss = :boss')
        ->setParameter('boss', $boss);
    }

    private function andExcludeBeforeUserStartAndEndDate (QueryBuilder $qb): QueryBuilder {
        $qb->andWhere('( u.startDate IS NULL OR ( u.startDate IS NOT NULL and e.startDate >= u.startDate ))');
        $qb->andWhere('( u.endDate IS NULL OR ( u.endDate IS NOT NULL and e.startDate <= u.endDate ))');
        return $qb;
    }
}
