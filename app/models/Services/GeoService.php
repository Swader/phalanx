<?php

namespace Services;

use Bitfalls\Phalcon\Abstracts\ServiceAbstract;
use Bitfalls\Objects\Result;
use Phalcon\Db;

/**
 * Class GeoService
 * @package Services
 */
class GeoService extends ServiceAbstract
{

    /** @var string */
    protected $sTable_Countries = '`countries` `main`';

    /** @var string */
    protected $sTable_Cities = '`cities` `main`';

    /** @var string */
    protected $sTable_States = '`states` `main`';

    /**
     * @return array
     */
    public function getGeonameIdsCities()
    {
        $aR = array();
        foreach ($this->getDb()->fetchAll('SELECT geonameid FROM ' . $this->sTable_Cities) as $a) {
            $aR[] = $a['geonameid'];
        }
        return $aR;
    }

    /**
     * @param $cid
     * @return array
     */
    public function getCityPairsByCid($cid)
    {
        $aR = array();
        foreach (
            $this->getDb()->fetchAll(
                'SELECT `main`.`id`, `main`.`name`
                FROM ' . $this->sTable_Cities . '
                WHERE `main`.`country_id` = :cid
                ORDER BY `main`.`name` ASC ',
                null,
                array('cid' => (int)$cid)) as $a) {

            $aR[$a['id']] = $a['name'];

        }
        return $aR;
    }

    /**
     * @param $aSearchParams
     * @return Result
     */
    public function search($aSearchParams)
    {

        $aBind = array();
        $sQuery = 'SELECT %s FROM ' . $this->sTable_Countries . '
        WHERE 1 ';

        if (isset($aSearchParams['id'])) {
            $sQuery .= ' AND (`main`.`id` = :id) ';
            $aBind['id'] = $aSearchParams['id'];
        }

        if (isset($aSearchParams['q'])) {
            $q = $aSearchParams['q'];
            $sQuery .= ' AND (
            `main`.`country_name` LIKE :q
            )';
            $aBind['q'] = '%' . $q . '%';
        }

        $oResult = $this->fetchPaginatedResult($sQuery, $aBind, $aSearchParams, '`main`.*');

        return $oResult;

    }

    /**
     * @param $aSearchParams
     * @return Result
     */
    public function searchCities($aSearchParams)
    {

        $aBind = array();
        $sQuery = 'SELECT %s FROM ' . $this->sTable_Cities . '
        WHERE 1 ';

        if (isset($aSearchParams['id'])) {
            $sQuery .= ' AND (`main`.`id` = :id) ';
            $aBind['id'] = $aSearchParams['id'];
        }

        if (isset($aSearchParams['q'])) {
            $q = $aSearchParams['q'];
            $sQuery .= ' AND (
            `main`.`name` LIKE :q
            )';
            $aBind['q'] = '%' . $q . '%';
        }

        $oResult = $this->fetchPaginatedResult($sQuery, $aBind, $aSearchParams, '`main`.*');

        return $oResult;

    }

    /**
     * @param $aSearchParams
     * @return Result
     */
    public function searchStates($aSearchParams)
    {

        $aBind = array();
        $sQuery = 'SELECT %s FROM ' . $this->sTable_States . '
        WHERE 1 ';

        if (isset($aSearchParams['id'])) {
            $sQuery .= ' AND (`main`.`id` = :id) ';
            $aBind['id'] = $aSearchParams['id'];
        }

        if (isset($aSearchParams['q'])) {
            $q = $aSearchParams['q'];
            $sQuery .= ' AND (
            `main`.`name` LIKE :q
            )';
            $aBind['q'] = '%' . $q . '%';
        }

        $oResult = $this->fetchPaginatedResult($sQuery, $aBind, $aSearchParams, '`main`.*');

        return $oResult;

    }

}