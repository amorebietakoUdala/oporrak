<?php

namespace App\Controller;

use App\Entity\AntiquityDays;
use App\Entity\Event;
use App\Entity\EventType;
use App\Entity\Status;
use App\Entity\User;
use App\Entity\WorkCalendar;
use App\Form\EventFormType;
use App\Repository\AntiquityDaysRepository;
use App\Repository\EventRepository;
use App\Repository\StatusRepository;
use App\Repository\WorkCalendarRepository;
use App\Services\StatsService;
use \DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/{_locale}/event")
 * @IsGranted("ROLE_USER")
 */
class EventController extends AbstractController
{
    private $mailer = null;
    private $translator = null;
    private $statsService = null;
    private EventRepository $eventRepo;
    private StatusRepository $statusRepo;
    private WorkCalendarRepository $wcRepo;
    private AntiquityDaysRepository $adRepo;

    public function __construct(MailerInterface $mailer, TranslatorInterface $translator, StatsService $statsService, EventRepository $eventRepo, StatusRepository $statusRepo, WorkCalendarRepository $wcRepo, AntiquityDaysRepository $adRepo)
    {
        $this->mailer = $mailer;
        $this->translator = $translator;
        $this->statsService = $statsService;
        $this->eventRepo = $eventRepo;
        $this->statusRepo = $statusRepo;
        $this->wcRepo = $wcRepo;
        $this->adRepo = $adRepo;
    }

