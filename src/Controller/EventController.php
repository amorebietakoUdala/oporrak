<?php

namespace App\Controller;

use App\Entity\AntiquityDays;
use App\Entity\Event;
use App\Entity\EventType;
use App\Entity\Status;
use App\Entity\User;
use App\Entity\WorkCalendar;
use App\Form\EventFormType;
use App\Repository\AdditionalVacationDaysRepository;
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
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/{_locale}/event')]
#[IsGranted('ROLE_USER')]
class EventController extends AbstractController
{
    public function __construct(private readonly MailerInterface $mailer, private readonly TranslatorInterface $translator, private readonly StatsService $statsService, private readonly EventRepository $eventRepo, private readonly StatusRepository $statusRepo, private readonly WorkCalendarRepository $wcRepo, private readonly AntiquityDaysRepository $adRepo, private readonly EntityManagerInterface $em, private readonly AdditionalVacationDaysRepository $avdRepo, private readonly int $daysForApproval = 15)
    {
    }

    #[Route(path: '/{id}/approve', name: 'event_approve', methods: ['GET'], options: ['expose' => true])]
    #[IsGranted('ROLE_BOSS')]
    public function approve(Request $request, Event $event = null): Response
    {
        if (null !== $event) {
            if ($event->getStatus()->getId() === Status::APPROVED) {
                $this->addFlash('success', 'message.alreadyApproved');
                return $this->render('event/confirmation.html.twig');
            }
            if ($event->getUser()->getBoss()->getUserIdentifier() !== $this->getUser()->getUserIdentifier() && !$this->isGranted('ROLE_HHRR')){
                $this->addFlash('error', 'message.notAuthorizedToApprove');
            } else {
                $event->setStatus($this->statusRepo->find(Status::APPROVED));
                $this->em->persist($event);
                $this->em->flush();
                $this->addFlash('success', 'message.approved');
                $html = $this->renderView('event/eventConfirmationMail.html.twig', [
                    'event' => $event
                ]);
                $user = $event->getUser();
                $subject = "{$user->getUserIdentifier()} opor eskaera erantzuna / Respuesta solicitud de vacaciones de {$user->getUserIdentifier()}";
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

    #[Route(path: '/{id}/deny', name: 'event_deny', methods: ['GET'], options: ['expose' => true])]
    #[IsGranted('ROLE_BOSS')]
    public function deny(Request $request, Event $event = null): Response
    {
        if (null !== $event) {
            if ($event->getStatus()->getId() === Status::NOT_APPROVED) {
                $this->addFlash('success', 'message.alreadyNotApproved');
                return $this->render('event/confirmation.html.twig');
            }
            if ($event->getUser()->getBoss()->getUserIdentifier() !== $this->getUser()->getUserIdentifier()&& !$this->isGranted('ROLE_HHRR')){
                $this->addFlash('error', 'message.notAuthorizedToDeny');
            } else {
                $event->setStatus($this->statusRepo->find(Status::NOT_APPROVED));
                $this->em->persist($event);
                $this->em->flush();
                $this->addFlash('success', 'message.notApproved');
                $html = $this->renderView('event/eventConfirmationMail.html.twig', [
                    'event' => $event
                ]);
                $user = $event->getUser();
                $subject = "{$user->getUserIdentifier()}-en opor eskaera erantzuna / Respuesta solicitud de vacaciones de {$user->getUserIdentifier()}";
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

    #[IsGranted('ROLE_HHRR')]
    #[Route(path: '/{id}/edit', name: 'event_edit', methods: ['GET', 'POST'], options: ['expose' => true])]
    public function edit(Event $event, Request $request): Response
    {
        $isCityHallReferer = $this->isRefererCityHallCalendar($request);
        $form = $this->createForm(EventFormType::class, $event, [
            'locale' => $request->getLocale(),
            'hhrr' => $this->isGranted('ROLE_HHRR'),
            'edit' => true,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Event $event */
            $event = $form->getData();
            if ($event->getStartDate() > $event->getEndDate()) {
                $this->addFlash('error', 'message.startDateGreaterThanEndDate');
                return $this->render('event/edit.html.twig', [
                    'form' => $form,
                    'hhrr' => $this->isGranted('ROLE_HHRR') && $isCityHallReferer, 
                ]);
            }
            $user = $this->getEffectiveUser($event);
            $year = $this->getEffectiveYear($event);
            /** @var WorkCalendar $workCalendar */
            $workCalendar = $this->wcRepo->findOneBy(['year' => $year]);
            // If any limitations exceeded it return the error directly
            $valid = $this->checkDoesNotExcessLimitations($event, $workCalendar, $user);
            if (!$valid) {
                return $this->render('event/edit.html.twig', [
                    'form' => $form,
                    'hhrr' => $this->isGranted('ROLE_HHRR') && $isCityHallReferer, 
                ]);
            }
            $this->em->persist($event);
            $this->em->flush();
        }
        $template = $request->isXmlHttpRequest() ? '_form.html.twig' : 'edit.html.twig';
        return $this->render('event/' . $template, [
            'form' => $form,
            'hhrr' => $this->isGranted('ROLE_HHRR'),
            ], new Response(null,$form->isSubmitted() && !$form->isValid() ? 422 : 200,)
        );
    }

    #[Route(path: '/{id}/delete', name: 'event_delete', methods: ['GET'], options: ['expose' => true])]
    public function delete(Event $event = null): Response
    {
        $days = $this->getParameter('days');
        $interval = new \DateInterval("P{$days}D");
        $interval->invert = 1;
        $deadline = (new \DateTime())->add($interval);
        $deadlineStr = $deadline->format('Y-m-d 23:59:59');
        $deadline = new \DateTime($deadlineStr);
        if ( (null !== $event && $event->getStartDate() > $deadline ) || $this->isGranted('ROLE_HHRR') ) {
            $this->em->remove($event);
            $this->em->flush();
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
            return new Response(null, \Symfony\Component\HttpFoundation\Response::HTTP_NO_CONTENT);
        } else {
            $message = $this->translator->trans('message.canNotDeletePastDay', [
                'deadline' => $deadline->format('Y-m-d'),
            ], 'messages');
            $response = new Response($message, \Symfony\Component\HttpFoundation\Response::HTTP_UNPROCESSABLE_ENTITY);
            return $response;
        }
        $this->addFlash('error', 'message.eventNotFound');
        return $this->redirectToRoute('calendar');
    }

    #[Route(path: '/new', name: 'event_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        return $this->save($request);
    }

    /**
     * Saves the received event. Could be new or a change on and existing one.
     *
     * When HTTP method is get serves and empty form.
     */
    #[Route(path: '/save', name: 'event_save', methods: ['GET', 'POST'])]
    public function save(Request $request): Response
    {
        $isCityHallReferer = $this->isRefererCityHallCalendar($request);
        //$event = new Event();
        $form = $this->createForm(EventFormType::class, null, [
            'days' => $this->getParameter('days'),
            'locale' => $request->getLocale(),
            'hhrr' => $this->isGranted('ROLE_HHRR') && $isCityHallReferer,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // When user has HHRR role and referer page is City Hall Calendar page, we don't send mails.
            $sendMail = $this->isGranted('ROLE_HHRR') && $isCityHallReferer ? false : true;
            /** @var Event $event */
            $event = $form->getData();
            if ($event->getId() !== null) {
                $event = $this->eventRepo->find($event->getId());
                $event->fill($form->getData());
            }
            if ($event->getStartDate() > $event->getEndDate()) {
                $this->addFlash('error', 'message.startDateGreaterThanEndDate');
                return $this->renderError($form, $request->isXmlHttpRequest(), $isCityHallReferer);
            }
            $user = $this->getEffectiveUser($event);
            $year = $this->getEffectiveYear($event);
            /** @var WorkCalendar $workCalendar */
            $workCalendar = $this->wcRepo->findOneBy(['year' => $year]);
            $currentYear = (new \DateTime())->format('Y');
            if ( $year !== $currentYear && $workCalendar === null ) {
                $this->addFlash('error', new TranslatableMessage('message.workCalendarNotDefined',['year' => $year]));
                return $this->renderError($form, $request->isXmlHttpRequest(), $isCityHallReferer);
            }
            // If any limitations exceeded it return the error directly
            $valid = $this->checkDoesNotExcessLimitations($event, $workCalendar, $user);
            if (!$valid) {
                return $this->renderError($form, $request->isXmlHttpRequest(), $isCityHallReferer);
            } else {
                if (!$this->isGranted('ROLE_HHRR') || !$isCityHallReferer ) {
                    $event->setStatus($this->statusRepo->find(Status::RESERVED));
                }
                $event->setUser($user);
                $event->setAskedAt(new \DateTime());
                $boss = $user->getBoss();
                if ($this->isGranted("ROLE_HHRR") && $event->getUser() !== $this->getUser()) {
                    $sendMail = false;
                }
                return $this->renderSuccess($event, $boss, $request->isXmlHttpRequest(), $sendMail);
            }
        }

        $template = $request->isXmlHttpRequest() ? '_form.html.twig' : 'new.html.twig';
        return $this->render('event/' . $template, [
            'form' => $form,
            'hhrr' => $this->isGranted('ROLE_HHRR') && $isCityHallReferer,
            ],  new Response(null,$form->isSubmitted() && !$form->isValid() ? 422 : 200,)
        );
    }

    /**
     * Returns valid if all limitations are passed. False in other case
     */
    private function checkDoesNotExcessLimitations(Event $event, WorkCalendar $workCalendar, User $user = null)
    {
        if ($user === null) {
            /** @var User $user */
            $user = $this->getUser();
        }

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

        if ($event->getType()->getId() === EventType::PARTICULAR_BUSSINESS_LEAVE && $event->getUsePreviousYearDays() ) {
            $this->addFlash('error', $this->translator->trans('message.particularBussinesLeaveDaysNotWithPreviousYearDays'));            
            return false;
        }
        if ( $event->getStartDate() > $workCalendar->getDeadlineNextYear() && 
             $event->getUser() === null && !$this->isGranted('ROLE_HHRR') ) {
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
            if (!$this->checkDoesNotExcessMaximumPartionableHours($user, $event, $event->getStartDate()->format('Y'), $workCalendar)) {
                return false;
            }
        }
        if ($event->getType()->getId() === EventType::ADDITONAL_VACATION_DAYS && $event->getStartDate() < new DateTime('2024-01-01')) {
            $this->addFlash('error', $this->translator->trans('message.addAdditionalVacationDaysOnlyAfter2023'));
            return false;
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

    private function renderSuccess($event, $boss, $ajax = false, $sendMail = true)
    {
        
        $this->em->persist($event);
        $this->em->flush();
        $overlaps = $this->eventRepo->findOverlapingEventsNotOfCurrentUser($event);
        if (null !== $boss) {
            $html = $this->renderView('event/eventApprovalMail.html.twig', [
                'event' => $event,
                'overlaps' => $overlaps,
                'daysForApproval' => $this->daysForApproval,
            ]);
            if ($sendMail) {
                $user = $event->getUser();
                $subject = "{$user->getUsername()}-en opor eskaera / Solicitud de vacaciones de {$user->getUsername()}";
                $this->sendEmail($boss->getEmail(), $subject, $html, false);
            }
        }
        if ($ajax) {
            return new Response(null, \Symfony\Component\HttpFoundation\Response::HTTP_NO_CONTENT);
        } else {
            return $this->redirectToRoute('myCalendar');
        }
    }

    /**
     * Returns true if it doesn't excess the maximum days, for that type and work calendar
     */
    private function checkDoesNotExcessMaximumDaysForType(User $user, Event $event, WorkCalendar $workCalendar): bool
    {
        $year = $event->getStartDate()->format('Y');
        $totals = $user->getTotals($workCalendar, $this->adRepo, $this->avdRepo, intval($year));
        $valid = true;
        $maxDays = $totals[$event->getType()->getId()];
        $valid = $this->checkDoesNotExcessMaximumDays($user, $event, $maxDays, $year, $workCalendar);
        return $valid;
    }

    private function checkDoesNotExcessMaximumPartionableHours(User $user, Event $event, $year, WorkCalendar $workCalendar): bool
    {
        $totalHours = 0;
        $eventsThisYear = $this->eventRepo->findUserEventsCurrentYearAndType($user, $year, $event->getType(), true);
        foreach ($eventsThisYear as $element) {
            if (null !== $element->getHours()) {
                $totalHours += $element->getHours();
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

    private function checkDoesNotExcessMaximumDays(User $user, Event $event, $maxDays, $year, WorkCalendar $workCalendar): bool
    {
        if (null !== $maxDays) {
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

    private function renderError($form, $ajax = false, $isCityHallReferer = false, $templateName = 'new.html.twig')
    {
        $template = $ajax ? '_form.html.twig' : $templateName;
        return $this->render('event/' . $template, [
            'form' => $form->createView(),
            'hhrr' => $this->isGranted('ROLE_HHRR') && $isCityHallReferer, 
        ], new Response(null, \Symfony\Component\HttpFoundation\Response::HTTP_UNPROCESSABLE_ENTITY));
    }

    /**
     * Returns true if the referer is City Hall calendar page.
     * 
     * @param Request $request
     * 
     * @return bool
     */
    private function isRefererCityHallCalendar($request): bool {
        $referer = $request->headers->get('referer');
        if ( $referer === null ) {
            return false;
        }
        $refererPathInfo = Request::create($referer)->getPathInfo();
        return $this->generateUrl('cityHallCalendar') === $refererPathInfo;
    }

    /**
     * Returns the effective year of the event. If it uses previousYearDays, the effective year is the previous year.
     * 
     * @param Event $event
     * 
     * @return int
     */
    private function getEffectiveYear($event): int {
        if ($event->getUsePreviousYearDays()) {
            $year = intval($event->getStartDate()->format('Y')) - 1;
        } else {
            $year = intval($event->getStartDate()->format('Y'));
        }
        return $year;
    }

    /**
     * Returns the effective user of the event. 
     * 
     * If event user is null we take authenticated user.
     * This is only for the case of human resources role that can create events for other users
     * 
     * @param Event $event
     * 
     * @return User
     */
    private function getEffectiveUser ($event): User {
        if ($event->getUser() === null) {
            /** @var User $user */
            $user = $this->getUser();
        } else {
            $user = $event->getUser();
        }
        return $user;
    }
}
