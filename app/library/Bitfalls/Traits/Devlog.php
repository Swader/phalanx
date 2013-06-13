<?php

namespace Bitfalls\Traits;

use Bitfalls\Phalcon\Injectable;
use Phalcon\DI;
use Phalcon\Logger\Adapter\File;
use Phalcon\Logger;

/**
 * Class Devlog
 * @package Bitfalls\Traits
 */
trait Devlog
{

    /** @var int */
    protected $iPreviousMemUsage;

    /** @var null */
    protected $oLog = null;

    /**
     * @param $s
     * @return null|File
     */
    public function __get($s)
    {
        if ($s == 'log') {
            if ($this->oLog === null) {
                $c = DI::getDefault()->get('config');
                $this->oLog = new File($c->application->logDir . 'dev.log');
            }
            return $this->oLog;
        } else {
            return parent::__get($s);
        }
    }

    /**
     * @return null|File
     */
    public function getLog()
    {
        return $this->log;
    }

    /**
     * @param $sFunctionName
     * @param null $aArguments
     * @return null|File
     * @throws \Exception
     */
    public function __call($sFunctionName, $aArguments = null)
    {
        if ($sFunctionName == 'log') {
            if (!isset($aArguments[0]) || empty($aArguments[0])) {
                throw new \Exception('Nothing to log, message empty!');
            }
            if (!isset($aArguments[1])) {
                $aArguments[1] = Logger::DEBUG;
            }
            $aResult = $this->getCurrentMemUsage();
            $aArguments[0] = $aArguments[0] . ' === MEM: ' . $aResult['now'] . '; Jump: ' . $aResult['jump'];
            $this->log->log($aArguments[0], $aArguments[1]);
            return $this->log;
        } else {
            return parent::__call($sFunctionName, $aArguments);
        }
    }

    /**
     * @return array
     */
    public function getCurrentMemUsage()
    {
        if (!$this->iPreviousMemUsage) {
            $this->iPreviousMemUsage = memory_get_usage();
        }
        $iMem = memory_get_usage();
        //echo $iMem.'-> mem <br />';
        $iJump = $iMem - $this->iPreviousMemUsage;
        //echo $iJump.'-> jump <br />';
        $this->iPreviousMemUsage = $iMem;

        return array(
            'now' => $this->convertBytes($iMem),
            'jump' => $this->convertBytes($iJump)
        );
    }

    /**
     * @param $iBytes
     * @return string
     */
    public function convertBytes($iBytes)
    {
        if ($iBytes == '0') return '0 b';
        $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
        return round($iBytes / pow(1024, ($i = floor(log($iBytes, 1024)))), 2) . ' ' . $unit[$i];
    }

    /**
     * This method helps debugging by providing a simple shorthand solution
     * for outputting complex variables (like nested array) in a human readable
     * manner. This is helpful when a variable of unknown type needs to be
     * appended to an exception message, and similar scenarios
     *
     * @param mixed $mVar The variable to dump
     * @param bool $bPre Whether or not to wrap the output in <pre> tags
     * @param bool $bDie Whether to die the output or return it
     *
     * @return string
     */
    public function vd($mVar, $bPre = false, $bDie = false)
    {
        ob_start();
        var_dump($mVar);
        $mVar = ob_get_clean();

        if ($bPre) {
            $mVar = '<pre>' . $mVar . '</pre>';
        }

        if ($bDie) {
            die($mVar);
        } else {
            return $mVar;
        }
    }

    /**
     * @see self::vd()
     * @param mixed $mVar
     * @param bool $bPre
     */
    public function vdd($mVar, $bPre = false)
    {
        self::vd($mVar, $bPre, 1);
    }

    /**
     * @see self::vd()
     * @param mixed $mVar
     * @param bool $bDie
     *
     * @return string
     */
    public function vdp($mVar, $bDie = false)
    {
        return self::vd($mVar, 1, $bDie);
    }

    /**
     * @see self::vd()
     * @param mixed $mVar
     */
    public function vddp($mVar)
    {
        self::vd($mVar, 1, 1);
    }

}