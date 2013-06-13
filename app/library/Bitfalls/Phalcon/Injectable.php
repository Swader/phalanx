<?php

namespace Bitfalls\Phalcon;

use Phalcon\DI;

/**
 * Class Injectable
 * @package Bitfalls\Phalcon
 */
trait Injectable {

    /** @var DI */
    protected $_di;

    /**
     * @param DI $di
     */
    public function setDi(DI $di)
    {
        $this->_di = $di;
    }

    /**
     * @return DI
     */
    public function getDi()
    {
        return $this->_di;
    }
}