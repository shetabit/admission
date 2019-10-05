<?php

namespace Shetabit\Admission\Facade;

use Illuminate\Support\Facades\Facade;

/**
 * Class Admission
 *
 * @package Shetabit\Admission\Facade
 */
class Admission extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    public static function getFacadeAccessor()
    {
        return 'shetabit-admission';
    }
}
