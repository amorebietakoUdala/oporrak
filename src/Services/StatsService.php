<?php

namespace App\Services;

use App\Entity\AntiquityDays;
use App\Entity\Event;
use App\Entity\Status;
use App\Entity\User;
use App\Entity\WorkCalendar;
use App\Repository\AntiquityDaysRepository;
use App\Repository\HolidayRepository;
use App\Repository\WorkCalendarRepository;

class StatsService
{
   private WorkCalendarRepository $wcRepo;
   private HolidayRepository $holidayRepo;
   private AntiquityDaysRepository $adRepo;

   public function __construct(WorkCalendarRepository $wcRepo, HolidayRepository $holidayRepo, AntiquityDaysRepository $adRepo) {
      $this->wcRepo = $wcRepo;
      $this->holidayRepo = $holidayRepo;
      $this->adRepo = $adRepo;
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
               $thisYearEndDate = new \DateTime("${year}-12-31");
               $thisYearStartDate = new \DateTime("${year}-01-01");
               $nextYearStartDate = new \DateTime("${nextYear}-01-01");
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

   private function calculateTotalWorkingDaysOnYear(Event $event, $year) {
      $startDate = $event->getStartDate();
      $year = $event->getStartDate()->format('Y');
      $nextYear = intval($event->getStartDate()->format('Y')) + 1;
      $nextYearStartDate = new \DateTime("${nextYear}-01-01");

      $endDate = $event->getEndDate();

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

   public function calculateStatsByUserAndStatus(array $events, int $year) {
      $counters = [];
      foreach($events as $event) {
         /** @var Event $event */
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
         $antiquityDays = $this->adRepo->findAntiquityDaysForYearsWorked($event->getUser()->getYearsWorked());
         if ( array_key_exists($username, $counters) ) {
            if ( array_key_exists($statusId, $counters[$username]) ) {
               $counters[$username][$statusId] = $counters[$username][$statusId] + $workingDays;
            } else {
               $counters[$username][$statusId] = $workingDays;
            }
         } else {
            $user = $event->getUser();
            if ( null !== $antiquityDays ) {
               $counters[$username]['total'] = $user->calculateCurrentYearBaseDays($workCalendars[$year]) + $antiquityDays->getVacationDays();
            } else {
               $counters[$username]['total'] = $user->calculateCurrentYearBaseDays($workCalendars[$year]);
            }
            $counters[$username][$statusId] = $workingDays;
         }
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
           return $workingDays = $event->getHours() / $workCalendar->getWorkingHours();
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
      return intval(date('w', strtotime($date)));
   }
}