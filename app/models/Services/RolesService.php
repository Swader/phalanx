<?php

namespace Services;

use Bitfalls\Phalcon\Abstracts\ServiceAbstract;
use Bitfalls\Objects\Result;

/**
 * Class RolesService
 * @package Services
 */
class RolesService extends ServiceAbstract
{

    /** @var string */
    protected $sTable_Main = '`user_roles` `main`';

    /** @var string */
    protected $sTable_Users = '`users`';

    /** @var string */
    protected $sTable_Rel = '`users_roles`';

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
            $sQuery .= ' AND (`main`.`slug` LIKE :q OR `main`.`name` LIKE :q) ';
            $aBind['q'] = '%' . $q . '%';
        }

        $oResult = $this->fetchPaginatedResult($sQuery, $aBind, $aSearchParams);

        return $oResult;

    }

}