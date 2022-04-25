<?php

namespace App\Controller;

use App\DTO\ReportsFilterFormDTO;
use App\Form\ReportsFilterFormType;
use App\Repository\EventRepository;
use App\Services\StatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class ReportsController extends AbstractController
{

    private $statsService = null;
    private EventRepository $eventRepo;

    public function __construct(StatsService $statsService, EventRepository $eventRepo) {
        $this->statsService = $statsService;
        $this->eventRepo = $eventRepo;
    }

    /**
     * @Route("/{_locale}/reports", name="reportsIndex")
     * @IsGranted("ROLE_HHRR")
     */
    public function index(Request $request): Response
    {
        $form = $this->createForm(ReportsFilterFormType::class, new ReportsFilterFormDTO(), [
            'locale' => $request->getLocale(),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var ReportsFilterFormDTO $data */
            $data = $form->getData();
            if ( null === $data->getStartDate() && 
                 null === $data->getEndDate() && 
                 null === $data->getUser() && 
                 null === $data->getDepartment()
                ) {
                $this->addFlash('error', 'message.selectOneCriteria');
                return $this->renderForm('reports/index.html.twig', [
                    'form' => $form,
                ]);
            }
            $events = $this->eventRepo->findApprovedEventsByDateUserAndDepartment(
                $data->getStartDate(), 
                $data->getEndDate(), 
                $data->getUser(), 
                $data->getDepartment()
            );
            $counters = $this->statsService->calculateStatsByUserAndEventType($events);
            
            return $this->renderForm('reports/index.html.twig', [
                'form' => $form,
                'counters' => $counters,
            ]);
        }

        return $this->renderForm('reports/index.html.twig', [
            'form' => $form,
        ]);
    }

}
