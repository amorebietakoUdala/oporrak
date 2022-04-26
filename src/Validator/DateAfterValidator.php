<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class DateAfterValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        /** @var App\Validator\DateAfter $constraint */

        if (null === $value || '' === $value) {
            return;
        }
        $days = $constraint->days;
        $interval = date_interval_create_from_date_string("-$days days");
        $today = new \DateTime();
        $minDate = date_add($today, $interval);
        if ($value <= $minDate) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value->format('Y-m-d'))
                ->setParameter('{{ minDate }}', $minDate->format('Y-m-d'))
                ->addViolation();
        }
        return;
    }
}
