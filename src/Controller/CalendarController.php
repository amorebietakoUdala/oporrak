<?php

namespace App\Controller;

use App\Entity\Event;
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
     * @Route("/{_locale}/calendar", name="calendar")
     */
    public function calendar(): Response
    {
        $event = new Event();
        $form = $this->createForm(EventFormType::class, $event);
        return $this->render('calendar/index.html.twig', [
            'form' => $form->createView(),
            'holidaysColor' => $this->getParameter('holidaysColor'),
        ]);
    }
}
