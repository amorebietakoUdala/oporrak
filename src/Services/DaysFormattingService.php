<?php

namespace App\Services;

use App\Entity\WorkCalendar;
use Symfony\Contracts\Translation\TranslatorInterface;

class DaysFormattingService
{
   public function __construct(
      private readonly TranslatorInterface $translator, 
   )
   {
   }

   /**
    * Calcula los días, horas y minutos a partir de un número de días decimales,
   * considerando la duración de la jornada laboral en horas y minutos.
   *
   * @param float $dias Número de días en formato decimal.
   * @param int $horasJornada Horas de una jornada laboral.
   * @param int $minutosJornada Minutos adicionales de una jornada laboral.
   * @return array Un arreglo con las claves 'days', 'hours', y 'minutes'.
   */
   private function calcularDiasHorasMinutosJornada($dias, $horasJornada = 8, $minutosJornada = 0) {
      $minutosPorDia = ($horasJornada * 60) + $minutosJornada;
      $minutosTotales = $dias * $minutosPorDia;
      $diasCompletos = floor($minutosTotales / $minutosPorDia);
      $minutosRestantes = $minutosTotales % $minutosPorDia;
      $horasRestantes = floor($minutosRestantes / 60);
      $minutosRestantes = $minutosRestantes % 60;

      return [
            'days' => intval($diasCompletos),
            'hours' => intval($horasRestantes),
            'minutes' => intval($minutosRestantes)
      ];
   }

   /**
      * Formatea el resultado a anterior a una cadena según el idioma
      */
   public function calcularDiasHorasMinutosJornadaString($dias, $horasJornada = 8, $minutosJornada = 0): string {
      $result = $this->calcularDiasHorasMinutosJornada($dias, $horasJornada, $minutosJornada);
      return $this->formatDaysHoursAndMinutes($result);
   }

   public function calcularDiasHorasMinutosJornadaWorkCalendarString($dias, WorkCalendar $wc): string {
      $result = $this->calcularDiasHorasMinutosJornada($dias, $wc->getWorkingHours(), $wc->getWorkingMinutes());
      return $this->formatDaysHoursAndMinutes($result);
   }

   public function formatDaysHoursAndMinutes(array $daysHoursMinutes): string {
      $formattedString = '';
      $formattedString = $this->translator->trans('message.days',[
         'days' => $daysHoursMinutes['days'],
      ]);
      $hoursLabel = $this->translator->trans('label.hours');
      $padedHours = mb_str_pad($daysHoursMinutes['hours'],2,0,STR_PAD_LEFT);
      $padedMinutes = mb_str_pad($daysHoursMinutes['minutes'],2,0,STR_PAD_LEFT);
      if ($daysHoursMinutes['hours'] !== null && $daysHoursMinutes['hours'] !== 0 || $daysHoursMinutes['minutes'] !== null && $daysHoursMinutes['minutes'] !== 0) {
         if ($daysHoursMinutes['days'] !== null && $daysHoursMinutes['days'] === 0 ) {
            $formattedString = '';
         } else {
            $and = $this->translator->trans('label.and');
            $formattedString = "$formattedString $and ";
         }
         $formattedString = trim("$formattedString $padedHours:$padedMinutes $hoursLabel");
      }
      return $formattedString;
   }
}