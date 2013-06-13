<?php

namespace Admin\Controllers;

use Bitfalls\Phalcon\ControllerBase;
use Bitfalls\Traits\Dates;
use Bitfalls\Utilities\Stringer;
use Services\GeoService;

/**
 * Class GeoController
 */
class GeoController extends ControllerBase
{
    use Dates;

    public function indexAction()
    {

        if ($this->request->isPost()) {

            if ($this->getParam('type') == 'country') {

                if ($this->getParam('import')) {
                    $file = $_FILES['file']['tmp_name'];
                } else {
                    $file = null;
                }

                $oCountries = new \Countries();
                $oCountries->import($file);

                $this->view->setVar('errors', $oCountries->getErrors());
                $this->view->setVar('successes', $oCountries->getSuccesses());

            } else if ($this->getParam('type') == 'city') {
                $oCities = new \Cities();
                $oCities->import($this->getParam('ignoreExisting'));

                $this->view->setVar('errors', $oCities->getErrors());
                $this->view->setVar('successes', $oCities->getSuccesses());

            }

        }

    }

    public function countrieslistAction()
    {
        $aSearchParams = $this->buildSearchParams(array(
            'q' => 'string', 'id' => 'int'
        ));

        /** @var GeoService $oService */
        $oService = $this->di->get('geoService');
        $oResult = $oService->search($aSearchParams);

        $aResult = $oResult->getData();

        foreach ($aResult as $i => &$aRow) {
            $oModel = \Countries::findFirst(array('id = :id:', 'bind' => array('id' => $aRow['id'])));
            $aResult[$i] = $oModel;
        }
        $oResult->setData($aResult);

        $this->view->setVar('result', $oResult);

        $this->setupPagination($oResult, '/admin/geo/countrieslist');
    }

    public function citieslistAction()
    {
        $aSearchParams = $this->buildSearchParams(array(
            'q' => 'string', 'id' => 'int'
        ));

        /** @var GeoService $oService */
        $oService = $this->di->get('geoService');
        $oResult = $oService->searchCities($aSearchParams);

        $aResult = $oResult->getData();

        foreach ($aResult as $i => &$aRow) {
            $oModel = \Cities::findFirst(array('id = :id:', 'bind' => array('id' => $aRow['id'])));
            $aResult[$i] = $oModel;
        }
        $oResult->setData($aResult);

        $this->view->setVar('result', $oResult);

        $this->setupPagination($oResult, '/admin/geo/citieslist');
    }

    public function stateslistAction()
    {
        $aSearchParams = $this->buildSearchParams(array(
            'q' => 'string', 'id' => 'int'
        ));

        /** @var GeoService $oService */
        $oService = $this->di->get('geoService');
        $oResult = $oService->searchStates($aSearchParams);

        $aResult = $oResult->getData();

        foreach ($aResult as $i => &$aRow) {
            $oModel = \States::findFirst(array('id = :id:', 'bind' => array('id' => $aRow['id'])));
            $aResult[$i] = $oModel;
        }
        $oResult->setData($aResult);

        $this->view->setVar('result', $oResult);

        $this->setupPagination($oResult, '/admin/geo/stateslist');
    }

    public function upsertcountryAction()
    {
        $oModel = new \Countries();
        $this->view->setVar('sHeading', 'Insert a new country');
        $id = $this->getParam('id', null, 'digit');

        if ($id) {
            /** @var \Countries $oModel */
            $oModel = $oModel->findFirst(
                array('id = :id:', 'bind' => array('id' => $id))
            );
            if (!$oModel) {
                if ($this->request->isPost()) {
                    $this->response->redirect('/admin/geo/countrieslist', true);
                } else {
                    $this->redirectBack();
                }
                return;
            } else {
                $this->view->setVar('sHeading', 'Edit the country entry: "' . $oModel->getCountryName() . '"');
            }
        }

        try {
            if ($this->request->isPost()) {

                $aFields = array(
                    'country_code' => true,
                    'country_name' => true,
                    'continent' => true,
                    'iso_numeric' => false,
                    'iso_alpha3' => false,
                    'fips' => false,
                    'phone_code' => false,
                    'tld' => false,
                    'currency_code' => false,
                    'currency_name' => false,
                    'postal_format' => false,
                    'postal_regex' => false,
                    'geonameid' => false,
                    'neighbours' => false
                );

                foreach ($aFields as $k => &$v) {
                    $value = $this->getParam($k);
                    if ($v === true && (!$value || empty($value))) {
                        throw new \Exception($k.' is a required field!');
                    }
                    $sMethod = 'set'.Stringer::toCamelCase($k);
                    $oModel->$sMethod($value);
                }

                if ($oModel->save() === false) {
                    $sMessage = 'Save error: ' . implode('. ', $oModel->getMessages());
                    throw new \Exception($sMessage);
                }

                $this->response->redirect('/admin/geo/upsertcountry/id/' . $oModel->getId(), true);
            }

        } catch (\Exception $e) {
            $this->view->setVar('errorMessage', $e->getMessage());
            $this->view->setVar('errorCode', $e->getCode());
            $this->view->setVar('stackTrace', $e->getTraceAsString());
        }

        $this->view->setVar('oEntity', $oModel);
    }

