<?php

namespace App\Controller;

use App\Entity\Department;
use App\Entity\EventType;
use App\Entity\User;
use App\Entity\Status;
use App\Repository\AdditionalVacationDaysRepository;
use App\Repository\AntiquityDaysRepository;
use App\Repository\EventRepository;
use App\Repository\HolidayRepository;
use App\Repository\StatusRepository;
use App\Repository\WorkCalendarRepository;
use App\Services\DaysFormattingService;
use App\Services\StatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/api')]
#[IsGranted('ROLE_USER')]
class ApiController extends AbstractController
{

   public function __construct(
      private readonly AntiquityDaysRepository $adRepo, 
      private readonly EventRepository $eventRepo, 
      private readonly HolidayRepository $hollidayRepo, 
      private readonly WorkCalendarRepository $wcRepo, 
      private readonly StatsService $statsService, 
      private readonly StatusRepository $statusRepo, 
      private readonly AdditionalVacationDaysRepository $avdRepo,
      private readonly DaysFormattingService $daysFormattingService,
      private readonly int $unionHours
      )
   {
   }

   #[Route(path: '/holidays', name: 'api_getHolidays', methods: 'GET')]
   public function getHolidays(Request $request): Response
   {
      $year = $request->get('year');
      $startDate = $request->get('startDate');
      $endDate = $request->get('endDate');
      if (null !== $startDate) {
         $startDate = new \DateTime($startDate);
         if (null !== $endDate) {
            $endDate = new \DateTime($endDate);
         } else {
            $endDate = new \DateTime();
         }
         $holidays = $this->hollidayRepo->findHolidaysBetween($startDate, $endDate);
         return $this->json($holidays, 200, []);
      } elseif (null === $year) {
         $year = \DateTime::createFromFormat('Y', (new \DateTime())->format('Y'));
      }
      $holidays = $this->hollidayRepo->findBy(['year' => $year]);
      return $this->json($holidays, 200, []);
   }

   #[Route(path: '/{_locale}/my/remaining-days', name: 'api_get_my_remaining_days', methods: 'GET')]
    public function getMyRemainigDays(Request $request): Response
    {
      $year = ( null === $request->get('year') || $request->get('year') === '') ? (new \DateTime())->format('Y') : $request->get('year');
      /** @var User $user */
      $user = $this->getUser();
      $workCalendar = $this->wcRepo->findOneBy(['year' => $year]);
      if ( null == $workCalendar ) {
         return $this->render('calendar/_remainingDays.html.twig', [
            'isUnionDelegate' => $user->isUnionDelegate(),
            'year' => $year,
            'vacationDays' => null,
            'particularBusinessLeave' => null,
            'overtimeDays' => null,
            'antiquityDays' => null,
            'additionalVacationDays' => null,
            'unionHours' => null,

         ]);
      }
      $totals = $this->totalDaysForEachType($user,$year);

      $events = $this->eventRepo->findEffectiveUserEventsOfTheYear($user, $year, null, false);

      // We wan't to show the union hours in hours and not in days
      $byHours = [
         EventType::UNION_HOURS => true,
      ];
      $counters = $this->statsService->calculateStatsByUserAndEventType($events, $year, $byHours);
      if (count($counters)) {
         $statsByEventType = $counters[$user->getUsername()];
         foreach ($totals as $key => $value) {
            if (array_key_exists($key, $statsByEventType)) {
            $remaining[$key] = round($totals[$key] - $statsByEventType[$key], 6);
            } else {
            $remaining[$key] = round($totals[$key], 6);
            }
         }
      } else {
         $remaining = $totals;
      }

      $totalUnionHours = 0;
      if( array_key_exists(EventType::UNION_HOURS, $remaining) ) {
         $totalUnionHours = $remaining[EventType::UNION_HOURS];
         unset($remaining[EventType::UNION_HOURS]);
      }
      $formattedCounters = $this->statsService->formatCounterAsDaysHoursAndMinutes($remaining, $this->wcRepo->findOneBy(['year' => $year]));

      return $this->render('calendar/_remainingDays.html.twig', [
         'isUnionDelegate' => $user->isUnionDelegate(),
         'year' => $year,
         'vacationDays' => $formattedCounters[1],
         'particularBusinessLeave' => $formattedCounters[2],
         'overtimeDays' => $formattedCounters[3],
         'antiquityDays' => $formattedCounters[4],
         'additionalVacationDays' => $formattedCounters[5],
         'unionHours' => $this->daysFormattingService->formatHours($totalUnionHours),
      ]);


      return $this->json($formattedCounters);
    }

