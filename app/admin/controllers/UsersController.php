<?php

namespace Admin\Controllers;

use Bitfalls\Objects\Result;
use Bitfalls\Phalcon\ControllerBase;
use Services\UsersService;

/**
 * Class UsersController
 */
class UsersController extends ControllerBase
{

    public function indexAction()
    {
        $this->response->redirect('/admin/users/list', true);
    }

    public function listAction()
    {
        $aSearchParams = $this->buildSearchParams(array(
            'q' => 'string', 'id' => 'int'
        ));

        /** @var UsersService $oService */
        $oService = $this->di->get('usersService');
        $oResult = $oService->search($aSearchParams);

        $aResult = $oResult->getData();
        foreach ($aResult as $i => &$aRow) {
            $oUser = \Users::findFirst(array('id = :id:', 'bind' => array('id' => $aRow['id'])));
            $aResult[$i] = $oUser;
        }
        $oResult->setData($aResult);

        $this->view->setVar('result', $oResult);
        $this->setupPagination($oResult, '/admin/users/list');
    }

    /**
     * @return \Phalcon\Http\ResponseInterface
     */
    public function upsertAction()
    {

        $oRoles = new \UserRoles();
        $this->view->setVar('allRoles', $oRoles->find());

        $oModel = new \Users();
        $this->view->setVar('sHeading', 'Insert a new user account');
        $id = $this->getParam('id', null, 'digit');

        if ($id) {
            /** @var \Users $oModel */
            $oModel = \Users::findFirst(
                array('id = :id:', 'bind' => array('id' => $id))
            );
            if (!$oModel) {
                return $this->response->redirect('/admin/users/list', true);
            } else {
                $this->view->setVar('sHeading', 'Edit the user account "' . $oModel->getUsername() . '"');
            }
        }

        try {
            if ($this->request->isPost()) {

                if ($this->getParam('main_contact_email', null, null, 'email')) {
                    $oMainContact = \Contacts::findFirst(array('email = :email:', 'bind' => array('email' => $this->getParam('main_contact_email'))));
                    if (!$oMainContact) {
                        $oMainContact = new \Contacts();
                        $oMainContact->setEmail($this->getParam('main_contact_email'));
                        $oMainContact->setActivated(1);
                        if (!$oMainContact->save()) {
                            throw new \Exception('Contact Save Error: '.implode(', ', $oMainContact->getMessages()));
                        }
                    }
                    // @todo Check why the assignment to relationship won't save here
                    //$oModel->mainContact = $oMainContact;
                    $oModel->setMainContactId($oMainContact->getId());
                } else {
                    throw new \Exception('Please fill all required fields.');
                }

                if ($this->getParam('first_name', null, null, 'string')) {
                    $oModel->setFirstName($this->getParam('first_name', null, null, 'string'));
                } else {
                    throw new \Exception('Please fill all required fields.');
                }
                if ($this->getParam('last_name', null, null, 'string')) {
                    $oModel->setLastName($this->getParam('last_name', null, null, 'string'));
                } else {
                    throw new \Exception('Please fill all required fields.');
                }
                if ($this->getParam('username', null, null, 'string')) {
                    $oModel->setUsername($this->getParam('username', null, null, 'string'));
                } else {
                    throw new \Exception('Please fill all required fields.');
                }
                if ($this->getParam('password', null, null, 'string')) {
                    $oModel->setPassword($this->getParam('password', null, null, 'string'));
                } else {
                    $oModel->setPassword(md5(time()));
                }

                $oModel->setType(1);

                $oCurrentUser = \Users::getCurrent();
                if ($oCurrentUser) {
                    $oModel->setCreatedBy($oCurrentUser->getId());
                } else {
                    $oModel->setCreatedBy(null);
                }

                if (!$oModel->save()) {
                    $sMessage = 'Save error: '.implode('. ', $oModel->getMessages());
                    throw new \Exception($sMessage);
                } else {
                    $oMainContact->setUserId($oModel->getId());
                    $oMainContact->save();

                    $aSelectedRoles = $this->getParam('userRoles', array());

                    $oModel->usersRoles->delete();
                    $aUsersRoles = array();
                    foreach ($aSelectedRoles as $sSelectedRole) {
                        $oUR = new \UsersRoles();
                        $oUR->setRoleSlug($sSelectedRole);
                        $oUR->setUserId($oModel->getId());
                        $aUsersRoles[] = $oUR;
                    }
                    $oModel->usersRoles = $aUsersRoles;
                    if (!$oModel->save()) {
                        $sMessage = 'Save error: '.implode('. ', $oModel->getMessages());
                        throw new \Exception($sMessage);
                    }
                }

                $this->response->redirect('/admin/users/upsert/id/' . $oModel->getId(), true);
            }

        } catch (\Exception $e) {
            $this->view->setVar('errorMessage', $e->getMessage());
            $this->view->setVar('errorCode', $e->getCode());
            $this->view->setVar('stackTrace', $e->getTraceAsString());
        }

        $this->view->setVar('user', $oModel);

    }

    public function deleteAction()
    {
        try {
            if ($this->getParam('id', null, 'digit')) {
                $oModel = new \Users();
                $oModel->findFirst(array('id = :id:', 'bind' => array('id' => $this->getParam('id'))))->delete();
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

