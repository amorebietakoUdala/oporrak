<?php

namespace App\Services;

use App\Entity\Event;
use App\Entity\Holiday;
use App\Entity\WorkCalendar;
use Doctrine\ORM\EntityManagerInterface;

class StatsService
{
   private $em;

   public function __construct(EntityManagerInterface $em) {
      $this->em = $em;
   }

   public function calculateTotalWorkingDays(array $events, $workCalendar)
   {
       $totalWorkingDays = 0;
       foreach ($events as $event) {
           $workingDays = $this->calculateWorkingDays($event, $workCalendar);
           $totalWorkingDays += $workingDays;
       }

       return $totalWorkingDays;
   }

   public function calculateStatsByUserAndEventType(array $events) {
      $counters = [];

      foreach($events as $event) {
         $year = $event->getStartDate()->format('Y');
         $workCalendars = [];
         $workCalendars[$year] = $this->em->getRepository(WorkCalendar::class)->findOneBy(['year' => $year]);          
         $workCalendars[$year-1] = $this->em->getRepository(WorkCalendar::class)->findOneBy(['year' => $year-1]);
         if ( !$event->getUsePreviousYearDays() ) {
            $workingDays = $this->calculateWorkingDays($event, $workCalendars[$year]);
         } else {
            $workingDays = $this->calculateWorkingDays($event, $workCalendars[$year-1]);
         }

         $userId = "{$event->getUser()->getUsername()}";
         $typeId = "{$event->getType()->getId()}";

         if ( array_key_exists($userId, $counters) ) {
            if ( array_key_exists($typeId, $counters[$userId]) ) {
               $counters[$userId][$typeId] = $counters[$userId][$typeId] + $workingDays;
            } else {
               $counters[$userId][$typeId] = $workingDays;
            }
         } else {
            $counters[$userId][$typeId] = $workingDays;
         }
      }

      return $counters;
   }
   
   public function calculateWorkingDays(Event $event, WorkCalendar $workCalendar)
   {
       if (!$event->getHalfDay()) {
           $holidaysBetween = count($this->em->getRepository(Holiday::class)->findHolidaysBetween($event->getStartDate(), $event->getEndDate()));
           $workingDays = $event->getDays();
           // Subtract two weekend days for every week in between
           $weeks = floor($workingDays / 7);
           //        dump($workingDays, $holidaysBetween, $weeks);
           $workingDays -= $weeks * 2;
           // Handle special cases
           $startDay = intVal(date('w', strtotime(($event->getStartDate())->format('Y-m-d'))));
           $endDay = intVal(date('w', strtotime(($event->getEndDate())->format('Y-m-d'))));
           // Remove weekend not previously removed.   
           if ($startDay - $endDay > 1) {
               $workingDays -= 2;
           }
           // Remove start day if span starts on Sunday but ends before Saturday
           if ($startDay == 7 && $endDay != 6) {
               $workingDays--;
           }
           // Remove end day if span ends on Saturday but starts after Sunday
           if ($endDay == 7 && $startDay != 1) {
               $workingDays--;
           }
           $workingDays -= $holidaysBetween;
           return $workingDays;
       } else {
           return $workingDays = $event->getHours() / $workCalendar->getWorkingHours();
       }
   }

}