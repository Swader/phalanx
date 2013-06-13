<?php

namespace Bitfalls\Traits;

/**
 * Class Dates
 * @package Bitfalls\Utilities
 */
trait Dates
{

    /** @var array  */
    protected $aDateFormats = array(
        'mysql' => 'Y-m-d',
        'mysql_full' => 'Y-m-d H:i:s',
        'human' => 'M d, Y'
    );

    /**
     * @param $sInput
     * @param bool $bAcceptableWithoutTime
     * @return bool
     */
    public function isMysqlDate($sInput, $bAcceptableWithoutTime = true)
    {
        $sPattern = '/\d{4}-[01]\d-[0-3]\d';
        $sPattern .= ($bAcceptableWithoutTime) ? '' : ' [0-2]\d:[0-5]\d:[0-5]\d/';
        return (bool)preg_match($sPattern, $sInput);
    }

    /**
     * Converts a value into a human readable date format
     *
     * @param null $mInput
     *
     * @return string
     */
    public function dateToReadable($mInput = null)
    {
        return date($this->aDateFormats['human'], $this->inputToTime($mInput));
    }

    /**
     * @param null $mInput
     * @param null $sFormat
     * @return bool|string
     */
    public function dateTo($mInput = null, $sFormat = null) {
        if (!$sFormat) {
            return $this->dateToReadable($mInput);
        } else {
            return date($sFormat, $this->inputToTime($mInput));
        }
    }

    /**
     * Converts a value into a MySQL DATE format
     *
     * @param mixed $mInput
     *
     * @return string
     */
    public function dateToMysql($mInput = null)
    {
        return date($this->aDateFormats['mysql'], $this->inputToTime($mInput));
    }

    /**
     * Converts a value into MySQL DATETIME format
     *
     * @param null $mInput
     *
     * @return string
     */
    public function dateToMysqlFull($mInput = null)
    {
        return date($this->aDateFormats['mysql_full'], $this->inputToTime($mInput));
    }

    /**
     * Returns numeric timestamp since Unix Epoch regardless of input
     * parameter type
     *
     * @param mixed $mInput
     *
     * @return int
     * @throws \InvalidArgumentException
     */
    public function inputToTime($mInput = null)
    {
        if (is_string($mInput)) {
            return strtotime($mInput);
        } elseif (ctype_digit($mInput)) {
            return $mInput;
        } elseif ($mInput === null) {
            return time();
        }
        throw new \InvalidArgumentException('Invalid input: ' . $mInput);
    }

    /**
     * Checks if input is a MySQL null date
     *
     * @param mixed $mInput
     *
     * @return bool
     */
    public function dateIsNull($mInput)
    {
        return (
            $mInput == '0000-00-00'
                || $mInput == '0000-00-00 00:00:00'
                || empty($mInput)
                || $mInput === null
        );
    }
}