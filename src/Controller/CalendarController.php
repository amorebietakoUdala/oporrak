<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Status;
use App\Form\EventFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CalendarController extends AbstractController
{

    /**
     * @Route("/", name="app_home")
     */
    public function home(Request $request): Response
    {
        return $this->redirectToRoute('calendar');
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
        return $this->render('calendar/personal.html.twig', [
            'form' => $form->createView(),
            'holidaysColor' => $this->getParameter('holidaysColor'),
            'year' => $year,
            'statuses' => $statuses,
            'days' => $this->getParameter('days'),
        ]);
    }

    /**
     * @Route("/{_locale}/department-calendar", name="departmentCalendar")
     */
    public function department(Request $request): Response
    {
        $event = new Event();
        $year = $request->get('year');
        if (null === $year) {
            $year = (new \DateTime())->format('Y');
        }
        $form = $this->createForm(EventFormType::class, $event);
        $statuses = $this->getDoctrine()->getManager()->getRepository(Status::class)->findAll();
        return $this->render('calendar/department.html.twig', [
            'form' => $form->createView(),
            'holidaysColor' => $this->getParameter('holidaysColor'),
            'colorPalette' => $this->getParameter('colorPalette'),
            'year' => $year,
            'statuses' => $statuses,
            'days' => $this->getParameter('days'),
        ]);
    }
}