    public function upsertstateAction()
    {
        $oModel = new \States();
        $this->view->setVar('sHeading', 'Insert a new state');
        $id = $this->getParam('id', null, 'digit');

        if ($id) {
            /** @var \States $oModel */
            $oModel = $oModel->findFirst(
                array('id = :id:', 'bind' => array('id' => $id))
            );
            if (!$oModel) {
                if ($this->request->isPost()) {
                    $this->response->redirect('/admin/geo/stateslist', true);
                } else {
                    $this->redirectBack();
                }
                return;
            } else {
                $this->view->setVar('sHeading', 'Edit the state entry: "' . $oModel->getName() . '"');
            }
        }

        try {
            if ($this->request->isPost()) {

                $aFields = array(
                    'name' => true,
                    'short_name' => true,
                    'country_id' => true
                );

                foreach ($aFields as $k => &$v) {
                    $value = $this->getParam($k);
                    if ($v === true && (!$value || empty($value))) {
                        throw new \Exception($k.' is a required field!');
                    }
                    $sMethod = 'set'.Stringer::toCamelCase($k);
                    $oModel->$sMethod($value);
                }

                if ($oModel->save() === false) {
                    $sMessage = 'Save error: ' . implode('. ', $oModel->getMessages());
                    throw new \Exception($sMessage);
                }

                $this->response->redirect('/admin/geo/upsertstate/id/' . $oModel->getId(), true);
            }

        } catch (\Exception $e) {
            $this->view->setVar('errorMessage', $e->getMessage());
            $this->view->setVar('errorCode', $e->getCode());
            $this->view->setVar('stackTrace', $e->getTraceAsString());
        }

        $this->view->setVar('oEntity', $oModel);
    }

    public function upsertcityAction()
    {
        $oModel = new \Cities();
        $this->view->setVar('sHeading', 'Insert a new city');
        $id = $this->getParam('id', null, 'digit');

        if ($id) {
            /** @var \Cities $oModel */
            $oModel = $oModel->findFirst(
                array('id = :id:', 'bind' => array('id' => $id))
            );
            if (!$oModel) {
                if ($this->request->isPost()) {
                    $this->response->redirect('/admin/geo/citieslist', true);
                } else {
                    $this->redirectBack();
                }
                return;
            } else {
                $this->view->setVar('sHeading', 'Edit the city entry: "' . $oModel->getName() . '"');
            }
        }

        try {
            if ($this->request->isPost()) {

                $aFields = array(
                    'name' => true,
                    'clean_name' => true,
                    'latitude' => true,
                    'longitude' => true,
                    'state_id' => false,
                    'country_id' => true
                );

                foreach ($aFields as $k => &$v) {
                    $value = $this->getParam($k);
                    if ($v === true && (!$value || empty($value))) {
                        throw new \Exception($k.' is a required field!');
                    }
                    $sMethod = 'set'.Stringer::toCamelCase($k);
                    $oModel->$sMethod($value);
                }

                if ($oModel->save() === false) {
                    $sMessage = 'Save error: ' . implode('. ', $oModel->getMessages());
                    throw new \Exception($sMessage);
                }

                $this->response->redirect('/admin/geo/upsertcity/id/' . $oModel->getId(), true);
            }

        } catch (\Exception $e) {
            $this->view->setVar('errorMessage', $e->getMessage());
            $this->view->setVar('errorCode', $e->getCode());
            $this->view->setVar('stackTrace', $e->getTraceAsString());
        }

        $this->view->setVar('oEntity', $oModel);
    }

    public function deletecountryAction()
    {
        try {
            if ($this->getParam('id', null, 'digit')) {
                $oModel = new \Countries();
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

    public function deletecityAction()
    {
        try {
            if ($this->getParam('id', null, 'digit')) {
                $oModel = new \Cities();
                $oModel = $oModel->findFirst(array('id = :id:', 'bind' => array('id' => $this->getParam('id'))));
                if ($oModel && $oModel->addresses->count() == 0) {
                    $oModel->delete();
                } else {
                    throw new \Exception('You cannot delete a city which is still bound to some addresses!');
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

    public function deletestateAction()
    {
        try {
            if ($this->getParam('id', null, 'digit')) {
                $oModel = new \States();
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

