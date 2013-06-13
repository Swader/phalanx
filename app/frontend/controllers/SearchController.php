<?php

namespace Frontend\Controllers;

use Bitfalls\Phalcon\ControllerBase;
use Bitfalls\Utilities\JsonSuccess;
use \Products;
use Services\ProductService;
use Services\SkuService;

/**
 * Class SearchController
 */
class SearchController extends ControllerBase
{

    /** @var string */
    protected $q = null;

    /**
     * Disables views for all actions in this controller
     */
    public function initialize()
    {
        if (isset($_GET['q'])) {
            $this->q = $this->filter->sanitize($_GET['q'], 'string');
        }
        $this->view->disable();
    }

    public function indexAction()
    {
        $this->response->redirect('/', true);
        $this->view->enable();
        var_dump($this->q);
    }

    public function autocompleteAction()
    {


//        $bWithCache = true;
//
//        if (!$bWithCache) {
//            $aReturnValues = array();
//            /** @var $oSuggestion Products */
//            foreach (Products::find(array(
//                'name LIKE :name:',
//                'bind' => array('name' => '%' . $this->q . '%'),
//                'limit' => 15
//            )) as $oSuggestion) {
//                $aReturnValues[] = $oSuggestion->getName();
//            }
//        } else {
//            $oProducts = new Products();
//            $aAllProductPairs = $oProducts->getCachedPairs();
//            $q = preg_quote($this->q, '~');
//
//            $aReturnValues = preg_grep('~' . $q . '~', $aAllProductPairs);
//
//        }
//        die(json_encode(array_values($aReturnValues)));
    }

    public function mainAction()
    {
//        /** @var SkuService $productService */
//        $service = $this->getDI()->get('skuService');
//
//        $aSearchParams = $this->buildSearchParams(array('q' => 'string', 'id' => 'int'));
//        $oResult = $service->search($aSearchParams);
//
//        $aData = array();
//        foreach ($oResult as $aArray) {
//            $p = new \Sku();
//            $p->assign($aArray);
//            $aData[] = $p->getDummy();
//        }
//
//        JsonSuccess::getInstance()->setResult($aData)->raise(true);

    }

}

