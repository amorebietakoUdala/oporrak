<?php

namespace App\Twig\Runtime;

use Twig\Extension\RuntimeExtensionInterface;

class StrPadRuntime implements RuntimeExtensionInterface
{
    public function __construct()
    {
        // Inject dependencies if needed
    }

    /**
     * Pads a string to a certain length with another string.
     *
     *  {{ "1"|str_pad(2, '0', STR_PAD_LEFT) }}
     *  {# returns 01 #}
     *
     * @param string|null $value     A string
     * @param int|null    $length    The length of the resulting string
     * @param string|null $padString The string to use for padding
     * @param int|null    $padType   One of STR_PAD_LEFT, STR_PAD_RIGHT, or STR_PAD_BOTH
     *
     * @return string The padded string
     */
    public function strPad($value, $length, $padString = ' ', $padType = STR_PAD_LEFT): string
    {
        return str_pad($value, $length, $padString, $padType);
    }
}
