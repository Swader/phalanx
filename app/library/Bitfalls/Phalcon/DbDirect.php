<?php

namespace Bitfalls\Phalcon;

use Phalcon\Db;

/**
 * Class DbDirect
 * @package Bitfalls\Phalcon
 */
trait DbDirect {
    use Injectable;

    /** @var  Db\Adapter\Pdo */
    protected $db;

    /**
     * @return mixed| Db\Adapter\Pdo
     */
    public function getDb() {
        if (!isset($this->db)) {
            $this->db = $this->getDi()->get('db');
        }
        return $this->db;
    }

    /**
     * @param  Db\Adapter\Pdo $db
     * @return $this
     */
    public function setDb(Db\Adapter\Pdo $db) {
        $this->db = $db;
        return $this;
    }
}