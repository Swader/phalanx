<?php

namespace Frontend\Controllers;

use Bitfalls\Phalcon\ControllerBase;
use Bitfalls\Traits\Devlog;
use Bitfalls\Utilities\JsonError;
use Services\GeoService;

/**
 * Class AjaxgeoController
 */
class AjaxgeoController extends ControllerBase
{
    use Devlog;

    public function initialize()
    {
        $this->view->disable();
        if (!\Users::getCurrent()) {
            JsonError::getInstance()->setMessage('You must be logged in to access the AJAX API.')->raise(true);
        }
    }

    public function indexAction()
    {

        $aReturn = array();
        switch ($this->getParam('q')) {
            case 'countries':
                foreach (\Countries::getCachedPairs() as $k => $v) {
                    $o = new \stdClass();
                    $o->id = $k;
                    $o->name = $v;
                    $aReturn[] = $o;
                }
                break;
            case 'cities':
                /** @var GeoService $oGeoService */
                $oGeoService = $this->getDI()->get('geoService');
                foreach ($oGeoService->getCityPairsByCid($this->getParam('cid')) as $k => $v) {
                    $o = new \stdClass();
                    $o->id = $k;
                    $o->name = $v;
                    $aReturn[] = $o;
                }
                break;
            case 'residence_types':
                foreach (\AddressResidenceTypes::getCachedPairs() as $k => $v) {
                    $o = new \stdClass();
                    $o->slug = $k;
                    $o->name = $v;
                    $aReturn[] = $o;
                }
                break;
            default:
                break;
        }
        die(json_encode($aReturn));

    }

}

