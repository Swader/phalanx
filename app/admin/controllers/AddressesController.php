<?php

namespace Admin\Controllers;

use Bitfalls\Phalcon\ControllerBase;
use Bitfalls\Traits\Dates;
use Bitfalls\Utilities\Stringer;

/**
 * Class AddressesController
 */
class AddressesController extends ControllerBase
{
    use Dates;

    public function listresidencetypesAction()
    {
        $oResult = \AddressResidenceTypes::find();
        $this->view->setVar('result', $oResult);
    }

    public function residencetypeupsertAction()
    {

        $sModel = '\AddressResidenceTypes';

        if ($this->request->isPost()) {
            $aNewEntries = $_POST['entry']['new'];
            unset($_POST['entry']['new']);
            foreach ($_POST['entry'] as $aEntry) {

                $sName = $aEntry['name'];
                $iId = (int)$aEntry['id'];
                $sDescription = $aEntry['description'];

                if (empty($sName) || empty($iId)) {
                    throw new \Exception('You failed to provide a name and/or valid ID.');
                } else {
                    /** @var \AddressResidenceTypes $oModel */
                    $oModel = new $sModel();
                    $oModel = $oModel->findFirst(array('id = :id:', 'bind' => array('id' => $iId)));
                    if ($oModel->getDescription() != $sDescription) {
                        $oModel->setDescription($sDescription);
                        $oModel->isDirty(true);
                    }
                    if ($oModel->getName() != $sName) {
                        $oModel->setName($sName);
                    }
                    if ($oModel->isDirty()) {
                        if ($oModel->save() === false) {
                            throw new \Exception('Save error on entry '.$oModel->getId().': '.implode(', ', $oModel->getMessages()));
                        }
                    }
                }
            }

            $aFields = array('name', 'slug', 'description');
            $aRequiredFields = array($aFields[0], $aFields[1]);
            $iCount = count($aNewEntries[$aFields[0]]);
            $aInsertions = array();
            for ($i = 0; $i < $iCount; $i++) {
                $aEntry = array();
                foreach ($aFields as $sField) {
                    $aEntry[$sField] = (isset($aNewEntries[$sField][$i])) ? $aNewEntries[$sField][$i] : null;
                }
                $aInsertions[] = $aEntry;
            }

            foreach ($aInsertions as &$aEntry) {
                $oModel = new $sModel();
                foreach ($aFields as $sField) {
                    $sMethodName = 'set'.ucfirst($sField);
                    $oModel->$sMethodName($aEntry[$sField]);
                }
                if (!$oModel->save()) {
                    continue;
                }
            }
        }

        $this->redirectBack();

    }

    public function deleteresidencetypeAction()
    {
        try {
            if ($this->getParam('id', null, 'digit')) {
                $oModel = new \AddressResidenceTypes();
                $oModel = $oModel->findFirst(array('id = :id:', 'bind' => array('id' => $this->getParam('id'))));
                if ($oModel->addresses->count() == 0) {
                    $oModel->delete();
                } else {
                    throw new \Exception('Addresses still bound to this type! Give them another type first!');
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

