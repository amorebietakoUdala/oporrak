<?php

namespace App\Controller;

use App\Entity\Holiday;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;


class HolidayController extends AbstractController
{
    private $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * @Route("/holidays/refresh", name="holiday")
     * @IsGranted("ROLE_ADMIN")
     */
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $year = $request->get('year');
        $response = $this->client->request(
            'GET',
            "https://opendata.euskadi.eus/contenidos/ds_eventos/calendario_laboral_$year/opendata/calendario_laboral_$year.json"
        );
        if ($response->getStatusCode() === Response::HTTP_OK) {
            $content = $response->getContent();
            $json = substr($content, 13, -1);
            $jsonData = json_decode($json, true);
            foreach ($jsonData as $day) {
                if (
                    $day['territory'] === 'Todos/denak' ||
                    ($day['municipalityEu'] === $this->getParameter('municipalityEu') && $day['territory'] === $this->getParameter('territoryEu'))
                ) {
                    $found = $em->getRepository(Holiday::class)->findOneBy(['date' => new \DateTime($day['date'])]);
                    if (null === $found) {
                        $holiday = new Holiday();
                        $holiday->fill($day);
                        $em->persist($holiday);
                    }
                }
            }
            $em->flush();
            $this->addFlash('success', 'Holidays refreshed');
        } else {
            $this->addFlash('error', 'error.notFound');
        }
        return $this->render('holiday/refresh.html.twig');
    }
}
