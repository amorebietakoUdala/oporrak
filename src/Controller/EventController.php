<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Status;
use App\Form\EventFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @Route("/{_locale}/event")
 */
class EventController extends AbstractController
{
    /**
     * @Route("/{event}/approve", name="event_approve", methods={"GET"}, options = { "expose" = true })
     * @IsGranted("ROLE_BOSS")
     */
    public function approve(Event $event, Request $request, MailerInterface $mailer): Response
    {
        if (null !== $event) {
            if ($event->getStatus()->getId() === Status::APPROVED) {
                $this->addFlash('success', 'event.alreadyApproved');
                return $this->render('event/confirmation.html.twig');
            }
            $event->setStatus($this->getDoctrine()->getRepository(Status::class)->find(Status::APPROVED));
            $em = $this->getDoctrine()->getManager();
            $em->persist($event);
            $em->flush();
            $this->addFlash('success', 'event.approved');
            $html = $this->renderView('event/eventConfirmationMail.html.twig', [
                'event' => $event
            ]);
            $user = $event->getUser();
            $subject = 'Opor eskaera erantzuna / Respuesta solicitud de vacaciones';
            $this->sendEmail($mailer, $user->getEmail(), $subject, $html);
        } else {
            $this->addFlash('error', 'event.notFound');
        }
        return $this->render('event/confirmation.html.twig');
    }

    /**
     * @Route("/{event}/deny", name="event_deny", methods={"GET"}, options = { "expose" = true })
     * @IsGranted("ROLE_BOSS")
     */
    public function deny(Event $event, Request $request, MailerInterface $mailer): Response
    {
        if (null !== $event) {
            if ($event->getStatus()->getId() === Status::NOT_APPROVED) {
                $this->addFlash('success', 'event.alreadyNotApproved');
                return $this->render('event/confirmation.html.twig');
            }
            $event->setStatus($this->getDoctrine()->getRepository(Status::class)->find(Status::NOT_APPROVED));
            $em = $this->getDoctrine()->getManager();
            $em->persist($event);
            $em->flush();
            $this->addFlash('success', 'event.notApproved');
            $html = $this->renderView('event/eventConfirmationMail.html.twig', [
                'event' => $event
            ]);
            $user = $event->getUser();
            $subject = 'Opor eskaera erantzuna / Respuesta solicitud de vacaciones';
            $this->sendEmail($mailer, $user->getEmail(), $subject, $html);
        } else {
            $this->addFlash('error', 'event.notFound');
        }
        return $this->render('event/confirmation.html.twig');
    }

    /**
     * @Route("/{event}/delete", name="event_delete", methods={"GET"}, options = { "expose" = true })
     */
    public function delete(Event $event = null): Response
    {
        if (null !== $event) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($event);
            $em->flush();

            return new Response(null, 204);
        }
        $this->addFlash('error', 'message.eventNotFound');
        return $this->redirectToRoute('calendar');
    }

    /**
     * @Route("/save", name="event_save", methods={"GET","POST"})
     */
    public function save(Request $request, MailerInterface $mailer): Response
    {
        $event = new Event();
        $form = $this->createForm(EventFormType::class, $event);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $event = $form->getData();
            /** @var User $user */
            $user = $this->getUser();
            $boss = $user->getBoss();
            $entityManager = $this->getDoctrine()->getManager();
            if (null === $event->getId()) {
                $event->setStatus($this->getDoctrine()->getRepository(Status::class)->find(Status::RESERVED));
                $event->setUser($this->getUser());
                $entityManager->persist($event);
                $entityManager->flush();
                if (null !== $boss) {
                    $html = $this->renderView('event/eventApprovalMail.html.twig', [
                        'event' => $event
                    ]);
                    $subject = 'Opor eskaera / Solicitud de vacaciones';
                    $this->sendEmail($mailer, $boss->getEmail(), $subject, $html);
                }
                if ($request->isXmlHttpRequest()) {
                    return new Response(null, 204);
                }
            } else {
                $bdEvent = $entityManager->getRepository(Event::class)->find($event->getId());
                $bdEvent->fill($event);
                $bdEvent->setUser($user);
                $entityManager->persist($bdEvent);
                $entityManager->flush();
                if (null !== $boss) {
                    $html = $this->renderView('event/eventApprovalMail.html.twig', [
                        'event' => $event
                    ]);
                    $subject = 'Opor eskaera / Solicitud de vacaciones';
                    $this->sendEmail($mailer, $boss->getEmail(), $subject, $html);
                }
                if ($request->isXmlHttpRequest()) {
                    return new Response(null, 204);
                }
            }
            return $this->redirectToRoute('calendar');
        }

        $template = $request->isXmlHttpRequest() ? '_form.html.twig' : 'new.html.twig';

        return $this->render('event/' . $template, [
            'event' => $event,
            'form' => $form->createView(),
        ], new Response(
            null,
            $form->isSubmitted() && !$form->isValid() ? 422 : 200,
        ));
    }

    private function sendEmail(MailerInterface $mailer, $to, $subject, $html)
    {
        $email = (new Email())
            ->from($this->getParameter('mailerFrom'))
            ->to($to)
            ->bcc($this->getParameter('mailerFrom'))
            //->bcc('bcc@example.com')
            //->replyTo('fabien@example.com')
            //->priority(Email::PRIORITY_HIGH)
            ->subject($subject)
            ->html($html);
        $mailer->send($email);
    }

    /**
     * @Route("/{event}/days", name="event_days", methods={"GET"})
     */
    public function days(Event $event = null)
    {
        if (null !== $event) {
            return $this->json($event->getDays());
        }
    }
}
