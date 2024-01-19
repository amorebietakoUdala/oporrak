<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

class DateAfter extends Constraint
{
    public $message = 'invalid.date';
    public $days = null;
}
