<?php

namespace Admin\Controllers;

use Bitfalls\Phalcon\ControllerBase;
use Services\ContactsService;

/**
 * Class ContactsController
 */
class ContactsController extends ControllerBase
{

    public function indexAction()
    {

    }

    public function listAction()
    {
        $aSearchParams = $this->buildSearchParams(array(
            'q' => 'string', 'id' => 'int'
        ));

        /** @var ContactsService $oService */
        $oService = $this->di->get('contactsService');
        $oResult = $oService->search($aSearchParams);

        $aResult = $oResult->getData();

        foreach ($aResult as $i => &$aRow) {
            /** @var \Contacts $oContact */
            $oContact = \Contacts::findFirst(array('id = :id:', 'bind' => array('id' => $aRow['id'])));
            if (!$oContact->user && $oContact->mainFor) {
                $oContact->setUserId($oContact->mainFor->getId());
                $oContact->save();
            }
            $aResult[$i] = $oContact;
        }

        $oResult->setData($aResult);

        $this->view->setVar('result', $oResult);
        $this->setupPagination($oResult, '/admin/contacts/list');

    }

    public function upsertAction()
    {
        $oContactsModel = new \Contacts();
        $id = $this->getParam('id', null, 'digit');
        if ($id) {
            $oContactsModel = $oContactsModel->findFirst(
                array('id = :id:', 'bind' => array('id' => $id))
            );
            $this->view->setVar('sHeading', 'Edit the contact "' . $oContactsModel->getEmail() . '"');
        } else {
            $this->view->setVar('sHeading', 'Insert a new contact');
        }

        try {
            if ($this->request->isPost()) {

                if (!filter_var($this->getParam('email', null, null, 'email'), FILTER_VALIDATE_EMAIL)) {
                    throw new \Exception('The email "' . $this->getParam('email') . '" does not seem to be a valid email address.');
                } else {
                    $oContactsModel->setEmail($this->getParam('email'));
                }

                $oContactsModel->setActivated($this->getParam('activated', 0));
                if ($oContactsModel->getActivated() == 1) {
                    $oContactsModel->setActivatedOn(date('Y-m-d H:i:s'));
                } else {
                    $oContactsModel->setActivatedOn(null);
                }
                $oContactsModel->save();

                $this->response->redirect('/admin/contacts/upsert/id/' . $oContactsModel->getId(), true);
            }

        } catch (\Exception $e) {
            $this->view->setVar('errorMessage', $e->getMessage());
            $this->view->setVar('errorCode', $e->getCode());
            $this->view->setVar('stackTrace', $e->getTraceAsString());
        }

        $this->view->setVar('contact', $oContactsModel);
    }

    public function deleteAction()
    {
        try {
            if ($this->getParam('id', null, 'digit')) {
                $oContactModel = new \Contacts();
                $oContactModel->findFirst(array('id = :id:', 'bind' => array('id' => $this->getParam('id'))))->delete();
                $this->redirectBack();
            } else {
                throw new \Exception('ID not found or not valid.');
            }
        } catch (\Exception $e) {
            $this->view->setVar('errorMessage', $e->getMessage());
            $this->view->setVar('errorCode', $e->getCode());
            $this->view->setVar('stackTrace', $e->getTraceAsString());
        }
    }

}

