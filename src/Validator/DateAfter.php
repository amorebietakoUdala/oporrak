<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class DateAfter extends Constraint
{
    /*
     * Any public properties become valid options for the annotation.
     * Then, use these in your validator class.
     */
    public $message = 'The date "{{ value }}" is not valid. Must be greater than {{ minDate }}. Can\'t add past events before that date.';
    public $days = null;

    public function __construct($params)
    {
        $this->days = $params['days'];
    }
}
