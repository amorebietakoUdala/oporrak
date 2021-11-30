<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

use App\Repository\EventTypeRepository;
use App\Entity\EventType;
use App\Form\EventTypeFormType;

/**
 * @Route("/{_locale}/event_type")
 * @IsGranted("ROLE_ADMIN")
 */
class EventTypeController extends AbstractController
{
    /**
     * Creates or updates an eventType
     * 
     * @Route("/new", name="event_type_save", methods={"GET","POST"})
     */
    public function createOrSave(Request $request, EventTypeRepository $repo): Response
    {
        $eventType = new EventType();
        $form = $this->createForm(EventTypeFormType::class, $eventType);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var EventType $data */
            $data = $form->getData();
            if (null !== $data->getId()) {
                $eventType = $repo->find($data->getId());
                $eventType->fill($data);
            }
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($eventType);
            $entityManager->flush();

            if ($request->isXmlHttpRequest()) {
                return new Response(null, 204);
            }
            return $this->redirectToRoute('event_type_index');
        }

        $template = $request->isXmlHttpRequest() ? '_form.html.twig' : 'new.html.twig';
        return $this->render('event_type/' . $template, [
            'eventType' => $eventType,
            'form' => $form->createView(),
        ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200,));
    }

    /**
     * List all eventTypes
     * @Route("/", name="event_type_index", methods={"GET"})
     */
    public function index(EventTypeRepository $repo, Request $request): Response
    {
        $ajax = $request->get('ajax') !== null ? $request->get('ajax') : "false";
        if ($ajax === "false") {
            $eventType = new EventType();
            $form = $this->createForm(EventTypeFormType::class, $eventType);
            return $this->render('event_type/index.html.twig', [
                'eventTypes' => $repo->findAll(),
                'form' => $form->createView(),
            ]);
        } else {
            return $this->render('event_type/_list.html.twig', [
                'eventTypes' => $repo->findAll(),
            ]);
        }
    }


    /**
     * Show the EventType form specified by id.
     * The EventType can't be changed
     * 
     * @Route("/{id}", name="event_type_show", methods={"GET"})
     */
    public function show(Request $request, EventType $eventType): Response
    {
        $form = $this->createForm(EventTypeFormType::class, $eventType, [
            'readonly' => true,
        ]);
        $template = $request->isXmlHttpRequest() ? '_form.html.twig' : 'show.html.twig';
        return $this->render('event_type/' . $template, [
            'eventType' => $eventType,
            'form' => $form->createView(),
            'readonly' => true
        ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200,));
    }

    /**
     * Renders the EventType form specified by id to edit it's fields
     * 
     * @Route("/{id}/edit", name="event_type_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, EventType $eventType): Response
    {
        $form = $this->createForm(EventTypeFormType::class, $eventType, [
            'readonly' => false,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var EventType $data */
            $data = $form->getData();
            $entityManager = $this->getDoctrine()->getManager();
            $eventType->fill($data);
            $entityManager->persist($eventType);

            $entityManager->flush();
        }

        $template = $request->isXmlHttpRequest() ? '_form.html.twig' : 'edit.html.twig';
        return $this->render('event_type/' . $template, [
            'eventType' => $eventType,
            'form' => $form->createView(),
            'readonly' => false
        ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200,));
    }

    /**
     * @Route("/{id}/delete", name="event_type_delete", methods={"DELETE"})
     */
    public function delete(Request $request, EventType $id): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($id);
        $entityManager->flush();
        if (!$request->isXmlHttpRequest()) {
            return $this->redirectToRoute('event_type_index');
        } else {
            return new Response(null, 204);
        }
    }
}
