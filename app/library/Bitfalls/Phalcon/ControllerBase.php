<?php

namespace Bitfalls\Phalcon;

use Bitfalls\Objects\Result;
use Bitfalls\Utilities\JsonError;
use Bitfalls\Utilities\Pagination;
use Phalcon\Mvc\Controller;
use Phalcon\Mvc\Dispatcher;

/**
 * Class ControllerBase
 */
class ControllerBase extends Controller
{

    /**
     * @param Dispatcher $dispatcher
     */
    public function beforeExecuteRoute(Dispatcher $dispatcher)
    {

        $aParams = $dispatcher->getParams();
        $aNewParams = array();

        for ($i = 0; $i < count($aParams); $i = $i + 2) {
            if (isset($aParams[$i + 1])) {
                $aNewParams[$aParams[$i]] = $aParams[$i + 1];
            }
        }

        $dispatcher->setParams(array_merge($aNewParams, $_GET, $this->getPost()));
    }

    /**
     * @return \Phalcon\Http\ResponseInterface|\Users
     */
    public function loginCheckAjax()
    {
        $oUser = \Users::getCurrent();
        if (!$oUser) {
            JsonError::getInstance()->setMessage('You must be logged in and have enough permissions to do this action.')->raise(true);
        }
    }

    /**
     * @param $sParam
     * @param null $mDefault
     * @param null $sCtype
     * @param null $mFilters
     * @return mixed|null
     * @throws \Exception
     */
    public function getParam($sParam, $mDefault = null, $sCtype = null, $mFilters = null)
    {
        $mValue = $this->getDispatcherParam($sParam, $mFilters);
        $mValue = ($mValue) ? $mValue : $mDefault;

        if ($sCtype) {
            $sFunction = 'ctype_' . $sCtype;
            if (!function_exists($sFunction)) {
                throw new \Exception('Ctype ' . $sCtype . ' not possible.');
            }
            $mValue = ($sFunction($mValue)) ? $mValue : $mDefault;
        }

        return $mValue;
    }

    /**
     * @param bool $bAsArray
     * @return array|\stdClass
     */
    public function getAjaxPost($bAsArray = false)
    {
        /** @var \stdClass $return */
        $return = json_decode(file_get_contents('php://input'));
        if ($bAsArray) {
            return (array)$return;
        }
        return $return;
    }

    /**
     * Redirects back to where the request came from
     */
    public function redirectBack()
    {
        $this->response->redirect($_SERVER['HTTP_REFERER'], true);
    }

    /**
     * Alias for $this->dispatcher->getParam
     *
     * @param $sParam
     * @param $mFilters
     * @return mixed
     */
    public function getDispatcherParam($sParam, $mFilters = null)
    {
        return $this->dispatcher->getParam($sParam, $mFilters);
    }

    /**
     * @param $aArray
     * @return array
     */
    protected function buildSearchParams($aArray)
    {
        $aSearchParams = array();

        $aBasics = array(
            array('limit', 'int', 50),
            array('page', 'int', 1),
            array('sort', 'string', 'main.id'),
            array('order', 'string', 'DESC')
        );

        foreach ($aBasics as $aBasic) {
            $dispatchedBasic = $this->dispatcher->getParam($aBasic[0], $aBasic[1]);
            if (!$dispatchedBasic) {
                $aSearchParams[$aBasic[0]] = (isset($this->$aBasic[0])) ? $this->$aBasic[0] : $aBasic[2];
            } else {
                $aSearchParams[$aBasic[0]] = $dispatchedBasic;
            }
        }

        foreach ($aArray as $sParam => $aSettings) {
            if (is_array($aSettings)) {
                $sFilter = $aSettings[0];
                $mDefault = $aSettings[1];
            } else {
                $sFilter = $aSettings;
            }
            $param = $this->dispatcher->getParam($sParam, $sFilter);
            if (!$param && isset($mDefault)) {
                $param = $mDefault;
            }
            if ($param) {
                $aSearchParams[$sParam] = $param;
            }
        }

        return $aSearchParams;
    }

    /**
     * @param Result $oResult
     * @param $sUrlPrefix
     * @return $this
     */
    protected function setupPagination(Result $oResult, $sUrlPrefix)
    {
        $oPagination = new Pagination($oResult);
        $oPagination->setUrlPrefix($sUrlPrefix);
        $this->view->setVar('pagination', $oPagination);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPost()
    {
        return $_POST;
    }

}