<?php

namespace Frontend\Controllers;

use Bitfalls\Phalcon\ControllerBase;
use Bitfalls\Traits\Devlog;
use Bitfalls\Utilities\JsonError;
use Bitfalls\Utilities\JsonSuccess;
use Services\ContactsService;

/**
 * Class AjaxuserbaseController
 */
class AjaxuserbaseController extends ControllerBase
{
    use Devlog;

    public function initialize()
    {
        $this->view->disable();
        $this->loginCheckAjax();
    }

    public function contactAction() {
        $id = $this->getParam('id', null, 'digit');
        $oContact = false;
        if ($id) {
            $oContact = \Contacts::findFirst(array('id = :id:', 'bind' => array('id' => $id)));
            if (!$oContact) {
                JsonError::getInstance()->setMessage('This contact entry does not exist');
            }
        }
        /** @var ContactsService $oContactService */
        $oContactService = $this->getDI()->get('contactsService');
        if ($this->request->isGet()) {
            if ($oContact) {
                /** @var \Contacts $oContact */
                JsonSuccess::getInstance()->setMessage('Success')->setResult($oContact->getDummy());
            }
        }

        if ($this->request->isDelete()) {
            if ($oContact && $oContact->delete()) {
                JsonSuccess::getInstance()->setResult(true)->raise(true);
            } else {
                JsonError::getInstance()->setResult(false)->raise(true);
            }
        }

        if ($this->request->isPost()) {
            switch ($this->getParam('action')) {
                case 'makeDefault':

                    break;
                case 'saveEdit':

                    break;
                default:
                    break;
            }
        }
    }

    public function resendactivationAction() {
        $post = $this->getAjaxPost(true);
        if (isset($post['id'])) {
            $oContact = \Contacts::findFirst(array('id = :id:', 'bind' => array('id'  => $post['id'])));
            /** @var \Contacts $oContact */
            if ($oContact) {
                /** @var ContactsService $oService */
                $oService = $this->getDI()->get('contactsService');
                if ($oService->sendActivationEmail($oContact)) {
                    JsonSuccess::getInstance()->setResult(true)->raise(true);
                } else {
                    JsonError::getInstance()->setMessage('Sending failed. Contact support.')->raise(true);
                }
            }
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
                /** @var \Services\GeoService $oGeoService */
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

