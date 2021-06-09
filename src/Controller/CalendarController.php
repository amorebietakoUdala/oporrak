<?php

namespace App\Controller;

use App\Entity\AntiquityDays;
use App\Entity\Event;
use App\Entity\Status;
use App\Form\EventFormType;
use App\Form\UserFilterType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_OPORRAK")
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
        $form = $this->createForm(EventFormType::class, $event);
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
        return $this->renderCalendar($request, 'calendar/department.html.twig', false);
    }

    /**
     * @Route("/{_locale}/city-hall-calendar", name="cityHallCalendar")
     */
    public function cityHall(Request $request): Response
    {
        return $this->renderCalendar($request, 'calendar/city-hall.html.twig', true);
    }

    private function renderCalendar(Request $request, $template, $showDepartment): Response
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
            'showDepartment' => $showDepartment
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
            'showDepartment' => $showDepartment
        ]);
    }
}