    /**
     * @Route("/{event}/approve", name="event_approve", methods={"GET"}, options = { "expose" = true })
     * @IsGranted("ROLE_BOSS")
     */
    public function approve(Request $request, EntityManagerInterface $em, Event $event = null): Response
    {
        if (null !== $event) {
            if ($event->getStatus()->getId() === Status::APPROVED) {
                $this->addFlash('success', 'message.alreadyApproved');
                return $this->render('event/confirmation.html.twig');
            }
            if ($event->getUser()->getBoss()->getUsername() !== $this->getUser()->getUserIdentifier() && !$this->isGranted('ROLE_HHRR')){
                $this->addFlash('error', 'message.notAuthorizedToApprove');
            } else {
                $event->setStatus($this->statusRepo->find(Status::APPROVED));
                $em->persist($event);
                $em->flush();
                $this->addFlash('success', 'message.approved');
                $html = $this->renderView('event/eventConfirmationMail.html.twig', [
                    'event' => $event
                ]);
                $user = $event->getUser();
                $subject = "{$user->getUsername()} opor eskaera erantzuna / Respuesta solicitud de vacaciones de {$user->getUsername()}";
                $this->sendEmail($user->getEmail(), $subject, $html, true);
            }
        } else {
            $this->addFlash('error', 'message.eventNotFound');
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
    public function deny(Request $request, EntityManagerInterface $em, Event $event = null): Response
    {
        if (null !== $event) {
            if ($event->getStatus()->getId() === Status::NOT_APPROVED) {
                $this->addFlash('success', 'message.alreadyNotApproved');
                return $this->render('event/confirmation.html.twig');
            }
            if ($event->getUser()->getBoss()->getUsername() !== $this->getUser()->getUserIdentifier()&& !$this->isGranted('ROLE_HHRR')){
                $this->addFlash('error', 'message.notAuthorizedToDeny');
            } else {
                $event->setStatus($this->statusRepo->find(Status::NOT_APPROVED));
                $em->persist($event);
                $em->flush();
                $this->addFlash('success', 'message.notApproved');
                $html = $this->renderView('event/eventConfirmationMail.html.twig', [
                    'event' => $event
                ]);
                $user = $event->getUser();
                $subject = "{$user->getUsername()}-en opor eskaera erantzuna / Respuesta solicitud de vacaciones de {$user->getUsername()}";
                $this->sendEmail($user->getEmail(), $subject, $html, false);
            }
        } else {
            $this->addFlash('error', 'message.eventNotFound');
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
    public function delete(Event $event = null, EntityManagerInterface $em): Response
    {
        $days = $this->getParameter('days');
        $interval = new \DateInterval("P${days}D");
        $interval->invert = 1;
        $deadline = (new \DateTime())->add($interval);
        $deadlineStr = $deadline->format('Y-m-d 23:59:59');
        $deadline = new \DateTime($deadlineStr);
        if ( (null !== $event && $event->getStartDate() > $deadline ) || $this->isGranted('ROLE_HHRR') ) {
            $em->remove($event);
            $em->flush();
            /** @var User $user */
            $user = $this->getUser();
            $boss = $user->getBoss();
            if (null !== $boss && $this->getParameter('sendDeletionEmails')) {
                $html = $this->renderView('event/eventDeletionMail.html.twig', [
                    'event' => $event
                ]);
                $subject = "{$user->getUserIdentifier()}-en opor eskaera bertan behera uztea / Cancelación de vacaciones de {$user->getUserIdentifier()}";
                $this->sendEmail($boss->getEmail(), $subject, $html, false);
            }
            return new Response(null, 204);
        } else {
            $message = $this->translator->trans('message.canNotDeletePastDay', [
                'deadline' => $deadline->format('Y-m-d'),
            ], 'messages');
            $response = new Response($message, 422);
            return $response;
        }
        $this->addFlash('error', 'message.eventNotFound');
        return $this->redirectToRoute('calendar');
    }

    /**
     * @Route("/new", name="event_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        return $this->save($request);
    }

    /**
     * @Route("/save", name="event_save", methods={"GET","POST"})
     */
    public function save(Request $request): Response
    {
        $event = new Event();
        $form = $this->createForm(EventFormType::class, $event, [
            'days' => $this->getParameter('days'),
            'locale' => $request->getLocale(),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Event $event */
            $event = $form->getData();
            /** @var User $user */
            $user = $this->getUser();
            if (null === $event->getId()) {
                if ($event->getStartDate() > $event->getEndDate()) {
                    $this->addFlash('error', 'message.startDateGreaterThanEndDate');
                    $valid = false;
                }
                if ($event->getUsePreviousYearDays()) {
                    $year = intval($event->getStartDate()->format('Y')) - 1;
                } else {
                    $year = intval($event->getStartDate()->format('Y'));
                }
                /** @var WorkCalendar $workCalendar */
                $workCalendar = $this->wcRepo->findOneBy(['year' => $year]);
                // If any limitations exceeded it return the error directly
                $valid = $this->checkDoesNotExcessLimitations($event, $workCalendar);
                if ($valid) {
                    $event->setStatus($this->statusRepo->find(Status::RESERVED));
                    $event->setUser($this->getUser());
                    $event->setAskedAt(new \DateTime());
                    $boss = $user->getBoss();
                    return $this->renderSuccess($event, $boss, $request->isXmlHttpRequest());
                } else {
                    return $this->renderError($form, $request->isXmlHttpRequest());
                }
            }
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

    /**
     * Returns valid if all limitations are passed. False in other case
     */
    private function checkDoesNotExcessLimitations(Event $event, WorkCalendar $workCalendar)
    {
        /** @var User $user */
        $user = $this->getUser();
        if ( $event->getStartDate()->format('Y') !== $event->getEndDate()->format('Y') ) {
            $this->addFlash('error', new TranslatableMessage('message.betweenYears', [], 'messages'));
            return false;
        }

        if ( $user->getEndDate() !== null && ( $event->getStartDate() > $user->getEndDate() || $event->getEndDate() > $user->getEndDate() ) ) {
            $this->addFlash('error', new TranslatableMessage('message.endOfContract', [
                'endDate' => $user->getEndDate()->format('Y/m/d')
            ], 'messages'));
            return false;
        }

        if ( $user->getStartDate() !== null && ( $event->getStartDate() < $user->getStartDate() ) ) {
            $this->addFlash('error', new TranslatableMessage('message.startOfContract', [
                'startDate' => $user->getStartDate()->format('Y/m/d')
            ], 'messages'));
            return false;
        }

        /* Check overlap with my own events */
        $myEvents = $this->eventRepo->findUserEventsBeetweenDates($user, new \DateTime($event->getStartDate()->format('Y') . '-01-01'));
        if ($this->checkOverlap($myEvents, $event)) {
            $this->addFlash('error', new TranslatableMessage('message.overlapingDates', [
                'startDate' => $event->getStartDate()->format('Y/m/d'),
                'endDate' => $event->getEndDate()->format('Y/m/d')
            ], 'messages'));
            return false;
        }
        
        if ($event->getHalfDay() && $event->getStartDate() != $event->getEndDate() ) {
            $this->addFlash('error', 'message.partitionableDaysOneByOne');
            return false;
        }

        if ($event->getHalfDay() && ($event->getHours() < 2 || $event->getHours() > $workCalendar->getWorkingHours() / 2)) {
            $this->addFlash('error', $this->translator->trans('message.partitionableHoursMinAndMax', [
                'min' => "2",
                'max' => number_format($workCalendar->getWorkingHours() / 2, 2),
            ]));
            return false;
        }
        $year = intval($event->getStartDate()->format('Y'));
        if (new \DateTime() < new \DateTime("${year}-01-01") && ($event->getUsePreviousYearDays() === null || $event->getUsePreviousYearDays() === false)) {
            $this->addFlash('error', $this->translator->trans('message.canNotAskBeforeDate', [
                'year' => $year,
                'startDate' => "${year}-01-01",
            ]));
            return false;
        }
        if ($event->getType()->getId() === EventType::PARTICULAR_BUSSINESS_LEAVE && 
            intval(($event->getStartDate())->format('Y')) > intval((new DateTime())->format('Y')) || 
            intval(($event->getEndDate())->format('Y')) > intval((new DateTime())->format('Y')) ) 
        {
            $this->addFlash('error', $this->translator->trans('message.particularBussinesLeaveDaysOnlyCurrentYear'));            
            return false;
        }
        if ($event->getType()->getId() === EventType::PARTICULAR_BUSSINESS_LEAVE && $event->getUsePreviousYearDays() ) {
            $this->addFlash('error', $this->translator->trans('message.particularBussinesLeaveDaysNotWithPreviousYearDays'));            
            return false;
        }
        if ($event->getStartDate() > $workCalendar->getDeadlineNextYear()) {
            $this->addFlash('error', $this->translator->trans('message.deadLineNextYearExceeded', [
                'deadline' => $workCalendar->getDeadlineNextYear()->format('Y-m-d'),
            ]));
            return false;
        }
        if ($event->getType()->getId() !== EventType::PARTICULAR_BUSSINESS_LEAVE && $event->getHalfDay()) {
            $this->addFlash('error', $this->translator->trans('message.partitionableDaysType', [
                'hours' => $workCalendar->getPartitionableHours(),
                'year' => $event->getStartDate()->format('Y'),
            ]));
            return false;
        }
        if ($event->getType()->getId() === EventType::PARTICULAR_BUSSINESS_LEAVE && $event->getHalfDay()) {
            if (!$this->checkDoesNotExcessMaximumPartionableHours($event, $event->getStartDate()->format('Y'), $workCalendar)) {
                return false;
            }
        }
        if (!$this->checkDoesNotExcessMaximumDaysForType($user, $event, $workCalendar)) {
            return false;
        }
        return true;
    }

    private function sendEmail($to, $subject, $html, bool $sendToHHRR)
    {
        $email = (new Email())
            ->from($this->getParameter('mailerFrom'))
            ->to($to)
            ->subject($subject)
            ->html($html);
        $addresses = [];
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

    private function renderSuccess($event, $boss, $ajax = false)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($event);
        $entityManager->flush();
        $overlaps = $this->eventRepo->findOverlapingEventsNotOfCurrentUser($event);
        if (null !== $boss) {
            $html = $this->renderView('event/eventApprovalMail.html.twig', [
                'event' => $event,
                'overlaps' => $overlaps
            ]);
            $user = $event->getUser();
            $subject = "{$user->getUsername()}-en opor eskaera / Solicitud de vacaciones de {$user->getUsername()}";
            $this->sendEmail($boss->getEmail(), $subject, $html, false);
        }
        if ($ajax) {
            return new Response(null, 204);
        } else {
            return $this->redirectToRoute('myCalendar');
        }
    }

    /**
     * Returns true if it doesn't excess the maximum days, for that type and work calendar
     */
    private function checkDoesNotExcessMaximumDaysForType(User $user, Event $event, WorkCalendar $workCalendar)
    {
        $year = $event->getStartDate()->format('Y');
        $totals = $user->getTotals($workCalendar, $this->adRepo);
        $valid = true;
        $maxDays = $totals[$event->getType()->getId()];
        $valid = $this->checkDoesNotExcessMaximumDays($event, $maxDays, $year, $workCalendar);
        return $valid;
    }

    private function checkDoesNotExcessMaximumPartionableHours(Event $event, $year, WorkCalendar $workCalendar)
    {
        $user = $this->getUser();
        $totalHours = 0;
        $eventsThisYear = $this->eventRepo->findUserEventsCurrentYearAndType($user, $year, $event->getType(), true);
        foreach ($eventsThisYear as $event) {
            if (null !== $event->getHours()) {
                $totalHours += $event->getHours();
            }
        }
        if ($totalHours + $event->getHours() > $workCalendar->getPartitionableHours()) {
            $this->addFlash('error', $this->translator->trans('message.partitionableHoursExceeded', [
                'maximumHours' => $workCalendar->getPartitionableHours(),
                'hours' => $totalHours + $event->getHours(),
                'year' => $event->getStartDate()->format('Y'),
            ]));
            return false;
        }
        return true;
    }

    private function checkDoesNotExcessMaximumDays(Event $event, $maxDays, $year, WorkCalendar $workCalendar)
    {
        if (null !== $maxDays) {
            $user = $this->getUser();
            $eventsThisYear = $this->eventRepo->findEffectiveUserEventsOfTheYear($user, $year, $event->getType(),false);
            $workingDays = $this->statsService->calculateTotalWorkingDays($eventsThisYear, $workCalendar);
            if ($workingDays + $this->statsService->calculateWorkingDays($event, $workCalendar) > $maxDays) {
                $this->addFlash(
                    'error',
                    $this->translator->trans('message.maximum_' . $event->getType()->getId() . '_days_exceeded', [
                        'days' => $workingDays + $this->statsService->calculateWorkingDays($event, $workCalendar) - $maxDays
                    ])
                );
                return false;
            }
        }
        return true;
    }

    private function renderError($form, $ajax = false)
    {
        $template = $ajax ? '_form.html.twig' : 'new.html.twig';
        return $this->render('event/' . $template, [
            'event' => $form->getData(),
            'form' => $form->createView(),
        ], new Response(null, 422));
    }
}
