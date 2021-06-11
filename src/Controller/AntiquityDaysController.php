<?php

namespace App\Controller;

use App\Entity\AntiquityDays;
use App\Form\AntiquityDaysType;
use App\Repository\AntiquityDaysRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @Route("{_locale}/antiquity/days")
 * @IsGranted("ROLE_USER")
 */
class AntiquityDaysController extends AbstractController
{
    /**
     * @Route("/", name="antiquity_days_index", methods={"GET"})
     */
    public function index(AntiquityDaysRepository $antiquityDaysRepository, Request $request): Response
    {
        $ajax = $request->get('ajax') !== null ? $request->get('ajax') : "false";
        if ($ajax === "false") {
            $antiquityDays = new AntiquityDays();
            $form = $this->createForm(AntiquityDaysType::class, $antiquityDays);

            return $this->render('antiquity_days/index.html.twig', [
                'antiquity_days' => $antiquityDaysRepository->findAll(),
                'form' => $form->createView(),
            ]);
        } else {
            return $this->render('antiquity_days/_list.html.twig', [
                'antiquity_days' => $antiquityDaysRepository->findAll(),
            ]);
        }
    }

    /**
     * Creates or updates a antiquity days record
     * 
     * @Route("/new", name="antiquity_days_save", methods={"GET","POST"})
     */
    public function createOrSave(Request $request, AntiquityDaysRepository $repo): Response
    {
        $antiquityDay = new AntiquityDays();
        $form = $this->createForm(AntiquityDaysType::class, $antiquityDay);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AntiquityDays $data */
            $data = $form->getData();
            if (null !== $data->getId()) {
                $antiquityDay = $repo->find($data->getId());
                $antiquityDay->fill($data);
            }
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($antiquityDay);
            $entityManager->flush();

            if ($request->isXmlHttpRequest()) {
                return new Response(null, 204);
            }
            return $this->redirectToRoute('antiquity_days_index');
        }

        $template = $request->isXmlHttpRequest() ? '_form.html.twig' : 'new.html.twig';
        return $this->render('antiquity_days/' . $template, [
            'antiquity_days' => $antiquityDay,
            'form' => $form->createView(),
        ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200,));
    }


    /**
     * @Route("/{id}", name="antiquity_days_show", methods={"GET"})
     */
    public function show(Request $request, AntiquityDays $antiquityDay): Response
    {
        $form = $this->createForm(AntiquityDaysType::class, $antiquityDay, [
            'readonly' => true,
        ]);
        $template = $request->isXmlHttpRequest() ? '_form.html.twig' : 'show.html.twig';
        return $this->render('antiquity_days/' . $template, [
            'antiquity_day' => $antiquityDay,
            'form' => $form->createView(),
            'readonly' => true
        ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200,));
    }

    /**
     * @Route("/{id}/edit", name="antiquity_days_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, AntiquityDays $antiquityDay): Response
    {
        $form = $this->createForm(AntiquityDaysType::class, $antiquityDay, [
            'readonly' => false,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AntiquityDays $antiquityDay */
            $antiquityDay = $form->getData();
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($antiquityDay);
            $entityManager->flush();
        }

        $template = $request->isXmlHttpRequest() ? '_form.html.twig' : 'edit.html.twig';
        return $this->render('antiquity_days/' . $template, [
            'antiquity_day' => $antiquityDay,
            'form' => $form->createView(),
            'readonly' => false
        ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200,));
    }

    /**
     * @Route("/{id}", name="antiquity_days_delete", methods={"DELETE"})
     */
    public function delete(Request $request, AntiquityDays $antiquityDay): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($antiquityDay);
        $entityManager->flush();
        if (!$request->isXmlHttpRequest()) {
            return $this->redirectToRoute('antiquity_days_index');
        } else {
            return new Response(null, 204);
        }
    }
}
