<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Status;
use App\Form\EventFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * @Route("/{_locale}/event")
 * @IsGranted("ROLE_OPORRAK")
 */
class EventController extends AbstractController
{
    private $mailer = null;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * @Route("/{event}/approve", name="event_approve", methods={"GET"}, options = { "expose" = true })
     * @IsGranted("ROLE_BOSS")
     */
    public function approve(Event $event, Request $request): Response
    {
        if (null !== $event) {
            if ($event->getStatus()->getId() === Status::APPROVED) {
                $this->addFlash('success', 'message.alreadyApproved');
                return $this->render('event/confirmation.html.twig');
            }
            $event->setStatus($this->getDoctrine()->getRepository(Status::class)->find(Status::APPROVED));
            $em = $this->getDoctrine()->getManager();
            $em->persist($event);
            $em->flush();
            $this->addFlash('success', 'message.approved');
            $html = $this->renderView('event/eventConfirmationMail.html.twig', [
                'event' => $event
            ]);
            $user = $event->getUser();
            $subject = 'Opor eskaera erantzuna / Respuesta solicitud de vacaciones';
            $this->sendEmail($user->getEmail(), $subject, $html, true);
        } else {
            $this->addFlash('error', 'event.notFound');
        }
        $return = $request->get('return');
        if (null !== $return) {
            return $this->redirect($return);
        } else {
            return $this->redirectToRoute('departmentCalendar');
        }
    }

    /**
     * @Route("/{event}/deny", name="event_deny", methods={"GET"}, options = { "expose" = true })
     * @IsGranted("ROLE_BOSS")
     */
    public function deny(Event $event, Request $request): Response
    {
        if (null !== $event) {
            if ($event->getStatus()->getId() === Status::NOT_APPROVED) {
                $this->addFlash('success', 'message.alreadyNotApproved');
                return $this->render('event/confirmation.html.twig');
            }
            $event->setStatus($this->getDoctrine()->getRepository(Status::class)->find(Status::NOT_APPROVED));
            $em = $this->getDoctrine()->getManager();
            $em->persist($event);
            $em->flush();
            $this->addFlash('success', 'message.notApproved');
            $html = $this->renderView('event/eventConfirmationMail.html.twig', [
                'event' => $event
            ]);
            $user = $event->getUser();
            $subject = 'Opor eskaera erantzuna / Respuesta solicitud de vacaciones';
            $this->sendEmail($user->getEmail(), $subject, $html, false);
        } else {
            $this->addFlash('error', 'event.notFound');
        }
        $return = $request->get('return');
        if (null !== $return) {
            return $this->redirect($return);
        } else {
            return $this->redirectToRoute('departmentCalendar');
        }
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
            $user = $this->getUser();
            $boss = $user->getBoss();
            if (null !== $boss && $this->getParameter('sendDeletionEmails')) {
                $html = $this->renderView('event/eventDeletionMail.html.twig', [
                    'event' => $event
                ]);
                $subject = 'Opor eskaera bertan behera uztea / Cancelación de vacaciones';
                $this->sendEmail($boss->getEmail(), $subject, $html, false);
            }
            return new Response(null, 204);
        }
        $this->addFlash('error', 'message.eventNotFound');
        return $this->redirectToRoute('calendar');
    }

    /**
     * @Route("/save", name="event_save", methods={"GET","POST"})
     */
    public function save(Request $request): Response
    {
        $event = new Event();
        $form = $this->createForm(EventFormType::class, $event, [
            'days' => $this->getParameter('days')
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $event = $form->getData();
            /** @var User $user */
            $user = $this->getUser();
            $boss = $user->getBoss();
            $entityManager = $this->getDoctrine()->getManager();
            if (null === $event->getId()) {
                $myEvents = $this->getDoctrine()->getRepository(Event::class)->findUserEventsBeetweenDates($user, new \DateTime($event->getStartDate()->format('Y') . '-01-01'));
                if ($this->checkOverlap($myEvents, $event)) {
                    return $this->renderOverlapError($event, $form, $request->isXmlHttpRequest());
                }
                $event->setStatus($this->getDoctrine()->getRepository(Status::class)->find(Status::RESERVED));
                $event->setUser($this->getUser());
                return $this->renderSuccess($event, $boss, $request->isXmlHttpRequest());
            }
            // For now is not necesary because can't edit an event
            // else {
            //     $bdEvent = $entityManager->getRepository(Event::class)->find($event->getId());
            //     $bdEvent->fill($event);
            //     $bdEvent->setUser($user);
            //     return $this->renderSuccess($bdEvent, $boss, $request->isXmlHttpRequest());
            // }
            return $this->redirectToRoute('calendar');
        }

        $template = $request->isXmlHttpRequest() ? '_form.html.twig' : 'new.html.twig';

        return $this->render('event/' . $template, [
            'event' => $form->getData(),
            'form' => $form->createView(),
        ], new Response(
            null,
            $form->isSubmitted() && !$form->isValid() ? 422 : 200,
        ));
    }

    private function sendEmail($to, $subject, $html, bool $sendToHHRR)
    {
        $email = (new Email())
            ->from($this->getParameter('mailerFrom'))
            ->to($to)
            ->subject($subject)
            ->html($html);
        $addresses = [$this->getParameter('mailerFrom')];
        if ($sendToHHRR) {
            $addresses[] = $this->getParameter('mailHHRR');
        }
        foreach ($addresses as $address) {
            $email->addBcc($address);
        }
        $this->mailer->send($email);
    }

    private function checkOverlap(array $allEvents, Event $event)
    {
        foreach ($allEvents as $myEvent) {
            $overlap = $myEvent->checkOverlap($event);
            if ($overlap) {
                return true;
            }
        }
        return false;
    }

    private function renderOverlapError($event, $form, $ajax = false)
    {
        $this->addFlash('error', new TranslatableMessage('message.overlapingDates', [
            'startDate' => $event->getStartDate()->format('Y/m/d'),
            'endDate' => $event->getEndDate()->format('Y/m/d')
        ], 'messages'));
        $template = $ajax ? '_form.html.twig' : 'new.html.twig';

        return $this->render('event/' . $template, [
            'event' => $form->getData(),
            'form' => $form->createView(),
        ], new Response(null, 422));
    }

    private function renderSuccess($event, $boss, $ajax = false)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($event);
        $entityManager->flush();
        $overlaps = $this->getDoctrine()->getRepository(Event::class)->findOverlapingEventsNotOfCurrentUser($event);
        if (null !== $boss) {
            $html = $this->renderView('event/eventApprovalMail.html.twig', [
                'event' => $event,
                'overlaps' => $overlaps
            ]);
            $subject = 'Opor eskaera / Solicitud de vacaciones';
            $this->sendEmail($boss->getEmail(), $subject, $html, false);
        }
        if ($ajax) {
            return new Response(null, 204);
        }
    }
}
