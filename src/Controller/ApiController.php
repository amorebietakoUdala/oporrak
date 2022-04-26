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

   public function __construct(AntiquityDaysRepository $adRepo, EventRepository $eventRepo, HolidayRepository $hollidayRepo, WorkCalendarRepository $wcRepo, StatsService $statsService)
   {
      $this->adRepo = $adRepo;
      $this->eventRepo = $eventRepo;
      $this->hollidayRepo = $hollidayRepo;
      $this->wcRepo = $wcRepo;
      $this->statsService = $statsService;
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
       $year = $request->get('year');
       if (null === $year) {
          $year = \DateTime::createFromFormat('Y', new \DateTime())->format('Y');
       }
       /** @var User $user */
       $user = $this->getUser();
       $antiquityDays = $this->adRepo->findAntiquityDaysForYearsWorked($user->getYearsWorked());
       $totalAntiquityDays = $antiquityDays->getVacationDays();
       $workCalendar = $this->wcRepo->findOneBy(['year' => $year]);
       $totalVacationDays = $workCalendar->getVacationDays();
       $totalParticularBussinessLeaveDays = $workCalendar->getParticularBusinessLeave();
       $totalOvertimeDays = $workCalendar->getOvertimeDays();
       $totals = [
          EventType::VACATION => $totalVacationDays,
          EventType::PARTICULAR_BUSSINESS_LEAVE => $totalParticularBussinessLeaveDays,
          EventType::OVERTIME => $totalOvertimeDays,
          EventType::ANTIQUITY_DAYS => $totalAntiquityDays,
       ];
       $events = $this->eventRepo->findUserEventsOfTheYearWithPreviousYearDays($user, $year, false);
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
      //      $nextYear = intVal($year) + 1;
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
      $year = $request->get('year');
      $usersParam = $request->get('user');
      $users = null;
      if ($usersParam !== null && $usersParam !== '') {
         $users = explode(',', $usersParam);
      }
      $status = $request->get('status') === null ? null : intval($request->get('status'));
      /** @var User $me */
      $me = $this->getUser();
      if (null === $year) {
         $year = \DateTime::createFromFormat('Y', new \DateTime())->format('Y');
      }
      $nextYear = intVal($year) + 1;
      if ($request->get('department') !== null && (in_array('ROLE_ADMIN', $me->getRoles()) || in_array('ROLE_HHRR', $me->getRoles()))) {
         $department = $request->get('department');
      } elseif (!in_array('ROLE_ADMIN', $me->getRoles()) && !in_array('ROLE_HHRR', $me->getRoles())) {
         $department = $me->getDepartment();
      } else {
         $department = null;
      }

      $items = $repo->findByDepartmentAndUsersAndStatusBeetweenDates($department, $users, $status, new \DateTime("$year-01-01"), new \DateTime("$nextYear-01-01"));
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
}
