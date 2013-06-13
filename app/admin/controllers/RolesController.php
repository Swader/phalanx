<?php

namespace Admin\Controllers;

use Bitfalls\Phalcon\ControllerBase;
use Services\RolesService;


/**
 * Class RolesController
 */
class RolesController extends ControllerBase
{

    public function indexAction()
    {
        $this->response->redirect('/admin/users/list');
    }

    public function listAction()
    {
        $aSearchParams = $this->buildSearchParams(array(
            'q' => 'string', 'id' => 'int'
        ));

        /** @var RolesService $oService */
        $oService = $this->di->get('rolesService');
        $oResult = $oService->search($aSearchParams);

        $aResult = $oResult->getData();
        foreach ($aResult as $i => &$aRow) {
            $aEntity = \UserRoles::findFirst(array('id = :id:', 'bind' => array('id' => $aRow['id'])));
            $aResult[$i] = $aEntity;
        }
        $oResult->setData($aResult);

        $this->view->setVar('result', $oResult);
        $this->setupPagination($oResult, '/admin/roles/list');
    }

    /**
     * @return \Phalcon\Http\ResponseInterface
     */
    public function upsertAction()
    {
        $oModel = new \UserRoles();
        $this->view->setVar('sHeading', 'Insert a new user role');
        $id = $this->getParam('id', null, 'digit');
        if ($id) {
            /** @var \UserRoles $oModel */
            $oModel = \UserRoles::findFirst(
                array('id = :id:', 'bind' => array('id' => $id))
            );
            if (!$oModel) {
                return $this->response->redirect('/admin/roles/list', true);
            } else {
                $this->view->setVar('sHeading', 'Edit the role "' . $oModel->getName() . '"');
            }
        }

        try {
            if ($this->request->isPost()) {

                if ($this->getParam('slug', null, null, 'string')) {
                    $oModel->setSlug($this->getParam('slug', null, null, 'string'));
                } else {
                    throw new \Exception('Please fill all required fields.');
                }

                if (!$oModel->getSlug()) {
                    throw new \Exception('Invalid slug.');
                }

                if ($this->getParam('name', null, null, 'string')) {
                    $oModel->setName($this->getParam('name', null, null, 'string'));
                } else {
                    throw new \Exception('Please fill all required fields.');
                }
                if ($this->getParam('description', null, null, 'string')) {
                    $oModel->setDescription($this->getParam('description', null, null, 'string'));
                } else {
                    throw new \Exception('Please fill all required fields.');
                }

                if (!$oModel->save()) {
                    $sMessage = 'Save error: '.implode('. ', $oModel->getMessages());
                    throw new \Exception($sMessage);
                }

                $this->response->redirect('/admin/roles/upsert/id/' . $oModel->getId(), true);
            }

        } catch (\Exception $e) {
            $this->view->setVar('errorMessage', $e->getMessage());
            $this->view->setVar('errorCode', $e->getCode());
            $this->view->setVar('stackTrace', $e->getTraceAsString());
        }

        $this->view->setVar('oEntity', $oModel);
    }

    public function deleteAction()
    {
        try {
            if ($this->getParam('id', null, 'digit')) {
                $oModel = new \UserRoles();
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

