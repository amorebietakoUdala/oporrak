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
   * 
   * @return array Un array con las claves 'days', 'hours', y 'minutes'.
   */
   private function calcularDiasHorasMinutosJornada($dias, $horasJornada = 8, $minutosJornada = 0): array {
      $minutosPorDia = ($horasJornada * 60) + $minutosJornada;
      // We round to 5 decimal places to avoid floating point errors.
      $minutosTotales = round($dias * $minutosPorDia, 5, PHP_ROUND_HALF_UP); ;
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
   * 
   * @param float $dias Número de días en formato decimal.
   * @param int $horasJornada Horas de una jornada laboral.
   * @param int $minutosJornada Minutos adicionales de una jornada laboral.
   * 
   * @return string La cadena formateada.
   */
   public function calcularDiasHorasMinutosJornadaString($dias, $horasJornada = 8, $minutosJornada = 0): string {
      $result = $this->calcularDiasHorasMinutosJornada($dias, $horasJornada, $minutosJornada);
      return $this->formatDaysHoursAndMinutes($result);
   }

   /**
   * Formatea los días, horas y minutos a una cadena según el calendario laboral pasado como parámetro.
   * 
   * @param float $dias Número de días en formato decimal.
   * @param WorkCalendar $wc Calendario laboral.
   * 
   * @return string La cadena formateada.
   */
  public function calcularDiasHorasMinutosJornadaWorkCalendarString($dias, WorkCalendar $wc): string {
      $result = $this->calcularDiasHorasMinutosJornada($dias, $wc->getWorkingHours(), $wc->getWorkingMinutes());
      return $this->formatDaysHoursAndMinutes($result);
   }

   /**
    * Formatea un array con los días, horas y minutos a una cadena cadena de texto según el idioma.
    * 
    * @param array $daysHoursMinutes Un array con las claves 'days', 'hours', y 'minutes'.
    * 
    * @return string La cadena formateada.
    */
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

   /**
    * Formats an amount of hours to a string with hours and minutes.
    *
    * @param float $hours The amount of hours.
    *
    * @return string The formatted string.
    */
   public function formatHours(float $hours): string {
      $hoursAndMinutes = $this->splitHoursAndMinutes($hours);
      $daysHoursMinutes = [
         'days' => 0,
         'hours' => $hoursAndMinutes['hours'],
         'minutes' => $hoursAndMinutes['minutes'],
      ];
      return $this->formatDaysHoursAndMinutes($daysHoursMinutes);
   }

   /**
    * Splits a total amount of hours into hours and minutes.
    *
    * @param float $totalHours The total amount of hours.
    * 
    * @return array An array with the keys 'hours' and 'minutes'.
    */
   public function splitHoursAndMinutes(float $totalHours): array {
      $totalMinutes = floor($totalHours * 60);
      $hours = floor($totalMinutes / 60);
      $minutes = $totalMinutes % 60;
      return [
         'hours' => $hours,
         'minutes' => $minutes,
      ];
   }
}