<?php

namespace App\Controller;

use App\Entity\Department;
use App\Form\DepartmentType;
use App\Repository\DepartmentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @Route("/{_locale}/department")
 * @IsGranted("ROLE_USER")
 */
class DepartmentController extends AbstractController
{
    /**
     * List all the departments
     * 
     * @Route("/", name="department_index", methods={"GET"})
     */
    public function index(DepartmentRepository $departmentRepository, Request $request): Response
    {
        $ajax = $request->get('ajax') !== null ? $request->get('ajax') : "false";
        if ($ajax === "false") {
            $department = new Department();
            $form = $this->createForm(DepartmentType::class, $department);
            return $this->render('department/index.html.twig', [
                'departments' => $departmentRepository->findAll(),
                'form' => $form->createView(),
            ]);
        } else {
            return $this->render('department/_list.html.twig', [
                'departments' => $departmentRepository->findAll(),
            ]);
        }
    }

    /**
     * Creates or updates a department
     * 
     * @Route("/new", name="department_save", methods={"GET","POST"})
     */
    public function createOrSave(Request $request, DepartmentRepository $repo): Response
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
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($department);
            $entityManager->flush();

            if ($request->isXmlHttpRequest()) {
                return new Response(null, 204);
            }
            return $this->redirectToRoute('department_index');
        }

        $template = $request->isXmlHttpRequest() ? '_form.html.twig' : 'new.html.twig';
        return $this->render('department/' . $template, [
            'department' => $department,
            'form' => $form->createView(),
        ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200,));
    }

    /**
     * Show the department form specified by id.
     * The department can't be changed
     * 
     * @Route("/{id}", name="department_show", methods={"GET"})
     */
    public function show(Request $request, Department $department): Response
    {
        $form = $this->createForm(DepartmentType::class, $department, [
            'readonly' => true,
        ]);
        $template = $request->isXmlHttpRequest() ? '_form.html.twig' : 'show.html.twig';
        return $this->render('department/' . $template, [
            'department' => $department,
            'form' => $form->createView(),
            'readonly' => true
        ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200,));
    }

    /**
     * Renders the department form specified by id to edit it's fields
     * 
     * @Route("/{id}/edit", name="department_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Department $department): Response
    {
        $form = $this->createForm(DepartmentType::class, $department, [
            'readonly' => false,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Department $department */
            $department = $form->getData();
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($department);
            $entityManager->flush();
        }

        $template = $request->isXmlHttpRequest() ? '_form.html.twig' : 'edit.html.twig';
        return $this->render('department/' . $template, [
            'department' => $department,
            'form' => $form->createView(),
            'readonly' => false
        ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200,));
    }

    /**
     * @Route("/{id}/delete", name="department_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Department $id): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($id);
        $entityManager->flush();
        if (!$request->isXmlHttpRequest()) {
            return $this->redirectToRoute('department_index');
        } else {
            return new Response(null, 204);
        }
    }
}
