<?php

namespace App\Controller;

use App\Entity\Department;
use App\Entity\EventType;
use App\Entity\User;
use App\Entity\Status;
use App\Entity\WorkCalendar;
use App\Repository\AntiquityDaysRepository;
use App\Repository\EventRepository;
use App\Repository\HolidayRepository;
use App\Repository\StatusRepository;
use App\Repository\WorkCalendarRepository;
use App\Services\StatsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @Route("/api")
 * @IsGranted("ROLE_USER")
 */
class ApiController extends AbstractController
{

   private AntiquityDaysRepository $adRepo;
   private EventRepository $eventRepo;
   private HolidayRepository $hollidayRepo;
   private WorkCalendarRepository $wcRepo;
   private StatsService $statsService;
   private StatusRepository $statusRepo;

   public function __construct(AntiquityDaysRepository $adRepo, EventRepository $eventRepo, HolidayRepository $hollidayRepo, WorkCalendarRepository $wcRepo, StatsService $statsService, StatusRepository $statusRepo)
   {
      $this->adRepo = $adRepo;
      $this->eventRepo = $eventRepo;
      $this->hollidayRepo = $hollidayRepo;
      $this->wcRepo = $wcRepo;
      $this->statsService = $statsService;
      $this->statusRepo = $statusRepo;
   }

   /**
    * @Route("/holidays", name="api_getHolidays", methods="GET")
    */
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

   /**
    * @Route("/my/remaining-days", name="api_get_my_remaining_days", methods="GET")
    */
    public function getMyRemainigDays(Request $request): Response
    {
      $year = ( null === $request->get('year') || $request->get('year') === '') ? (new \DateTime())->format('Y') : $request->get('year');
      /** @var User $user */
      $user = $this->getUser();
      $totals = $this->totalDaysForEachType($user,$year);
      $events = $this->eventRepo->findEffectiveUserEventsOfTheYear($user, $year);
      $counters = $this->statsService->calculateStatsByUserAndEventType($events);
      if (count($counters)) {
         $statsByEventType = $counters[$user->getUsername()];
         foreach ($totals as $key => $value) {
            if (array_key_exists($key, $statsByEventType)) {
            $remaining[$key] = $totals[$key] - $statsByEventType[$key];
            } else {
            $remaining[$key] = $totals[$key];
            }
         }
      } else {
         $remaining = $totals;
      }
      return $this->json($remaining);
    }

    private function totalDaysForEachType(User $user, $year) {
      $workCalendar = $this->wcRepo->findOneBy(['year' => $year]);
      $totals = $user->getTotals($workCalendar,$this->adRepo);
      return $totals;
    }

   /**
    * @Route("/my/dates", name="api_get_my_dates", methods="GET")
    */
   public function getMyDates(Request $request): Response
   {
      $year = $request->get('year');
      if (null === $year) {
         $year = \DateTime::createFromFormat('Y', new \DateTime())->format('Y');
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
         'items' => $items === null ? [] : $items
      ];

      return $this->json($dates, 200, [], ['groups' => ['event']]);
   }

   /**
    * @Route("/dates", name="api_get_dates", methods="GET")
    */
   public function getDepartmentDates(Request $request, EventRepository $repo): Response
   {
      $usersParam = $request->get('users');
      $calendar = $request->get('calendar') !== null ? $request->get('calendar') : 'department';
      $users = null;
      if ($usersParam !== null && $usersParam !== '') {
         $users = explode(',', $usersParam);
      }
      $status = $request->get('status') === null ? null : intval($request->get('status'));
      /** @var User $me */
      $me = $this->getUser();
      $year = ( null === $request->get('year') || $request->get('year') === '') ? (new \DateTime())->format('Y') : $request->get('year');
      $nextYear = intVal($year) + 1;
      if ($request->get('department') !== null && (in_array('ROLE_ADMIN', $me->getRoles()) || in_array('ROLE_HHRR', $me->getRoles()))) {
         $department = $request->get('department');
      } elseif (!in_array('ROLE_ADMIN', $me->getRoles()) && !in_array('ROLE_HHRR', $me->getRoles())) {
         $department = $me->getDepartment();
      } else {
         $department = null;
      }
      $items = $repo->findByDepartmentAndUsersAndStatusBeetweenDates(new \DateTime("$year-01-01"), new \DateTime("$nextYear-01-01"), $department, $users, $status);
      /** If he/she has role boss and it's department calendar, adds his/her workers events to the list */
      if (in_array('ROLE_BOSS', $me->getRoles()) && $calendar === 'department' && $usersParam === null ) {
         $workers = $repo->findByBossAndStatusBeetweenDates($me, $department, $status, new \DateTime("$year-01-01"), new \DateTime("$nextYear-01-01"));
         $items = array_merge($items, $workers);
      }
      $dates = [
         'total_count' => $items === null ? 0 : count($items),
         'items' => $items === null ? [] : $items
      ];

      return $this->json($dates, 200, [], ['groups' => ['event']]);
   }

   /**
    * @Route("/work_calendar", name="api_getWorkCalendar", methods="GET")
    */
   public function workCalendar(Request $request, EntityManagerInterface $em)
   {
      $year = $request->get('year');
      $workCalendar = $em->getRepository(WorkCalendar::class)->findOneBy(['year' => $year]);
      return $this->json($workCalendar, 200, [],);
   }

   /**
    * @Route("/department/{id}/users", name="api_get_department_users", methods="GET", options = { "expose" = true })
    */
   public function departmentUsers(Department $deparment)
   {
      $users = $deparment->getUsers()->toArray();
      return $this->json($users, 200, [], ['groups' => ['list']]);
   }

   /**
    * @Route("/{_locale}/user/stats", name="api_get_user_stats", methods="GET")
    */
   public function getUserStats(Request $request) {
      $users = (null === $request->get('users') || $request->get('users') === '') ? [] : explode(",",$request->get('users'));
      $colors = (null === $request->get('colors') || $request->get('colors') === '') ? [] : explode(",",$request->get('colors'));
      $json = (null === $request->get('json') || $request->get('json') === '') ? false : boolval($request->get('json'));
      $year = ( null === $request->get('year') || $request->get('year') === '') ? (new \DateTime())->format('Y') : $request->get('year');
      $userColors = $this->createArray($users, $colors);
      $events = $this->eventRepo->findEffectiveEventsOfTheYearByUsernames($year,$users);
      $stats = $this->statsService->calculateStatsByUserAndStatus($events, $year);
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

    /**
     * @Route("/{_locale}/department/{department}/overlaps", name="api_department_overlaps", methods="GET")
     */
    public function reservedEvents(Request $request, Department $department = null) {
      $year = ( null === $request->get('year') || $request->get('year') === '') ? (new \DateTime())->format('Y') : $request->get('year');
      $json = (null === $request->get('json') || $request->get('json') === '') ? false : boolval($request->get('json'));
      $reservedEvents = [];
      $overlaps = [];
      if ( $department !== null) {
          $reservedEvents = $this->eventRepo->findByDepartmentAndUsersAndStatusBeetweenDates(
            date_create_from_format('Y-m-d',$year.'-01-01'), 
            date_create_from_format('Y-m-d',(intval($year)+1).'-01-01'),
            $department, 
              null, 
              Status::RESERVED 
          );
          $reservedEventsFromMyMinions = $this->eventRepo->findByBossAndStatusBeetweenDates($this->getUser(),$department,Status::RESERVED, date_create_from_format('Y-m-d',$year.'-01-01'), date_create_from_format('Y-m-d',(intval($year)+1).'-01-01'));
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
}
