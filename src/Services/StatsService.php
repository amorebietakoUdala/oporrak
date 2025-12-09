<?php

namespace App\Services;

use App\Entity\Event;
use App\Entity\EventType;
use App\Entity\Status;
use App\Entity\User;
use App\Entity\WorkCalendar;
use App\Repository\AdditionalVacationDaysRepository;
use App\Repository\AntiquityDaysRepository;
use App\Repository\HolidayRepository;
use App\Repository\UserRepository;
use App\Repository\WorkCalendarRepository;
use DateTime;

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
   public function calculateStatsByUserAndEventType(array $events, int $year, $byHours, $formattedCounters = false) {
      $counters = [];
      $totalMinutesOfHalfDaysPerUser = [];
      // Set unionHoursPerMonth as a type that is calculated in hours and not in days
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
         // Others in days
         if ( !array_key_exists($typeId, $byHours) ) {
            $byHours[$typeId] = false;
         }
         /** If workingDays is less than 0, it's a halfday. So we don't sum to the fulldays until the end 
          *  This is needed to fix the total and remaining days of typeId = 2
         */
         if( $workingDays < 1) {
            if (array_key_exists($userId, $totalMinutesOfHalfDaysPerUser)) {
               if (array_key_exists($event->getType()->getId(), $totalMinutesOfHalfDaysPerUser[$userId])) {
                  $totalMinutesOfHalfDaysPerUser[$userId][$event->getType()->getId()] += $event->getEventTotalMinutes();
               } else {
                  $totalMinutesOfHalfDaysPerUser[$userId][$event->getType()->getId()] = $event->getEventTotalMinutes();
               }
            } else {
               $totalMinutesOfHalfDaysPerUser[$userId][$event->getType()->getId()] = $event->getEventTotalMinutes();
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
      //dump($counters, $totalMinutesOfHalfDaysPerUser);
      $counters = $this->addTotalMinutesOfHalfDaysPerUserToCounters($counters, $totalMinutesOfHalfDaysPerUser, $wc, $byHours);
      //dump($counters);
      $this->calculateTotals($counters);
      if ($formattedCounters) {
         $counters = $this->formatStatsAsDaysHoursAndMinutes($counters, $wc);
      }
      //dd($counters);
      return $counters;
   }

   private function addTotalMinutesOfHalfDaysPerUserToCounters(array &$counters, $totalMinutesOfHalfDaysPerUser, $workCalendar, array $byHours, bool $excludeUnionHours = true): array {
      foreach ($totalMinutesOfHalfDaysPerUser as $userId => $value) {
         foreach ($value as $typeId => $value2) {
            // dump($counters, $totalMinutesOfHalfDaysPerUser, $userId, $typeId);
            // If byHours key for typeId is true, we calculate the result in hours and not in days dividing by 60
            if ( array_key_exists($typeId, $byHours) && $byHours[$typeId] == true) {
               if ( $typeId !== EventType::UNION_HOURS && $excludeUnionHours || !$excludeUnionHours ) {
                  $counters[$userId][$typeId] += $value2 / 60;
                  if (array_key_exists('remaining', $counters[$userId])) {
                     $counters[$userId]['remaining'] -= $value2 / 60;
                  }
               }
            // Otherwise, we calculate the result in days and not in hours dividing by the total working minutes of the day
            } else {
               // dump($workCalendar->getTotalWorkingMinutes());
               $counters[$userId][$typeId] += ( $value2 / $workCalendar->getTotalWorkingMinutes() );
               if (array_key_exists('remaining', $counters[$userId])) {
                  $counters[$userId]['remaining'] -= ( $value2 / $workCalendar->getTotalWorkingMinutes() );
               }
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
            $antiquityDays = $this->adRepo->findAntiquityDaysForYearsWorked($user->getYearsWorked(), $year);
            if ( null !== $antiquityDays ) {
               $counters[$username]['total'] = $counters[$username]['total'] + $antiquityDays->getVacationDays();
            }
            $additionalVacationDays = $this->avdRepo->findAdditionalVacationDaysForYearsWorked($user->getYearsWorked(), $year);
            if ( null !== $additionalVacationDays ) {
               $counters[$username]['total'] = $counters[$username]['total'] + $additionalVacationDays->getVacationDays();
            }
         }
      }
      return $counters;

   }

   /**
   * Calculates the stats of the events by user and status.
   * 
   * @param array $events The array of events to calculate the stats.
   * @param int $year The year to calculate the stats.
   * @param array $usernames The array of usernames to calculate the stats.
   */
   public function calculateStatsByUserAndStatus(array $events, int $year, array $usernames) {
      // Add total days for the year to counters
      $counters = $this->calculateTotalsForUsernamesAndYear($usernames,$year);
      $totalMinutesOfHalfDaysPerUser = [];
      // We set which types are calculated in hours and not in days. In this case we need all types in hours, so we set UNION_HOURS to false.
      $byHours = [
         EventType::UNION_HOURS => true,
      ];
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
         if ( !array_key_exists($statusId, $byHours) ) {
            $byHours[$statusId] = false;
         }
         /** If workingDays is less than 0, it's a halfday. So we don't sum to the fulldays until the end 
          *  This is needed to fix the total and remaining days of typeId = 2 and typeId = 6
         */
         if( $workingDays < 1 ) {
            // We don't count Union Hours in this stats, only the other types.
            if ( $event->getType()->getId() !== EventType::UNION_HOURS ) {
               if (array_key_exists($username, $totalMinutesOfHalfDaysPerUser)) {
                     if (array_key_exists($statusId, $totalMinutesOfHalfDaysPerUser[$username])) {
                        $totalMinutesOfHalfDaysPerUser[$username][$statusId] += $event->getEventTotalMinutes();
                     } else {
                        $totalMinutesOfHalfDaysPerUser[$username][$statusId] = $event->getEventTotalMinutes();
                     }
               } else {
                  $totalMinutesOfHalfDaysPerUser[$username][$statusId] = $event->getEventTotalMinutes();
               }
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
      $wc = $this->wcRepo->findOneBy(['year' => $year]);
      $counters = $this->addTotalMinutesOfHalfDaysPerUserToCounters($counters, $totalMinutesOfHalfDaysPerUser, $wc, $byHours);
      # Update remaining days. 
      foreach ($counters as $username => $counter) {
         $counters[$username]['remaining'] = $counters[$username]["total"];
         foreach ($counter as $key => $value) {
            if ( $key !== 'total' && $key !== 'remaining' ) {
               $counters[$username]['remaining'] -= $value;
            }
         }
      }
      return $counters;
   }

   /**
    * Calculates the working days of an event. Checks if the event is has holidays and weekends in between.
    * @param Event $event The event to calculate the working days.
    * @param WorkCalendar $workCalendar The work calendar to be used to calculate the working days.
    *
    * @return float|int The working days of the event.
    */
   public function calculateWorkingDays(Event $event, WorkCalendar $workCalendar): int|float {
      $user = $event->getUser();
      $includeHolidays = false;
      $includeWeekends = false;
      if ($user !== null) {
         $includeWeekends = $user->isWorksOnWeekends();
         if ($includeWeekends) {
            $includeHolidays = true;
         }
      }
      if (!$event->getHalfDay()) {
         return $this->adjustWorkingDays($event, $includeHolidays, $includeWeekends);
      } else {
         return $event->getEventTotalMinutes() / $workCalendar->getTotalWorkingMinutes();
      }
   }

   /**
    * It's adjusts working days by removing weekends and holidays if specified.
    * @param Event $event The event to calculate the working days.
    * @param bool $includeHolidays If true, holidays are included in the working days calculation.
    * @param bool $includeWeekends If true, weekends are included in the working days calculation.
    */
   private function adjustWorkingDays(Event $event, bool $includeHolidays = false, bool $includeWeekends = false): int {
      $workingDays = $event->getDays();
      // Remove weekends
      if (!$includeWeekends) {
         /** @var DateTime $startDay */
         $starDay = $event->getStartDate();
         /** @var DateTime $endDay */
         $endDay = $event->getEndDate();
         $endDayPlus1 = clone $endDay;
         $endDayPlus1->modify('+1 day'); // Include last day
     
         $interval = new \DatePeriod($starDay, new \DateInterval('P1D'), $endDayPlus1);
         $workingDays = 0;
     
         foreach ($interval as $date) {
             $weekday = $date->format('N'); // 1 (Monday) a 7 (Sunday)
             if ($weekday < 6) {
                 $workingDays++;
             }
         }
      }
      // Remove weekends
      if (!$includeHolidays) {
         $holidays = $this->holidayRepo->findHolidaysBetween($event->getStartDate(), $event->getEndDate());
         $holidaysBetween = $this->countHolidays($holidays, $includeWeekends);
         $workingDays -= $holidaysBetween;
      }

      return $workingDays;
   }

   /**
    * Calculates the number of holidays including weekends or without weekends.
    * 
    * @param array $holidays The array of holidays to be checked.
    * @param bool $includeWeekends If true, weekends are included in the holidays calculation.
    * 
    * @return int The number of holidays.
    */
   private function countHolidays($holidays, bool $includeWeekends = false): int {
      $count = 0;
      foreach ($holidays as $holiday) {
         if ($includeWeekends) {
            $count++;
         } else {
            // Check if the holiday is a weekend (Saturday or Sunday)
            if ($this->getWeekday($holiday->getDate()->format('Y-m-d')) !== 0 && $this->getWeekday($holiday->getDate()->format('Y-m-d')) !== 6 ) {
               $count++;
            }
         }
      }
      return $count;
   }

   /**
    * Returns the weekday of a date.
    * @param string $date The date to be checked.
    * @return int The weekday of the date. 0 (Sunday) to 6 (Saturday).
    */
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

   /**
    * Returns the stats array formatted as days, hours and minutes.
    * @param array $stats The stats array to be formatted. Stats must be in days (float value).
    * @param WorkCalendar $workCalendar The work calendar to be used to calculate the stats.
    *
    * @return array
    */
   public function formatStatsAsDaysHoursAndMinutes(array $stats, WorkCalendar $workCalendar): array {
      $newStats  = [];
      foreach ($stats as $key => $value) {
         //dump($key, $value);
         if ( is_array($value) ) {
            foreach ( $value as $key2 => $item ) {
               $newStats[$key][$key2] = $this->daysFormattingService->calcularDiasHorasMinutosJornadaWorkCalendarString($item,$workCalendar);
            }
         }
      }
      return $newStats;
   }
   /** 
    * Calculates the total of each user in the counters array. And adds it as a new key 'total' to each user in the received array.
    * @param array $counters The array of counters to calculate the totals.
    *
    * @return void Changes the received array by reference.
    */
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