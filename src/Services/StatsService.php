<?php

namespace App\Services;

use App\Entity\Event;
use App\Entity\Status;
use App\Entity\WorkCalendar;
use App\Repository\AdditionalVacationDaysRepository;
use App\Repository\AntiquityDaysRepository;
use App\Repository\HolidayRepository;
use App\Repository\UserRepository;
use App\Repository\WorkCalendarRepository;

class StatsService
{
   public function __construct(
      private readonly WorkCalendarRepository $wcRepo, 
      private readonly HolidayRepository $holidayRepo, 
      private readonly AntiquityDaysRepository $adRepo, 
      private readonly UserRepository $userRepo, 
      private readonly AdditionalVacationDaysRepository $avdRepo,
      private readonly DaysFormattingService $daysFormattingService,
      )
   {
   }

   public function calculateTotalWorkingDays(array $events, $workCalendar)
   {
      $totalWorkingDays = 0;
      foreach ($events as $event) {
         if ($event->getStatus()->getId() !== Status::NOT_APPROVED) {
            if ( !$event->isBetweenYears() ) {
               $workingDays = $this->calculateWorkingDays($event, $workCalendar);               
            } else {
               $year = $workCalendar->getYear();
               $nextYear = $year + 1;
               $eventStartYear = intval($event->getStartDate()->format('Y'));
               $thisYearEndDate = new \DateTime("$year-12-31");
               $thisYearStartDate = new \DateTime("$year-01-01");
               $nextYearStartDate = new \DateTime("$nextYear-01-01");
               if ( $year === $eventStartYear ) {
                  $dummyEvent = new Event();
                  $dummyEvent->setStartDate($event->getStartDate());
                  $dummyEvent->setEndDate($thisYearEndDate);
               } else {
                  $dummyEvent = new Event();
                  $dummyEvent->setStartDate($thisYearStartDate);
                  $dummyEvent->setEndDate($event->getEndDate());
               }
               $workingDays = $this->calculateWorkingDays($dummyEvent, $workCalendar);
            }
            $totalWorkingDays += $workingDays;
         }
      }
      return $totalWorkingDays;
   }

   public function calculateStatsByUserAndEventType(array $events) {
      $counters = [];
      foreach($events as $event) {
         $year = $event->getStartDate()->format('Y');
         $workCalendars = [];
         $workCalendars[$year] = $this->wcRepo->findOneBy(['year' => $year]);          
         $workCalendars[$year-1] = $this->wcRepo->findOneBy(['year' => $year-1]);
         if ( !$event->getUsePreviousYearDays() ) {
            $workingDays = $this->calculateWorkingDays($event, $workCalendars[$year]);
         } else {
            $workingDays = $this->calculateWorkingDays($event, $workCalendars[$year-1]);
         }
         $userId = "{$event->getUser()->getUsername()}";
         $typeId = "{$event->getType()->getId()}";
         if ($event->getStatus()->getId() !== Status::NOT_APPROVED ) {
            if ( array_key_exists($userId, $counters) ) {
               if ( array_key_exists($typeId, $counters[$userId]) ) {
                  $counters[$userId][$typeId] += $workingDays;
               } else {
                  $counters[$userId][$typeId] = $workingDays;
               }
            } else {
               $counters[$userId][$typeId] = $workingDays;
            }
         }
      }
      return $counters;
   }

   public function calculateStatsByStatus(array $events, int $year) {
      $counters = [];
      foreach($events as $event) {
         $workCalendars = [];
         $workCalendars[$year] = $this->wcRepo->findOneBy(['year' => $year]);          
         $workCalendars[$year-1] = $this->wcRepo->findOneBy(['year' => $year-1]);
         if ( !$event->getUsePreviousYearDays() ) {
            $workingDays = $this->calculateWorkingDays($event, $workCalendars[$year]);
         } else {
            $workingDays = $this->calculateWorkingDays($event, $workCalendars[$year-1]);
         }

         $statusId = "{$event->getStatus()->getId()}";
         if ( array_key_exists($statusId, $counters) ) {
            $counters[$statusId] = $counters[$statusId] + $workingDays;
         } else {
            $counters[$statusId] = $workingDays;
         }
      }
      return $counters;
   }

   /**
    * @return array
    */
   private function calculateTotalsForUsernamesAndYear(array $usernames, int $year) {
      $counters = [];
      $workCalendar = $this->wcRepo->findOneBy(['year' => $year]);
      foreach($usernames as $username) {
         $user = $this->userRepo->findOneBy(['username' => $username]);
         if ($user !== null) {
            $counters[$username]['total'] = $user->calculateCurrentYearBaseDays($workCalendar, $year);
            $antiquityDays = $this->adRepo->findAntiquityDaysForYearsWorked($user->getYearsWorked());
            if ( null !== $antiquityDays ) {
               $counters[$username]['total'] = $counters[$username]['total'] + $antiquityDays->getVacationDays();
            }
            $additionalVacationDays = $this->avdRepo->findAdditionalVacationDaysForYearsWorked($user->getYearsWorked());
            if ( null !== $additionalVacationDays ) {
               $counters[$username]['total'] = $counters[$username]['total'] + $additionalVacationDays->getVacationDays();
            }
         }
      }
      return $counters;

   }