    private function totalDaysForEachType(User $user, $year) {
      $workCalendar = $this->wcRepo->findOneBy(['year' => $year]);
      $totals = $user->getTotals($workCalendar,$this->adRepo, $this->avdRepo, intval($year), $this->unionHours);
      return $totals;
    }

   #[Route(path: '/my/dates', name: 'api_get_my_dates', methods: 'GET')]
   public function getMyDates(Request $request): Response
   {
      $year = $request->get('year');
      if (null === $year) {
         $year = (new \DateTime())->format('Y');
      }
      /** @var User $user */
      $user = $this->getUser();
      $items = $this->eventRepo->findUserEventsOfTheYearWithPreviousYearDays($user, $year, false);
      $eventsWithLastYearDays = $this->eventRepo->findUserEventsOfTheYearWithPreviousYearDays($user, $year, true);
      $color = $this->getParameter('previousYearsDaysColor');
      $status = new Status();
      // Overwrite events color, for previous year days
      foreach ($eventsWithLastYearDays as $event) {
         $status->copy($event->getStatus());
         $status->setColor($color);
         $event->setStatus($status);
      }
      $items = array_merge($items, $eventsWithLastYearDays);
      $dates = [
         'total_count' => $items === null ? 0 : count($items),
         'items' => $items ?? []
      ];

      return $this->json($dates, 200, [], ['groups' => ['event']]);
   }

   #[Route(path: '/dates', name: 'api_get_dates', methods: 'GET')]
   public function getDepartmentDates(Request $request, EventRepository $repo): Response
   {
      $usersParam = $request->get('users');
      $calendar = $request->get('calendar') ?? 'department';
      $users = null;
      if ($usersParam !== null && $usersParam !== '') {
         $users = explode(',', (string) $usersParam);
      }
      $status = $request->get('status') === null ? null : intval($request->get('status'));
      /** @var User $me */
      $me = $this->getUser();
      $year = ( null === $request->get('year') || $request->get('year') === '') ? (new \DateTime())->format('Y') : $request->get('year');
      $nextYear = intVal($year) + 1;
      if ($request->get('department') !== null && ( $this->isGranted("ROLE_ADMIN") || $this->isGranted("ROLE_HHRR") )) {
         $department = $request->get('department');
      } elseif (!$this->isGranted("ROLE_ADMIN") && !$this->isGranted("ROLE_HHRR")) {
         $department = $me->getDepartment();
      } else {
         $department = null;
      }
      $items = $repo->findByDepartmentAndUsersAndStatusBeetweenDates(new \DateTime("$year-01-01"), new \DateTime("$nextYear-01-01"), $department, $users, $status);
      /** If he/she has role boss and it's department calendar, adds his/her workers events to the list */
      if (in_array('ROLE_BOSS', $me->getRoles()) && $calendar === 'department' && $usersParam === null ) {
         $workers = $repo->findByBossAndStatusBeetweenDates($department, new \DateTime("$year-01-01"), $me, $status, new \DateTime("$nextYear-01-01"));
         $items = array_merge($items, $workers);
      }
      $dates = [
         'total_count' => $items === null ? 0 : count($items),
         'items' => $items ?? []
      ];

      return $this->json($dates, 200, [], ['groups' => ['event']]);
   }

   #[Route(path: '/department/{id}/users', name: 'api_get_department_users', methods: 'GET', options: ['expose' => true])]
   public function departmentUsers(Department $deparment)
   {
      $users = $deparment->getUsers()->toArray();
      return $this->json($users, 200, [], ['groups' => ['list']]);
   }

