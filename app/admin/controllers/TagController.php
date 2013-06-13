<?php

namespace Admin\Controllers;

use Bitfalls\Phalcon\ControllerBase;
use Bitfalls\Phalcon\Model;
use Bitfalls\Utilities\Stringer;
use Services\TagService;

/**
 * Class TagController
 */
class TagController extends ControllerBase
{

    public function indexAction()
    {

    }

    public function listotherAction()
    {
        $oResult1 = \TagTypes::find();
        $this->view->setVar('result_tagtypes', $oResult1);

        $oResult2 = \Entities::find();
        $this->view->setVar('result_entities', $oResult2);
    }

    public function listAction()
    {
        $aSearchParams = $this->buildSearchParams(array(
            'q' => 'string', 'id' => 'int'
        ));

        /** @var TagService $oService */
        $oService = $this->di->get('tagService');
        $oResult = $oService->search($aSearchParams);

        $aResult = $oResult->getData();
        foreach ($aResult as $i => &$aRow) {
            $oModel = \Tags::findFirst(array('id = :id:', 'bind' => array('id' => $aRow['id'])));
            $aResult[$i] = $oModel;
        }
        $oResult->setData($aResult);

        $this->view->setVar('result', $oResult);
        $this->setupPagination($oResult, '/admin/tag/list');

    }

    public function upsertotherAction()
    {

        $aModels = array(
            '\Entities' => 'entity',
            '\TagTypes' => 'tagtype'
        );

        if ($this->request->isPost()) {
            foreach ($aModels as $sModel => $sPostKey) {
                if (isset($_POST[$sPostKey]['new'])) {
                    $aNewEntries = $_POST[$sPostKey]['new'];
                    unset($_POST[$sPostKey]['new']);

                    switch ($sPostKey) {
                        case 'entity':
                            $aFields = array('entity', 'references', 'description');
                            break;
                        case 'tagtype':
                            $aFields = array('type', 'description');
                            break;
                        default:
                            throw new \Exception('Wrong post key: ' . $sPostKey);
                            break;
                    }
                    $aRequiredFields = array($aFields[0]);
                    $iCount = count($aNewEntries[$aFields[0]]);
                    $aInsertions = array();
                    for ($i = 0; $i < $iCount; $i++) {
                        $aEntry = array();
                        foreach ($aFields as $sField) {
                            $aEntry[$sField] = (isset($aNewEntries[$sField][$i])) ? $aNewEntries[$sField][$i] : null;
                            if (empty($aEntry[$sField]) && in_array($sField, $aRequiredFields)) {
                                continue 2;
                            }
                        }
                        $aInsertions[] = $aEntry;
                    }

                    foreach ($aInsertions as &$aEntry) {
                        $oModel = new $sModel();
                        foreach ($aFields as $sField) {
                            if (in_array($sField, $aRequiredFields) && empty($aEntry[$sField])) {
                                continue 2;
                            } else {
                                $sMethodName = 'set' . Stringer::toCamelCase($sField);
                                $oModel->$sMethodName($aEntry[$sField]);
                            }
                        }
                        if (!$oModel->save()) {
                            continue;
                        }
                    }

                }

                foreach ($_POST[$sPostKey] as $aEntry) {

                    $iId = (int)$aEntry['id'];
                    $aFields = array();
                    switch ($sPostKey) {
                        case 'entity':
                            $sEntity = $aEntry['entity'];
                            $sReferences = $aEntry['references'];
                            $sDescription = $aEntry['description'];

                            if (empty($sEntity) || empty($iId)) {
                                throw new \Exception('Missing entity value.');
                            }

                            $aFields = array(
                                'entity' => $sEntity,
                                'references' => $sReferences,
                                'description' => $sDescription
                            );

                            break;
                        case 'tagtype':
                            $sType = $aEntry['type'];
                            $sDescription = $aEntry['description'];

                            if (empty($sType) || empty($iId)) {
                                throw new \Exception('Type name missing.');
                            }

                            $aFields = array(
                                'description' => $sDescription,
                                'type' => $sType
                            );

                            break;
                        default:
                            throw new \Exception('Wrong post key: ' . $sPostKey);
                            break;
                    }

                    /** @var Model $oModel */
                    $oModel = $sModel::findFirst(array('id = :id:', 'bind' => array('id' => $iId)));
                    if (!$oModel) {
                        throw new \Exception('ID ' . $iId . ' produced no results');
                    } else {
                        foreach ($aFields as $k => $v) {
                            $fGet = 'get' . Stringer::toCamelCase($k);
                            $fSet = 'set' . Stringer::toCamelCase($k);

                            if ($oModel->$fGet() != $v) {
                                $oModel->$fSet($v);
                                $oModel->isDirty(true);
                            }

                            if ($oModel->isDirty()) {
                                if ($oModel->save() === false) {
                                    throw new \Exception('Save error on entry ' . $oModel->getId() . ': ' . implode(', ', $oModel->getMessages()));
                                }
                            }
                        }
                    }

                }
            }
        }

        $this->redirectBack();
    }

    public function upsertAction()
    {

        $oModel = new \Tags();
        $this->view->setVar('sHeading', 'Insert a new tag');
        $id = $this->getParam('id', null, 'digit');

        if ($id) {
            /** @var \Tags $oModel */
            $oModel = $oModel->findFirst(
                array('id = :id:', 'bind' => array('id' => $id))
            );
            if (!$oModel) {
                if ($this->request->isPost()) {
                    $this->response->redirect('/admin/tag/list', true);
                } else {
                    $this->redirectBack();
                }
                return;
            } else {
                $this->view->setVar('sHeading', 'Edit the tag "' . $oModel->getTag() . '"');
            }
        }

        try {
            if ($this->request->isPost()) {

                $sTag = $this->getParam('tag', null, null, 'string');
                if ($sTag) {
                    $oModel->setTag($sTag);
                } else {
                    throw new \Exception('Tag field is required.');
                }

                $iType = $this->getParam('tag_type', null, 'digit');
                if ($iType) {
                    $oModel->setTagType($iType);
                } else {
                    throw new \Exception('Type must be set.');
                }

                $oModel->setParent($this->getParam('parent', null, 'digit'));
                $oModel->setDescription($this->getParam('description', null, null, 'string'));

                if ($oModel->save() === false) {
                    $sMessage = 'Save error: ' . implode('. ', $oModel->getMessages());
                    throw new \Exception($sMessage);
                }

                $this->response->redirect('/admin/tag/upsert/id/' . $oModel->getId(), true);
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
                $oModel = new \Stores();
                $oModel = $oModel->findFirst(array('id = :id:', 'bind' => array('id' => $this->getParam('id'))));
                if ($oModel->sku->count() == 0) {
                    $oModel->delete();
                } else {
                    throw new \Exception('Store is still applied to some SKU!');
                }
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

    public function deletetypeAction()
    {
        try {
            if ($this->getParam('id', null, 'digit')) {
                $oModel = new \TagTypes();
                $oModel = $oModel->findFirst(array('id = :id:', 'bind' => array('id' => $this->getParam('id'))));
                if ($oModel) {
                    $oModel->delete();
                }
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

    public function deleteentityAction()
    {
        try {
            if ($this->getParam('id', null, 'digit')) {
                $oModel = new \Entities();
                $oModel = $oModel->findFirst(array('id = :id:', 'bind' => array('id' => $this->getParam('id'))));
                if ($oModel) {
                    $oModel->delete();
                }
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