   public function calculateStatsByUserAndStatus(array $events, int $year, array $usernames) {
      $counters = $this->calculateTotalsForUsernamesAndYear($usernames,$year);
      foreach($events as $event) {
         $workCalendars = [];
         $workCalendars[$year] = $this->wcRepo->findOneBy(['year' => $year]);          
         $workCalendars[$year-1] = $this->wcRepo->findOneBy(['year' => $year-1]);
         if ( !$event->getUsePreviousYearDays() ) {
            $workingDays = $this->calculateWorkingDays($event, $workCalendars[$year]);
         } else {
            $workingDays = $this->calculateWorkingDays($event, $workCalendars[$year-1]);
         }
         $statusId = "{$event->getStatus()->getId()}";
         $username = "{$event->getUser()->getUsername()}";
         if ( array_key_exists($username, $counters) ) {
            if ( array_key_exists($statusId, $counters[$username]) ) {
               $counters[$username][$statusId] = $counters[$username][$statusId] + $workingDays;
            } else {
               $counters[$username][$statusId] = $workingDays;
            }
         } else {
            $counters[$username][$statusId] = $workingDays;
         }
         $counters[$username]['remaining'] = $counters[$username]["total"] - $counters[$username][$statusId];
      }
      return $counters;
   }

   public function calculateStatsByUser(array $events, int $year) {
      $counters = [];
      foreach($events as $event) {
         $workCalendars = [];
         $workCalendars[$year] = $this->wcRepo->findOneBy(['year' => $year]);          
         $workCalendars[$year-1] = $this->wcRepo->findOneBy(['year' => $year-1]);
         if ( !$event->getUsePreviousYearDays() ) {
            $workingDays = $this->calculateWorkingDays($event, $workCalendars[$year]);
         } else {
            $workingDays = $this->calculateWorkingDays($event, $workCalendars[$year-1]);
         }

         $user = "{$event->getUser()->getUsername()}";
         if ( array_key_exists($user, $counters) ) {
               $counters[$user] = $counters[$user] + $workingDays;
         } else {
            $counters[$user] = $workingDays;
         }
      }
      return $counters;
   }

   public function calculateWorkingDays(Event $event, WorkCalendar $workCalendar) {
       if (!$event->getHalfDay()) {
           $holidays = $this->holidayRepo->findHolidaysBetween($event->getStartDate(), $event->getEndDate());
           // Only in working days Saturdays and Sundays don't count.
           $holidaysBetween = $this->calculateHolidaysOnWorkingDays( $holidays );
           $workingDays = $event->getDays();
           // Subtract two weekend days for every week in between
           $weeks = floor($workingDays / 7);
           $workingDays -= $weeks * 2;
           // Handle special cases
           $startDay = $this->getWeekday($event->getStartDate()->format('Y-m-d'));
           $endDay = $this->getWeekday($event->getEndDate()->format('Y-m-d'));
           // Remove weekend not previously removed.   
           if ($startDay - $endDay > 1) {
               $workingDays -= 2;
           }
           // Remove start day if span starts on Sunday but ends before Saturday
           if ($startDay == 0 && $endDay < 6) {
               $workingDays--;
           }
           // Remove end day if span ends on Saturday but starts after Sunday
           if ($endDay == 6 && $startDay > 0) {
               $workingDays--;
           }
           $workingDays -= $holidaysBetween;
           return $workingDays;
       } else {
            return $event->getEventTotalMinutes() / $workCalendar->getTotalWorkingMinutes();
//           return $workingDays = $event->getHoursDecimal() / $workCalendar->getWorkingHoursDecimal();
       }
   }

   private function calculateHolidaysOnWorkingDays($holidays) {
      $holidaysOnWorkingDays = 0;
      foreach ($holidays as $holiday) {
         if ($this->getWeekday($holiday->getDate()->format('Y-m-d')) !== 0 && $this->getWeekday($holiday->getDate()->format('Y-m-d')) !== 6 ) {
            $holidaysOnWorkingDays += 1;
         }
      }
      return $holidaysOnWorkingDays;
   }

   private function getWeekday($date) {
      return intval(date('w', strtotime((string) $date)));
   }

   public function formatCounterAsDaysHoursAndMinutes(array $counters, WorkCalendar $wc): array {
      $formattedCounters = [];
      foreach ( $counters as $key => $value ) {
         $formattedCounters[$key] = $this->daysFormattingService->calcularDiasHorasMinutosJornadaWorkCalendarString($value,$wc);
      }
      return $formattedCounters;
   }

   public function formatStatsAsDaysHoursAndMinutes(array $stats, WorkCalendar $workCalendar): array {
      $newStats  = [];
      foreach ($stats as $key => $value) {
         if ( is_array($value) ) {
            foreach ( $value as $key2 => $item ) {
               $newStats[$key][$key2] = $this->daysFormattingService->calcularDiasHorasMinutosJornadaWorkCalendarString($item,$workCalendar);
            }
         }
      }
      return $newStats;
   }

}