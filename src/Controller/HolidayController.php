<?php

namespace App\Controller;

use App\Entity\Holiday;
use App\Form\HolidayType;
use App\Repository\HolidayRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;


/**
 * @Route("/{_locale}/holidays")
 * @IsGranted("ROLE_USER")
 */
class HolidayController extends AbstractController
{
    private $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * @Route("/refresh", name="holiday")
     * @IsGranted("ROLE_ADMIN")
     */
    public function refresh(Request $request, EntityManagerInterface $em): Response
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
                        $holiday->fillFromArray($day);
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

    /**
     * List all the Holidays
     * 
     * @Route("/", name="holiday_index", methods={"GET"})
     */
    public function index(HolidayRepository $holidayRepository, Request $request): Response
    {
        $ajax = $request->get('ajax') !== null ? $request->get('ajax') : "false";
        if ($ajax === "false") {
            $holiday = new Holiday();
            $form = $this->createForm(HolidayType::class, $holiday);
            return $this->render('holiday/index.html.twig', [
                'holidays' => $holidayRepository->findBy([], ['date' => 'DESC']),
                'form' => $form->createView(),
            ]);
        } else {
            return $this->render('holiday/_list.html.twig', [
                'holidays' => $holidayRepository->findBy([], ['date' => 'DESC']),
            ]);
        }
    }

    /**
     * Creates or updates a Holiday
     * 
     * @Route("/new", name="holiday_save", methods={"GET","POST"})
     */
    public function createOrSave(Request $request, holidayRepository $repo): Response
    {
        $holiday = new Holiday();
        $form = $this->createForm(HolidayType::class, $holiday);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Holiday $data */
            $data = $form->getData();
            if (null !== $data->getId()) {
                $holiday = $repo->find($data->getId());
                $holiday->fill($data);
            }
            $entityManager = $this->getDoctrine()->getManager();
            $holiday->setYear($holiday->getDate()->format('Y'));
            $entityManager->persist($holiday);
            $entityManager->flush();

            if ($request->isXmlHttpRequest()) {
                return new Response(null, 204);
            }
            return $this->redirectToRoute('holiday_index');
        }

        $template = $request->isXmlHttpRequest() ? '_form.html.twig' : 'new.html.twig';
        return $this->render('holiday/' . $template, [
            'holiday' => $holiday,
            'form' => $form->createView(),
        ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200,));
    }

    /**
     * Show the Holiday form specified by id.
     * The Holiday can't be changed
     * 
     * @Route("/{id}", name="holiday_show", methods={"GET"})
     */
    public function show(Request $request, Holiday $holiday): Response
    {
        $form = $this->createForm(HolidayType::class, $holiday, [
            'readonly' => true,
        ]);
        $template = $request->isXmlHttpRequest() ? '_form.html.twig' : 'show.html.twig';
        return $this->render('holiday/' . $template, [
            'Holiday' => $holiday,
            'form' => $form->createView(),
            'readonly' => true
        ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200,));
    }

    /**
     * Renders the Holiday form specified by id to edit it's fields
     * 
     * @Route("/{id}/edit", name="holiday_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Holiday $holiday): Response
    {
        $form = $this->createForm(HolidayType::class, $holiday, [
            'readonly' => false,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Holiday $holiday */
            $holiday = $form->getData();
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($holiday);
            $entityManager->flush();
        }

        $template = $request->isXmlHttpRequest() ? '_form.html.twig' : 'edit.html.twig';
        return $this->render('holiday/' . $template, [
            'holiday' => $holiday,
            'form' => $form->createView(),
            'readonly' => false
        ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200,));
    }

    /**
     * @Route("/{id}/delete", name="holiday_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Holiday $id): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($id);
        $entityManager->flush();
        if (!$request->isXmlHttpRequest()) {
            return $this->redirectToRoute('holiday_index');
        } else {
            return new Response(null, 204);
        }
    }
}
