<?php

namespace App\Controller;

use App\Entity\AdditionalVacationDays;
use App\Form\AdditionalVacationDaysType;
use App\Repository\AdditionalVacationDaysRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @Route("{_locale}/additional-vacation-days")
 * @IsGranted("ROLE_USER")
 */
class AdditionalVacationDaysController extends AbstractController
{
    /**
     * @Route("/", name="additional_vacation_days_index", methods={"GET"})
     */
    public function index(AdditionalVacationDaysRepository $AdditionalVacationDaysRepository, Request $request): Response
    {
        $ajax = $request->get('ajax') !== null ? $request->get('ajax') : "false";
        if ($ajax === "false") {
            $AdditionalVacationDays = new AdditionalVacationDays();
            $form = $this->createForm(AdditionalVacationDaysType::class, $AdditionalVacationDays);

            return $this->render('additional_vacation_days/index.html.twig', [
                'additional_vacation_days' => $AdditionalVacationDaysRepository->findAll(),
                'form' => $form->createView(),
            ]);
        } else {
            return $this->render('additional_vacation_days/_list.html.twig', [
                'additional_vacation_days' => $AdditionalVacationDaysRepository->findAll(),
            ]);
        }
    }

    /**
     * Creates or updates a antiquity days record
     * 
     * @Route("/new", name="additional_vacation_days_save", methods={"GET","POST"})
     */
    public function createOrSave(Request $request, AdditionalVacationDaysRepository $repo, EntityManagerInterface $em): Response
    {
        $antiquityDay = new AdditionalVacationDays();
        $form = $this->createForm(AdditionalVacationDaysType::class, $antiquityDay);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AdditionalVacationDays $data */
            $data = $form->getData();
            if (null !== $data->getId()) {
                $antiquityDay = $repo->find($data->getId());
                $antiquityDay->fill($data);
            }
            $em->persist($antiquityDay);
            $em->flush();

            if ($request->isXmlHttpRequest()) {
                return new Response(null, 204);
            }
            return $this->redirectToRoute('additional_vacation_days_index');
        }

        $template = $request->isXmlHttpRequest() ? '_form.html.twig' : 'new.html.twig';
        return $this->render('additional_vacation_days/' . $template, [
            'additional_vacation_day' => $antiquityDay,
            'form' => $form->createView(),
        ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200,));
    }


    /**
     * @Route("/{id}", name="additional_vacation_days_show", methods={"GET"})
     */
    public function show(Request $request, AdditionalVacationDays $antiquityDay): Response
    {
        $form = $this->createForm(AdditionalVacationDaysType::class, $antiquityDay, [
            'readonly' => true,
        ]);
        $template = $request->isXmlHttpRequest() ? '_form.html.twig' : 'show.html.twig';
        return $this->render('additional_vacation_days/' . $template, [
            'additional_vacation_day' => $antiquityDay,
            'form' => $form->createView(),
            'readonly' => true
        ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200,));
    }

    /**
     * @Route("/{id}/edit", name="additional_vacation_days_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, AdditionalVacationDays $antiquityDay, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(AdditionalVacationDaysType::class, $antiquityDay, [
            'readonly' => false,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AdditionalVacationDays $antiquityDay */
            $antiquityDay = $form->getData();
            $em->persist($antiquityDay);
            $em->flush();
        }

        $template = $request->isXmlHttpRequest() ? '_form.html.twig' : 'edit.html.twig';
        return $this->render('additional_vacation_days/' . $template, [
            'additional_vacation_day' => $antiquityDay,
            'form' => $form->createView(),
            'readonly' => false
        ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200,));
    }

    /**
     * @Route("/{id}", name="additional_vacation_days_delete", methods={"DELETE"})
     */
    public function delete(Request $request, AdditionalVacationDays $antiquityDay, EntityManagerInterface $em): Response
    {
        $em->remove($antiquityDay);
        $em->flush();
        if (!$request->isXmlHttpRequest()) {
            return $this->redirectToRoute('additional_vacation_days_index');
        } else {
            return new Response(null, 204);
        }
    }
}
