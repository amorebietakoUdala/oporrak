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

/**
 * @Route("/{_locale}/event")
 */
class EventController extends AbstractController
{
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
                    $this->sendEmail($mailer, $boss->getEmail(), $event);
                }
                if ($request->isXmlHttpRequest()) {
                    return new Response(null, 204);
                }
            } else {
                $bdEvent = $entityManager->getRepository(Event::class)->find($event->getId());
                $bdEvent->fill($event);
                $entityManager->persist($bdEvent);
                $entityManager->flush();
                if (null !== $boss) {
                    $this->sendEmail($mailer, $boss->getEmail(), $event);
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

    private function sendEmail(MailerInterface $mailer, $to, Event $event)
    {
        $html = $this->renderView('event/mail.html.twig', [
            'event' => $event
        ]);
        $email = (new Email())
            ->from($this->getParameter('mailerFrom'))
            ->to($to)
            ->bcc($this->getParameter('mailerFrom'))
            //->bcc('bcc@example.com')
            //->replyTo('fabien@example.com')
            //->priority(Email::PRIORITY_HIGH)
            ->subject($this->getParameter('mailerSubject'))
            ->html($html);
        $mailer->send($email);
    }
}
