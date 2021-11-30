<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class DateAfter extends Constraint
{
    public $message = 'invalid.date';
    public $days = null;

    public function __construct($params)
    {
        $this->days = $params['days'];
    }
}
