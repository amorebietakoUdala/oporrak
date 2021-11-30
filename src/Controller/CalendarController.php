<?php

namespace App\Controller;

use App\Entity\AntiquityDays;
use App\Entity\Event;
use App\Entity\Holiday;
use App\Entity\Status;
use App\Entity\User;
use App\Entity\WorkCalendar;
use App\Form\EventFormType;
use App\Form\UserFilterType;
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
        $statuses = $this->getDoctrine()->getManager()->getRepository(Status::class)->findAll();
        $antiquityDays = $this->getDoctrine()->getManager()->getRepository(AntiquityDays::class)->findAll();
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
            'roles' => $this->getUser()->getRoles(),
            'locale' => $request->getLocale(),
            'showDepartment' => $showDepartment,
            'department' => $department
        ]);
        $statuses = $this->getDoctrine()->getManager()->getRepository(Status::class)->findAll();
        $antiquityDays = $this->getDoctrine()->getManager()->getRepository(AntiquityDays::class)->findAll();
        return $this->render($template, [
            'form' => $form->createView(),
            'userFilterForm' => $userFilterForm->createView(),
            'holidaysColor' => $this->getParameter('holidaysColor'),
            'year' => $year,
            'statuses' => $statuses,
            'days' => $this->getParameter('days'),
            'antiquityDays' => $antiquityDays,
            'showDepartment' => $showDepartment,
            'previousYearDaysColor' => $this->getParameter('previousYearsDaysColor')
        ]);
    }

    /**
     * @Route("/{_locale}/my/stats", name="api_get_my_stats", methods="GET")
     */
    public function getMyStats(Request $request): Response
    {
        $year = $request->get('year');
        if (null === $year) {
            $year = (new \DateTime())->format('Y');
        }
        $stats = $this->calculateStats($year);
        return $this->render('calendar/_legend.html.twig', [
            'stats' => $stats,
        ]);
    }

    private function calculateStats(int $year)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        $workCalendar = $em->getRepository(WorkCalendar::class)->findOneBy(['year' => $year]);
        $events = $em->getRepository(Event::class)->findEffectiveUserEventsOfTheYear($user, $year);
        $eventsWithLastYearDays = $em->getRepository(Event::class)->findUserEventsOfTheYearWithPreviousYearDays($user, $year);
        $statuses = $em->getRepository(Status::class)->findAll();
        $holidays = $em->getRepository(Holiday::class)->findHolidaysBetween(new \DateTime("${year}-01-01"), new \DateTime("${year}-12-31"));

        $stats = $this->initializeCounters($statuses);
        foreach ($events as $event) {
            if ($event->getHalfDay()) {
                $stats[$event->getStatus()->getId()]['count'] += $event->getHours() / $workCalendar->getWorkingHours();
            } else {
                $stats[$event->getStatus()->getId()]['count'] += $event->getDays();
            }
        }
        foreach ($eventsWithLastYearDays as $event) {
            if ($event->getHalfDay()) {
                $stats['eventsWithLastYearDays']['count'] += $event->getHours() / $workCalendar->getWorkingHours();
            } else {
                $stats['eventsWithLastYearDays']['count'] += $event->getDays();
            }
        }
        $stats['holidays'] = [
            'description' => 'label.holidays',
            'count' => count($holidays),
            'color' => $this->getParameter('holidaysColor'),
        ];
        return $stats;
    }

    private function initializeCounters($statuses)
    {
        $stats = [];
        foreach ($statuses as $status) {
            $stats[$status->getId()] = [
                'description' => $status->getDescription(),
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
