<?php

namespace App\Controller;

use App\DTO\ReportsFilterFormDTO;
use App\Entity\Status;
use App\Form\ReportsFilterFormType;
use App\Repository\EventRepository;
use App\Services\StatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ReportsController extends AbstractController
{

    public function __construct(private readonly StatsService $statsService, private readonly EventRepository $eventRepo)
    {
    }

    #[Route(path: '/{_locale}/reports', name: 'reportsIndex')]
    #[IsGranted('ROLE_HHRR')]
    public function index(Request $request): Response
    {
        $form = $this->createForm(ReportsFilterFormType::class, new ReportsFilterFormDTO(), [
            'locale' => $request->getLocale(),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var ReportsFilterFormDTO $data */
            $data = $form->getData();
            if ( 
                //  null === $data->getStartDate() && 
                //  null === $data->getEndDate() && 
                 null === $data->getYear() && 
                 null === $data->getUser() && 
                 null === $data->getDepartment()
                ) {
                $this->addFlash('error', 'message.selectOneCriteria');
                return $this->render('reports/index.html.twig', [
                    'form' => $form,
                ]);
            }
            // $events = $this->eventRepo->findApprovedEventsByDateUserAndDepartment(
            //     $data->getStartDate(), 
            //     $data->getEndDate(), 
            //     $data->getUser(), 
            //     $data->getDepartment()
            // );
            $events = $this->eventRepo->findApprovedEventsByYearUserAndDepartment(
                $data->getYear(), 
                $data->getUser(), 
                $data->getDepartment()
            );

            $counters = $this->statsService->calculateStatsByUserAndEventType($events);
            return $this->render('reports/index.html.twig', [
                'form' => $form,
                'counters' => $counters,
            ]);
        }

        return $this->render('reports/index.html.twig', [
            'form' => $form,
        ]);
    }

}