   #[Route(path: '/{_locale}/user/stats', name: 'api_get_user_stats', methods: 'GET')]
   public function getUserStats(Request $request) {
      $users = (null === $request->get('users') || $request->get('users') === '') ? [] : explode(",",(string) $request->get('users'));
      $colors = (null === $request->get('colors') || $request->get('colors') === '') ? [] : explode(",",(string) $request->get('colors'));
      $json = (null === $request->get('json') || $request->get('json') === '') ? false : boolval($request->get('json'));
      $year = ( null === $request->get('year') || $request->get('year') === '') ? (new \DateTime())->format('Y') : $request->get('year');
      $userColors = $this->createArray($users, $colors);
      $events = $this->eventRepo->findEffectiveEventsOfTheYearByUsernames($year,$users);
      $stats = $this->statsService->calculateStatsByUserAndStatus($events, $year, $users);
      $workCalendar = $this->wcRepo->findOneBy(["year" => $year]);
      if ($workCalendar === null) {
         return $this->render('calendar/_userLegend.html.twig',[
            'stats' => null
         ]);
      }

      $stats = $this->statsService->formatStatsAsDaysHoursAndMinutes($stats, $workCalendar);

      $statuses = $this->statusRepo->getArrayOfColors();
      if ($json) {
         return $this->json($stats, 200, [], ['groups' => ['event']]);
      }
      return $this->render('calendar/_userLegend.html.twig',[
         'stats' => $stats,
         'userColors' => $userColors,
         'statuses' => $statuses,
      ]);
   }

   private function createArray(array $users, array $colors) {
      $userColors = [];
      foreach ($users as $key => $value) {
         $userColors[$value] = $colors[$key];
      }
      return $userColors;
   }

    #[Route(path: '/{_locale}/department/{department}/overlaps', name: 'api_department_overlaps', methods: 'GET')]
    public function reservedEvents(Request $request, Department|null $department = null) {
      $year = ( null === $request->get('year') || $request->get('year') === '') ? (new \DateTime())->format('Y') : $request->get('year');
      $json = (null === $request->get('json') || $request->get('json') === '') ? false : boolval($request->get('json'));
      $wc = $this->wcRepo->findOneBy(["year" => $year]);
      // Even if we show this year calendar we show next years event with usePreviousYearDays set to true
      // This allows to approve all dates of this year count, even they are reserved on next year.
      // So take next year`s deadline date to show overlaps and unapproved events.
      $deadlineNextYear = $wc->getDeadlineNextYear();
      $reservedEvents = [];
      $overlaps = [];
      if ( $department !== null) {
          $reservedEvents = $this->eventRepo->findByDepartmentAndUsersAndStatusBeetweenDates(
            date_create_from_format('Y-m-d',$year.'-01-01'), 
            $deadlineNextYear,
            $department, 
              null, 
              Status::RESERVED 
          );
          $reservedEventsFromMyMinions = $this->eventRepo->findByBossAndStatusBeetweenDates($department, date_create_from_format('Y-m-d',$year.'-01-01'), $this->getUser(), Status::RESERVED, $deadlineNextYear);
          $reservedEvents = array_merge($reservedEvents, $reservedEventsFromMyMinions);
          $overlaps = [];
          foreach ($reservedEvents as $event) {
              $overlaps[$event->getId()] = $this->eventRepo->findOverlapingEventsNotOfCurrentUser($event);
          }
      }
      if ($json) {
         return $this->json([
            'reservedEvents' => $reservedEvents,
            'overlaps' => $overlaps,
        ], 200, [], ['groups' => ['event']]);
      }
      return $this->render('calendar/_reservedEvents.html.twig',[
          'reservedEvents' => $reservedEvents,
          'overlaps' => $overlaps,
      ]);
  }

  #[Route(path: '/{_locale}/summary', name: 'api_getSummary', methods: 'GET')]
  public function summary(Request $request): Response
  {
     $year = $request->get('year');
     $workCalendar = $this->wcRepo->findOneBy(['year' => $year]);
     $antiquityDays = $this->adRepo->findAll();
     $additionalVacationDays = $this->avdRepo->findAll();
     return $this->render('calendar/_summary.html.twig', [
        'year' => $year,
        'workCalendar' => $workCalendar,
        'antiquityDays' => $antiquityDays,
        'additionalVacationDays' => $additionalVacationDays,
     ]);
  }

}
