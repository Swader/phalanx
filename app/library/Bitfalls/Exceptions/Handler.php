<?php

namespace Bitfalls\Exceptions;

/**
 * Class Handler
 * @package Bitfalls\Exceptions
 */
class Handler {
    public static function handle(\Exception $e) {
        echo "Exception handler says: ";
        die($e->getMessage().'<hr /><pre>'.$e->getTraceAsString().'</pre>');
    }
}