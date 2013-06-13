<?php

namespace Services;

use Bitfalls\Phalcon\Abstracts\ServiceAbstract;
use Bitfalls\Objects\Result;

/**
 * Class TagService
 * @package Services
 */
class TagService extends ServiceAbstract
{

    /** @var string */
    protected $sTable_Main = '`tags` `main`';

    /** @var string */
    protected $sTable_Entities = '`entities`';

    /** @var string */
    protected $sTable_TagTypes = '`tag_types`';

    /** @var string */
    protected $sTable_Bind = '`tag_bind`';

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
            $sQuery .= ' AND (
            `main`.`tag` LIKE :q
            OR `main`.`description` LIKE :q
            )';
            $aBind['q'] = '%' . $q . '%';
        }

        $oResult = $this->fetchPaginatedResult($sQuery, $aBind, $aSearchParams, '`main`.*');

        return $oResult;

    }

}