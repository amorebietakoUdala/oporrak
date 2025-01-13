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
use Symfony\Component\Security\Http\Attribute\IsGranted;


#[Route(path: '/{_locale}/holidays')]
#[IsGranted('ROLE_USER')]
class HolidayController extends AbstractController
{
    public function __construct(private readonly HttpClientInterface $client, private readonly HolidayRepository $holidayRepo)
    {
    }

    #[Route(path: '/refresh', name: 'holiday')]
    #[IsGranted('ROLE_ADMIN')]
    public function refresh(Request $request, EntityManagerInterface $em): Response
    {
        $year = $request->get('year');
        $clean = $request->get('clean') ? boolval($request->get('clean')) : false;

        $response = $this->client->request(
            'GET',
            "https://opendata.euskadi.eus/contenidos/ds_eventos/calendario_laboral_$year/opendata/calendario_laboral_$year.json"
        );
        if ($response->getStatusCode() === Response::HTTP_OK) {
            $content = $response->getContent();
            $json = $content;
            if ($clean) {
                $json = substr($content, 13, -1);
            }
            // We lowercase everything because sometimes comes "municipalityEu" and other times "MunicipalityEu" to avoid errors
            $json_lowerCase = mb_strtolower($json);
            $jsonData = json_decode($json_lowerCase, true);
            $municipalityEu = mb_strtolower(trim($this->getParameter('municipalityEu')));
            $territoryEu = mb_strtolower(trim($this->getParameter('territoryEu')));
            foreach ($jsonData as $day) {
                if (
                    $day['territory'] === 'todos/denak' ||
                    (trim((string) mb_strtolower($day['municipalityeu'])) === $municipalityEu && trim((string) mb_strtolower($day['territory'])) === $territoryEu ) ||
                    (trim((string) mb_strtolower($day['municipalityeu'])) === $municipalityEu && trim((string) mb_strtolower($day['territory'])) === $territoryEu ) ||
                    // When it's holiday in all the province MunicipalityEu is set to territoryEu so we add those too
                    (trim((string) mb_strtolower($day['municipalityeu'])) === $territoryEu && trim((string) mb_strtolower($day['territory'])) === $territoryEu )
                ) {
                    $found = $this->holidayRepo->findOneBy(['date' => new \DateTime($day['date'])]);
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
     */
    #[Route(path: '/', name: 'holiday_index', methods: ['GET'])]
    public function index(HolidayRepository $holidayRepository, Request $request): Response
    {
        $ajax = $request->get('ajax') ?? "false";
        if ($ajax === "false") {
            $holiday = new Holiday();
            $form = $this->createForm(HolidayType::class, $holiday);
            return $this->render('holiday/index.html.twig', [
                'holidays' => $holidayRepository->findBy([], ['date' => 'DESC']),
                'form' => $form,
            ]);
        } else {
            return $this->render('holiday/_list.html.twig', [
                'holidays' => $holidayRepository->findBy([], ['date' => 'DESC']),
            ]);
        }
    }

    /**
     * Creates or updates a Holiday
     */
    #[Route(path: '/new', name: 'holiday_save', methods: ['GET', 'POST'])]
    public function createOrSave(Request $request, EntityManagerInterface $em): Response
    {
        $holiday = new Holiday();
        $form = $this->createForm(HolidayType::class, $holiday);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Holiday $data */
            $data = $form->getData();
            if (null !== $data->getId()) {
                $holiday = $this->holidayRepo->find($data->getId());
                $holiday->fill($data);
            }
            $holiday->setYear($holiday->getDate()->format('Y'));
            $em->persist($holiday);
            $em->flush();

            if ($request->isXmlHttpRequest()) {
                return new Response(null, Response::HTTP_NO_CONTENT);
            }
            return $this->redirectToRoute('holiday_index');
        }

        $template = $request->isXmlHttpRequest() ? '_form.html.twig' : 'new.html.twig';
        return $this->render('holiday/' . $template, [
            'holiday' => $holiday,
            'form' => $form,
        ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200,));
    }

    /**
     * Show the Holiday form specified by id.
     * The Holiday can't be changed
     */
    #[Route(path: '/{id}', name: 'holiday_show', methods: ['GET'])]
    public function show(Request $request, Holiday $holiday): Response
    {
        $form = $this->createForm(HolidayType::class, $holiday, [
            'readonly' => true,
        ]);
        $template = $request->isXmlHttpRequest() ? '_form.html.twig' : 'show.html.twig';
        return $this->render('holiday/' . $template, [
            'Holiday' => $holiday,
            'form' => $form,
            'readonly' => true
        ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200,));
    }

    /**
     * Renders the Holiday form specified by id to edit it's fields
     */
    #[Route(path: '/{id}/edit', name: 'holiday_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Holiday $holiday, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(HolidayType::class, $holiday, [
            'readonly' => false,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Holiday $holiday */
            $holiday = $form->getData();
            $em->persist($holiday);
            $em->flush();
        }

        $template = $request->isXmlHttpRequest() ? '_form.html.twig' : 'edit.html.twig';
        return $this->render('holiday/' . $template, [
            'holiday' => $holiday,
            'form' => $form,
            'readonly' => false
        ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200,));
    }

    #[Route(path: '/{id}/delete', name: 'holiday_delete', methods: ['DELETE'])]
    public function delete(Request $request, Holiday $id, EntityManagerInterface $em): Response
    {
        $em->remove($id);
        $em->flush();
        if (!$request->isXmlHttpRequest()) {
            return $this->redirectToRoute('holiday_index');
        } else {
            return new Response(null, Response::HTTP_NO_CONTENT);
        }
    }
}
