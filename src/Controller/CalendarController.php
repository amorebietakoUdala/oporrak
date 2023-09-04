<?php

namespace App\Controller;

use App\Entity\Department;
use App\Entity\Event;
use App\Entity\Status;
use App\Entity\User;
use App\Form\EventFormType;
use App\Form\UserFilterType;
use App\Repository\AntiquityDaysRepository;
use App\Repository\EventRepository;
use App\Repository\HolidayRepository;
use App\Repository\StatusRepository;
use App\Repository\UserRepository;
use App\Repository\WorkCalendarRepository;
use App\Services\StatsService;
use DateInterval;
use PhpParser\Node\Expr\FuncCall;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_USER")
 */
class CalendarController extends AbstractController
{

    private StatsService $statsService;
    private StatusRepository $statusRepo;
    private AntiquityDaysRepository $adRepo;
    private WorkCalendarRepository $wcRepo;
    private EventRepository $eventRepo;
    private HolidayRepository $holidayRepo;
    private UserRepository $userRepo;

    public function __construct (StatsService $statsService, StatusRepository $statusRepo, AntiquityDaysRepository $adRepo, WorkCalendarRepository $wcRepo, EventRepository $eventRepo, HolidayRepository $holidayRepo, UserRepository $userRepo) {
        $this->statsService = $statsService;
        $this->statusRepo = $statusRepo;
        $this->adRepo = $adRepo;
        $this->wcRepo = $wcRepo;
        $this->eventRepo = $eventRepo;
        $this->holidayRepo = $holidayRepo;
        $this->userRepo = $userRepo;
    }

    /**
     * @Route("/", name="app_home")
     */
    public function home(Request $request): Response
    {
        return $this->redirectToRoute('myCalendar');
    }

    /**
     * @Route("/{_locale}/mycalendar", name="myCalendar")
     */
    public function personal(Request $request): Response
    {
        $event = new Event();
        $year = $request->get('year');
        if (null === $year) {
            $year = (new \DateTime())->format('Y');
        }
        $form = $this->createForm(EventFormType::class, $event, [
            'days' => $this->getParameter('days'),
            'locale' => $request->getLocale(),
        ]);
        $statuses = $this->statusRepo->findAll();
        $antiquityDays = $this->adRepo->findAll();
        return $this->render('calendar/personal.html.twig', [
            'form' => $form->createView(),
            'holidaysColor' => $this->getParameter('holidaysColor'),
            'year' => $year,
            'statuses' => $statuses,
            'days' => $this->getParameter('days'),
            'antiquityDays' => $antiquityDays,
        ]);
    }

    /**
     * @Route("/{_locale}/department-calendar", name="departmentCalendar")
     */
    public function department(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        return $this->renderCalendar($request, 'calendar/department.html.twig', false, $user->getDepartment());
    }

    /**
     * @Route("/{_locale}/city-hall-calendar", name="cityHallCalendar")
     */
    public function cityHall(Request $request): Response
    {
        return $this->renderCalendar($request, 'calendar/city-hall.html.twig', true);
    }

    private function renderCalendar(Request $request, $template, $showDepartment, $department = null): Response
    {
        $event = new Event();
        $year = $request->get('year');
        if (null === $year) {
            $year = (new \DateTime())->format('Y');
        }
        $form = $this->createForm(EventFormType::class, $event);
        $userFilterForm = $this->createForm(UserFilterType::class, [
            'locale' => $request->getLocale(),
            'showDepartment' => $showDepartment,
            'department' => $department,
        ]);
        $statuses = $this->statusRepo->findAll();
        $antiquityDays = $this->adRepo->findAll();

        return $this->render($template, [
            'form' => $form->createView(),
            'userFilterForm' => $userFilterForm->createView(),
            'holidaysColor' => $this->getParameter('holidaysColor'),
            'year' => $year,
            'statuses' => $statuses,
            'days' => $this->getParameter('days'),
            'antiquityDays' => $antiquityDays,
            'showDepartment' => $showDepartment,
            'previousYearDaysColor' => $this->getParameter('previousYearsDaysColor'),
            'roles' => array_values($this->getUser()->getRoles()),
            'colorPalette' => $this->getParameter('colorPalette'),
            'hhrr' => $this->isGranted("ROLE_HHRR"),
        ]);
    }

    /**
     * @Route("/{_locale}/my/stats", name="api_get_my_stats", methods="GET")
     */
    public function getMyStats(Request $request): Response
    {
        $year = $request->get('year');
        $locale = $request->getLocale();
        if (null === $year) {
            $year = (new \DateTime())->format('Y');
        }
        $stats = $this->calculateStats($year, $locale);
        return $this->render('calendar/_legend.html.twig', [
            'stats' => $stats,
        ]);
    }

    private function calculateStats(int $year, $locale)
    {
        $user = $this->getUser();

        $workCalendar = $this->wcRepo->findOneBy(['year' => $year]);
        $events = $this->eventRepo->findEffectiveUserEventsOfTheYear($user, $year);
        $counters = $this->statsService->calculateStatsByStatus($events, $year);
        $eventsWithLastYearDays = $this->eventRepo->findUserEventsOfTheYearWithPreviousYearDays($user, $year, true);
        $workingDaysWithPreviousYearDays = $this->statsService->calculateTotalWorkingDays($eventsWithLastYearDays, $workCalendar);
        $statuses = $this->statusRepo->findAll();
        $holidays = $this->holidayRepo->findHolidaysBetween(new \DateTime("${year}-01-01"), new \DateTime("${year}-12-31"));
        $stats = $this->initializeCounters($statuses, $locale);
        foreach ($counters as $key => $value) {
            $stats[$key]['count'] = $value;
        }
        $stats['eventsWithLastYearDays']['count'] = $workingDaysWithPreviousYearDays;
        $stats['holidays'] = [
            'description' => 'label.holidays',
            'count' => count($holidays),
            'color' => $this->getParameter('holidaysColor'),
        ];
        return $stats;
    }

    private function initializeCounters($statuses, $locale)
    {
        $stats = [];
        foreach ($statuses as $status) {
            $stats[$status->getId()] = [
                'description' => $locale === 'es' ? $status->getDescriptionEs() : $status->getDescriptionEu(),
                'count' => 0,
                'color' => $status->getColor(),
            ];
        }
        $stats['eventsWithLastYearDays'] = [
            'description' => 'label.eventsWithLastYearDays',
            'count' => 0,
            'color' => $this->getParameter('previousYearsDaysColor'),
        ];
        return $stats;
    }

}
