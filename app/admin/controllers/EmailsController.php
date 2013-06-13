<?php

namespace Admin\Controllers;

use Bitfalls\Phalcon\ControllerBase;
use Bitfalls\Utilities\Stringer;
use Services\MailerService;

/**
 * Class EmailsController
 */
class EmailsController extends ControllerBase
{

    public function indexAction()
    {

    }

    public function listAction()
    {
        $aSearchParams = $this->buildSearchParams(array(
            'q' => 'string', 'id' => 'int'
        ));

        /** @var MailerService $oService */
        $oService = $this->di->get('mailerService');
        $oResult = $oService->search($aSearchParams);

        $aResult = $oResult->getData();
        foreach ($aResult as $i => &$aRow) {
            $oModel = \EmailsTemplates::findFirst(array('id = :id:', 'bind' => array('id' => $aRow['id'])));
            $aResult[$i] = $oModel;
        }
        $oResult->setData($aResult);

        $this->view->setVar('result', $oResult);
        $this->setupPagination($oResult, '/admin/emails/list');

    }

    public function listqAction()
    {
        {
            $aSearchParams = $this->buildSearchParams(array(
                'q' => 'string',
                'id' => 'int',
                'minPriority' =>  'int',
                'maxPriority' => 'int',
                'sender' => 'string',
                'recipient' => 'string',
                'headers' => 'string',
                'sent' => 'int',
                'slug' => 'string',
                'created_from' => 'string',
                'created_to' => 'string',
                'toBeSentOnFrom' => 'string',
                'toBeSentOnTo' => 'string',
                'sentOnFrom' => 'string',
                'sentOnTo' => 'string',
            ));

            /** @var MailerService $oService */
            $oService = $this->di->get('mailerService');
            $oResult = $oService->searchArchiveQueue($aSearchParams);

            $aResult = $oResult->getData();
            foreach ($aResult as $i => &$aRow) {
                $oModel = \EmailsQueue::findFirst(array('id = :id:', 'bind' => array('id' => $aRow['id'])));
                $aResult[$i] = $oModel;
            }
            $oResult->setData($aResult);

            $this->view->setVar('result', $oResult);
            $this->setupPagination($oResult, '/admin/emails/listq');

        }
    }

    public function upsertAction()
    {
        $oModel = new \EmailsTemplates();
        $this->view->setVar('sHeading', 'Insert a new email template');
        $id = $this->getParam('id', null, 'digit');

        if ($id) {
            /** @var \EmailsTemplates $oModel */
            $oModel = $oModel->findFirst(
                array('id = :id:', 'bind' => array('id' => $id))
            );
            if (!$oModel) {
                $this->redirectBack();
                return;
            } else {
                $this->view->setVar('sHeading', 'Edit the email template "' . $oModel->getSlug() . '"');
            }
        }

        try {
            if ($this->request->isPost()) {

                if ($this->getParam('name', null, null, 'string')) {
                    $oModel->setName($this->getParam('name', null, null, 'string'));
                } else {
                    throw new \Exception('Please fill all required fields. Name missing.');
                }

                if ($this->getParam('slug', null, null, 'string')) {
                    $oModel->setSlug($this->getParam('slug', null, null, 'string'));
                } else {
                    throw new \Exception('Please fill all required fields. Slug missing.');
                }
                if ($this->getParam('subject', null, null, 'string')) {
                    $oModel->setSubject($this->getParam('subject', null, null, 'string'));
                } else {
                    throw new \Exception('Please fill all required fields. Subject missing.');
                }
                if ($this->getParam('body', null, null, 'string')) {
                    $oModel->setBody($this->getParam('body', null, null, 'string'));
                } else {
                    throw new \Exception('Please fill all required fields. Body missing..');
                }
                if ($this->getParam('body_html')) {
                    $oModel->setBodyHtml($this->getParam('body_html'));
                }
                if ($this->getParam('template_info', null, null, 'string')) {
                    $oModel->setTemplateInfo($this->getParam('template_info', null, null, 'string'));
                }

                if (!$oModel->save()) {
                    $sMessage = 'Save error: '.implode('. ', $oModel->getMessages());
                    throw new \Exception($sMessage);
                }

                $this->response->redirect('/admin/emails/upsert/id/' . $oModel->getId(), true);
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
                $oModel = new \EmailsTemplates();
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

    public function deleteqAction()
    {
        try {
            if ($this->getParam('id', null, 'digit')) {
                $oModel = new \EmailsQueue();
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

