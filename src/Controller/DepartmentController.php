<?php

namespace App\Controller;

use App\Entity\Department;
use App\Form\DepartmentType;
use App\Repository\DepartmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/{_locale}/department')]
#[IsGranted('ROLE_USER')]
class DepartmentController extends AbstractController
{
    /**
     * List all the departments
     */
    #[Route(path: '/', name: 'department_index', methods: ['GET'])]
    public function index(DepartmentRepository $departmentRepository, Request $request): Response
    {
        $ajax = $request->get('ajax') ?? "false";
        if ($ajax === "false") {
            $department = new Department();
            $form = $this->createForm(DepartmentType::class, $department);
            return $this->render('department/index.html.twig', [
                'departments' => $departmentRepository->findAll(),
                'form' => $form,
            ]);
        } else {
            return $this->render('department/_list.html.twig', [
                'departments' => $departmentRepository->findAll(),
            ]);
        }
    }

    /**
     * Creates or updates a department
     */
    #[Route(path: '/new', name: 'department_save', methods: ['GET', 'POST'])]
    public function createOrSave(Request $request, DepartmentRepository $repo, EntityManagerInterface $em): Response
    {
        $department = new Department();
        $form = $this->createForm(DepartmentType::class, $department);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Department $data */
            $data = $form->getData();
            if (null !== $data->getId()) {
                $department = $repo->find($data->getId());
                $department->fill($data);
            }
            $em->persist($department);
            $em->flush();

            if ($request->isXmlHttpRequest()) {
                return new Response(null, \Symfony\Component\HttpFoundation\Response::HTTP_NO_CONTENT);
            }
            return $this->redirectToRoute('department_index');
        }

        $template = $request->isXmlHttpRequest() ? '_form.html.twig' : 'new.html.twig';
        return $this->render('department/' . $template, [
            'department' => $department,
            'form' => $form,
        ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200,));
    }

    /**
     * Show the department form specified by id.
     * The department can't be changed
     */
    #[Route(path: '/{id}', name: 'department_show', methods: ['GET'])]
    public function show(Request $request, Department $department): Response
    {
        $form = $this->createForm(DepartmentType::class, $department, [
            'readonly' => true,
        ]);
        $template = $request->isXmlHttpRequest() ? '_form.html.twig' : 'show.html.twig';
        return $this->render('department/' . $template, [
            'department' => $department,
            'form' => $form,
            'readonly' => true
        ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200,));
    }

    /**
     * Renders the department form specified by id to edit it's fields
     */
    #[Route(path: '/{id}/edit', name: 'department_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Department $department, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(DepartmentType::class, $department, [
            'readonly' => false,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Department $department */
            $department = $form->getData();
            $em->persist($department);
            $em->flush();
        }

        $template = $request->isXmlHttpRequest() ? '_form.html.twig' : 'edit.html.twig';
        return $this->render('department/' . $template, [
            'department' => $department,
            'form' => $form,
            'readonly' => false
        ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200,));
    }

    #[Route(path: '/{id}/delete', name: 'department_delete', methods: ['DELETE'])]
    public function delete(Request $request, Department $id, EntityManagerInterface $em): Response
    {
        $em->remove($id);
        $em->flush();
        if (!$request->isXmlHttpRequest()) {
            return $this->redirectToRoute('department_index');
        } else {
            return new Response(null, \Symfony\Component\HttpFoundation\Response::HTTP_NO_CONTENT);
        }
    }
}
