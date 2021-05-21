<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\User;
use App\Entity\Holiday;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @Route("/api")
 */
class ApiController extends AbstractController
{
    /**
     * @Route("/holidays", name="api_getHolidays", methods="GET")
     */
    public function getHolidays(Request $request, EntityManagerInterface $em): Response
    {
        $year = $request->get('year');
        if (null === $year) {
            $year = \DateTime::createFromFormat('Y', new \DateTime())->format('Y');
        }

        $holidays = $em->getRepository(Holiday::class)->findBy(['year' => $year]);
        return $this->json($holidays, 200, []);
    }

    /**
     * @Route("/my/dates", name="api_get_my_dates", methods="GET")
     */
    public function getMyDates(Request $request, EntityManagerInterface $em): Response
    {
        $year = $request->get('year');
        /** @var User $user */
        $user = $this->getUser();
        if (null === $year) {
            $year = \DateTime::createFromFormat('Y', new \DateTime())->format('Y');
        }
        $nextYear = intVal($year) + 1;
        $items = $em->getRepository(Event::class)->findByUserAndDates($user, new \DateTime("$year-01-01"), new \DateTime("$nextYear-01-01"));
        $dates = [
            'total_count' => $items === null ? 0 : count($items),
            'items' => $items === null ? [] : $items
        ];
        return $this->json($dates, 200, [], ['groups' => ['event']]);
    }
    /**
     * @Route("/event/{id}/edit", name="api_edit_event", methods="POST")
     */
    public function editEvent(Request $request, $id)
    {
        dd($request, $id);
    }

    /**
     * @Route("/event/new", name="api_save_event", methods="POST")
     */
    public function saveEvent(Request $request)
    {
        dd($request);
    }
}
