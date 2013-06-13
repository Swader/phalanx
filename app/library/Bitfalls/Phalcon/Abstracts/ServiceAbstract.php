<?php

namespace Bitfalls\Phalcon\Abstracts;

use Bitfalls\Objects\Result;
use Bitfalls\Phalcon\DbDirect;
use Phalcon\Db;

/**
 * Class ServiceAbstract
 * @package Bitfalls\Phalcon\Abstracts
 */
abstract class ServiceAbstract
{
    use DbDirect;

    /**
     * @param $sQuery
     * @param $aBind
     * @param $aSearchParams
     * @param string $sFields
     * @return Result
     */
    protected function fetchPaginatedResult($sQuery, $aBind, $aSearchParams, $sFields = '*')
    {
        $iRowCount = null;

        if (!strpos($sQuery, 'SQL_CALC_FOUND_ROWS')) {
            $iRowCount = $this->getDb()->fetchOne(sprintf($sQuery, 'count(*) as num'), Db::FETCH_ASSOC, $aBind);
            $iRowCount = (isset($iRowCount['num'])) ? $iRowCount['num'] : 0;
        }

        if ($aSearchParams['sort']) {
            $sQuery .= ' ORDER BY ' . $aSearchParams['sort'] . ' ' . $aSearchParams['order'];
        }

        if ($aSearchParams['limit']) {
            $iPage = ((int)$aSearchParams['page'] < 1) ? 1 : $aSearchParams['page'];
            $sStartRow = ($iPage == 1) ? 0 : ($iPage - 1) * $aSearchParams['limit'];
            $sQuery .= ' LIMIT ' . $sStartRow . ', ' . $aSearchParams['limit'];
        }

        $sQuery = sprintf($sQuery, $sFields);

        $aRows = $this->getDb()
            ->query($sQuery, $aBind)
            ->fetchAll();
        if (strpos($sQuery, 'SQL_CALC_FOUND_ROWS')) {
            $iRowCount = $this->getDb()->fetchOne('SELECT FOUND_ROWS() as `found`');
            $iRowCount = (isset($iRowCount['found'])) ? (int)$iRowCount['found'] : 0;
        }

        foreach ($aRows as &$aRow) {
            foreach ($aRow as $k => &$v) {
                if (is_numeric($k)) unset($aRow[$k]);
            }
        }

        $r = new Result($aRows, $iRowCount);
        return $r->setSearchParams($aSearchParams);
    }

    /**
     * @param $aSearchParams
     * @return Result
     */
    abstract function search($aSearchParams);
}