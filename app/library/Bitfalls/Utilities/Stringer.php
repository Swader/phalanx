<?php

namespace Bitfalls\Utilities;

/**
 * Class Stringer
 * @package Bitfalls\Utilities
 */
class Stringer
{
    /**
     * @param $sString
     * @return string
     */
    public static function cleanString($sString)
    {
        $sString = preg_replace("/[^A-Za-z0-9_]/", " ", $sString);
        $sString = preg_replace("/\s+/", " ", $sString);
        $sString = str_replace(" ", "_", $sString);
        return strtolower($sString);
    }

    /**
     * Converts any_string into CamelCase
     * @param $s
     * @param string $sDelimiter
     * @return string
     */
    public static function toCamelCase($s, $sDelimiter = '_')
    {
        return implode('', array_map(function ($el) {
            return ucfirst($el);
        }, explode($sDelimiter, $s)));
    }
}