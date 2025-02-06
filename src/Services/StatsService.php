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

   /**
    * It returns an array of usernames and their total event days.
    * If $formattedCounters is set to true. Total days are returned as user friendly comprehensive strings instead of days in float format.
    * 
    * @param array $events. Array of the events to be classified and resumed.
    * @param $formattedCounters
    * 
    * @return array $counters
    */
   public function calculateStatsByUserAndEventType(array $events, int $year, $formattedCounters = false) {
      $counters = [];
      $totalMinutesOfHalfDaysPerUser = [];
      $wc = $this->wcRepo->findOneBy(['year' => $year]);
      foreach($events as $event) {
         $eventStartYear = $event->getStartDate()->format('Y');
         $workCalendars = [];
         $workCalendars[$eventStartYear] = $this->wcRepo->findOneBy(['year' => $eventStartYear]);          
         $workCalendars[$eventStartYear-1] = $this->wcRepo->findOneBy(['year' => $eventStartYear-1]);
         if ( !$event->getUsePreviousYearDays() ) {
            $workingDays = $this->calculateWorkingDays($event, $workCalendars[$eventStartYear]);
         } else {
            $workingDays = $this->calculateWorkingDays($event, $workCalendars[$eventStartYear-1]);
         }
         $userId = "{$event->getUser()->getUsername()}";
         $typeId = "{$event->getType()->getId()}";
         /** If workingDays is less than 0, it's a halfday. So we don't sum to the fulldays until the end 
          *  This is needed to fix the total and remaining days of typeId = 2
         */
         if( $workingDays < 1) {
            if (array_key_exists($userId, $totalMinutesOfHalfDaysPerUser)) {
               $totalMinutesOfHalfDaysPerUser[$userId] += $event->getEventTotalMinutes();
            } else {
               $totalMinutesOfHalfDaysPerUser[$userId] = $event->getEventTotalMinutes();
            }
            $workingDays = 0;
         }
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
      $counters = $this->addTotalMinutesOfHalfDaysPerUserToCounters($counters, $totalMinutesOfHalfDaysPerUser, $wc, 2, false);
      $this->calculateTotals($counters);
      if ($formattedCounters) {
         $counters = $this->formatStatsAsDaysHoursAndMinutes($counters, $wc);
      }
      return $counters;
   }

   private function addTotalMinutesOfHalfDaysPerUserToCounters(array &$counters, $totalMinutesOfHalfDaysPerUser, $workCalendar, $typeId = 2, $fixRemaining = true): array {
      foreach ($totalMinutesOfHalfDaysPerUser as $userId => $value) {
         $counters[$userId][$typeId] += ( $value / $workCalendar->getTotalWorkingMinutes() );
         if (array_key_exists('remaining', $counters[$userId])) {
            $counters[$userId]['remaining'] -= ( $value / $workCalendar->getTotalWorkingMinutes() );
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
      $totalMinutesOfHalfDaysPerUser = [];
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
         /** If workingDays is less than 0, it's a halfday. So we don't sum to the fulldays until the end 
          *  This is needed to fix the total and remaining days of typeId = 2
         */
         if( $workingDays < 1) {
            if (array_key_exists($username, $totalMinutesOfHalfDaysPerUser)) {
               $totalMinutesOfHalfDaysPerUser[$username] += $event->getEventTotalMinutes();
            } else {
               $totalMinutesOfHalfDaysPerUser[$username] = $event->getEventTotalMinutes();
            }
            $workingDays = 0;
         }
         if ( array_key_exists($username, $counters) ) {
            if ( array_key_exists($statusId, $counters[$username]) ) {
               $counters[$username][$statusId] = $counters[$username][$statusId] + $workingDays;
            } else {
               $counters[$username][$statusId] = $workingDays;
            }
         } else {
            $counters[$username][$statusId] = $workingDays;
         }
      }
      # Update remaining days
      foreach ($counters as $username => $counter) {
         $counters[$username]['remaining'] = $counters[$username]["total"];
         foreach ($counter as $key => $value) {
            if ( $key !== 'total' && $key !== 'remaining' ) {
               $counters[$username]['remaining'] -= $value;
            }
         }
      }
      $wc = $this->wcRepo->findOneBy(['year' => $year]);
      $counters = $this->addTotalMinutesOfHalfDaysPerUserToCounters($counters, $totalMinutesOfHalfDaysPerUser, $wc, 2, true);
      return $counters;
   }

   public function calculateStatsByUser(array $events, int $year) {
      $counters = [];
      $totalMinutesOfHalfDaysPerUser = [];
      foreach($events as $event) {
         $workCalendars = [];
         $workCalendars[$year] = $this->wcRepo->findOneBy(['year' => $year]);          
         $workCalendars[$year-1] = $this->wcRepo->findOneBy(['year' => $year-1]);
         if ( !$event->getUsePreviousYearDays() ) {
            $workingDays = $this->calculateWorkingDays($event, $workCalendars[$year]);
         } else {
            $workingDays = $this->calculateWorkingDays($event, $workCalendars[$year-1]);
         }
         $userId = "{$event->getUser()->getUsername()}";
         /** If workingDays is less than 0, it's a halfday. So we don't sum to the fulldays until the end 
          *  This is needed to fix the total and remaining days of typeId = 2
         */
         if( $workingDays < 1) {
            if (array_key_exists($userId, $totalMinutesOfHalfDaysPerUser)) {
               $totalMinutesOfHalfDaysPerUser[$userId] += $event->getEventTotalMinutes();
            } else {
               $totalMinutesOfHalfDaysPerUser[$userId] = $event->getEventTotalMinutes();
            }
            $workingDays = 0;
         }
         if ( array_key_exists($userId, $counters) ) {
               $counters[$userId] = $counters[$userId] + $workingDays;
         } else {
            $counters[$userId] = $workingDays;
         }
      }
      $wc = $this->wcRepo->findOneBy(['year' => $year]);
      $counters = $this->addTotalMinutesOfHalfDaysPerUserToCounters($counters, $totalMinutesOfHalfDaysPerUser, $wc, 2, true);
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

   private function calculateTotals(array &$counters) {
      $total = 0;
      foreach ($counters as $key => $typeValue) {
         foreach ($typeValue as $key2 => $value2) {
            $total += $value2;
         }
         $counters[$key]['total'] = $total;
         $total = 0;
      }
   }
}