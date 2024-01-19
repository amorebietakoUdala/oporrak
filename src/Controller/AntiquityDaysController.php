<?php

namespace App\Controller;

use App\Entity\AntiquityDays;
use App\Form\AntiquityDaysType;
use App\Repository\AntiquityDaysRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '{_locale}/antiquity/days')]
#[IsGranted('ROLE_USER')]
class AntiquityDaysController extends AbstractController
{
    #[Route(path: '/', name: 'antiquity_days_index', methods: ['GET'])]
    public function index(AntiquityDaysRepository $antiquityDaysRepository, Request $request): Response
    {
        $ajax = $request->get('ajax') ?? "false";
        if ($ajax === "false") {
            $antiquityDays = new AntiquityDays();
            $form = $this->createForm(AntiquityDaysType::class, $antiquityDays);

            return $this->render('antiquity_days/index.html.twig', [
                'antiquity_days' => $antiquityDaysRepository->findAll(),
                'form' => $form,
            ]);
        } else {
            return $this->render('antiquity_days/_list.html.twig', [
                'antiquity_days' => $antiquityDaysRepository->findAll(),
            ]);
        }
    }

    /**
     * Creates or updates a antiquity days record
     */
    #[Route(path: '/new', name: 'antiquity_days_save', methods: ['GET', 'POST'])]
    public function createOrSave(Request $request, AntiquityDaysRepository $repo, EntityManagerInterface $em): Response
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
            $em->persist($antiquityDay);
            $em->flush();

            if ($request->isXmlHttpRequest()) {
                return new Response(null, \Symfony\Component\HttpFoundation\Response::HTTP_NO_CONTENT);
            }
            return $this->redirectToRoute('antiquity_days_index');
        }

        $template = $request->isXmlHttpRequest() ? '_form.html.twig' : 'new.html.twig';
        return $this->render('antiquity_days/' . $template, [
            'antiquity_days' => $antiquityDay,
            'form' => $form,
        ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200,));
    }


    #[Route(path: '/{id}', name: 'antiquity_days_show', methods: ['GET'])]
    public function show(Request $request, AntiquityDays $antiquityDay): Response
    {
        $form = $this->createForm(AntiquityDaysType::class, $antiquityDay, [
            'readonly' => true,
        ]);
        $template = $request->isXmlHttpRequest() ? '_form.html.twig' : 'show.html.twig';
        return $this->render('antiquity_days/' . $template, [
            'antiquity_day' => $antiquityDay,
            'form' => $form,
            'readonly' => true
        ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200,));
    }

    #[Route(path: '/{id}/edit', name: 'antiquity_days_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, AntiquityDays $antiquityDay, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(AntiquityDaysType::class, $antiquityDay, [
            'readonly' => false,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AntiquityDays $antiquityDay */
            $antiquityDay = $form->getData();
            $em->persist($antiquityDay);
            $em->flush();
        }

        $template = $request->isXmlHttpRequest() ? '_form.html.twig' : 'edit.html.twig';
        return $this->render('antiquity_days/' . $template, [
            'antiquity_day' => $antiquityDay,
            'form' => $form,
            'readonly' => false
        ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200,));
    }

    #[Route(path: '/{id}', name: 'antiquity_days_delete', methods: ['DELETE'])]
    public function delete(Request $request, AntiquityDays $antiquityDay, EntityManagerInterface $em): Response
    {
        $em->remove($antiquityDay);
        $em->flush();
        if (!$request->isXmlHttpRequest()) {
            return $this->redirectToRoute('antiquity_days_index');
        } else {
            return new Response(null, \Symfony\Component\HttpFoundation\Response::HTTP_NO_CONTENT);
        }
    }
}
