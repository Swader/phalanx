<?php

namespace Services;

use Bitfalls\Phalcon\Abstracts\ServiceAbstract;
use Bitfalls\Objects\Result;

/**
 * Class UsersService
 * @package Services
 */
class UsersService extends ServiceAbstract
{

    /** @var string */
    protected $sTable_Main = '`users` `main`';

    /** @var string */
    protected $sTable_Contacts = 'contacts';

    /**
     * @param $aSearchParams
     * @return Result
     */
    public function search($aSearchParams)
    {

        $aBind = array();
        $sQuery = 'SELECT %s FROM ' . $this->sTable_Main . '
        WHERE 1 ';

        if (isset($aSearchParams['id'])) {
            $sQuery .= ' AND (`main`.`id` = :id) ';
            $aBind['id'] = $aSearchParams['id'];
        }

        if (isset($aSearchParams['q'])) {
            $q = $aSearchParams['q'];
            $sQuery .= ' AND (`main`.`username` LIKE :q OR `main`.`first_name` LIKE :q OR `main`.`last_name` LIKE :q) ';
            $aBind['q'] = '%' . $q . '%';
        }

        $oResult = $this->fetchPaginatedResult($sQuery, $aBind, $aSearchParams);

        return $oResult;

    }

}