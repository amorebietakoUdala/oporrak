<?php

namespace App\Controller;

use App\Entity\WorkCalendar;
use App\Form\WorkCalendarType;
use App\Repository\WorkCalendarRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/{_locale}/workcalendar')]
#[IsGranted('ROLE_USER')]
class WorkCalendarController extends AbstractController
{

    public function __construct(private readonly WorkCalendarRepository $wcRepo)
    {
    }

    /**
     * List all the WorkCalendars
     */
    #[Route(path: '/', name: 'workcalendar_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $ajax = $request->get('ajax') ?? "false";
        if ($ajax === "false") {
            $WorkCalendar = new WorkCalendar();
            $form = $this->createForm(WorkCalendarType::class, $WorkCalendar);
            return $this->render('workcalendar/index.html.twig', [
                'workcalendars' => $this->wcRepo->findBy([], ['year' => 'DESC']),
                'form' => $form,
            ]);
        } else {
            return $this->render('workcalendar/_list.html.twig', [
                'workcalendars' => $this->wcRepo->findBy([], ['year' => 'DESC']),
            ]);
        }
    }

    /**
     * Creates or updates a WorkCalendar
     */
    #[Route(path: '/new', name: 'workcalendar_save', methods: ['GET', 'POST'])]
    public function createOrSave(Request $request, EntityManagerInterface $em): Response
    {
        $workCalendar = new WorkCalendar();
        $form = $this->createForm(WorkCalendarType::class, $workCalendar);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var WorkCalendar $data */
            $data = $form->getData();
            if (null !== $data->getId()) {
                $workCalendar = $this->wcRepo->find($data->getId());
                $workCalendar->fill($data);
            }
            $em->persist($workCalendar);
            $em->flush();

            if ($request->isXmlHttpRequest()) {
                return new Response(null, Response::HTTP_NO_CONTENT);
            }
            return $this->redirectToRoute('workcalendar_index');
        }

        $template = $request->isXmlHttpRequest() ? '_form.html.twig' : 'new.html.twig';
        return $this->render('workcalendar/' . $template, [
            'workcalendar' => $workCalendar,
            'form' => $form,
        ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200,));
    }

    /**
     * Show the WorkCalendar form specified by id.
     * The WorkCalendar can't be changed
     */
    #[Route(path: '/{id}', name: 'workcalendar_show', methods: ['GET'])]
    public function show(Request $request, WorkCalendar $workCalendar): Response
    {
        $form = $this->createForm(WorkCalendarType::class, $workCalendar, [
            'readonly' => true,
        ]);
        $template = $request->isXmlHttpRequest() ? '_form.html.twig' : 'show.html.twig';
        return $this->render('workcalendar/' . $template, [
            'workcalendar' => $workCalendar,
            'form' => $form,
            'readonly' => true
        ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200,));
    }

    /**
     * Renders the WorkCalendar form specified by id to edit it's fields
     */
    #[Route(path: '/{id}/edit', name: 'workcalendar_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, WorkCalendar $workCalendar, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(WorkCalendarType::class, $workCalendar, [
            'readonly' => false,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var WorkCalendar $workCalendar */
            $workCalendar = $form->getData();
            $em->persist($workCalendar);
            $em->flush();
        }

        $template = $request->isXmlHttpRequest() ? '_form.html.twig' : 'edit.html.twig';
        return $this->render('workcalendar/' . $template, [
            'workcalendar' => $workCalendar,
            'form' => $form,
            'readonly' => false
        ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200,));
    }

    #[Route(path: '/{id}/delete', name: 'workcalendar_delete', methods: ['DELETE'])]
    public function delete(Request $request, WorkCalendar $id, EntityManagerInterface $em): Response
    {
        $em->remove($id);
        $em->flush();
        if (!$request->isXmlHttpRequest()) {
            return $this->redirectToRoute('workcalendar_index');
        } else {
            return new Response(null, Response::HTTP_NO_CONTENT);
        }
    }
}
