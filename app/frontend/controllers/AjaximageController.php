<?php

namespace Frontend\Controllers;

use Bitfalls\Phalcon\ControllerBase;
use Bitfalls\Traits\Devlog;
use Bitfalls\Utilities\JsonError;
use Bitfalls\Utilities\JsonSuccess;

/**
 * Class AjaximageController
 */
class AjaximageController extends ControllerBase
{
    use Devlog;

    public function initialize()
    {
        $this->view->disable();
        $this->loginCheckAjax();
    }

    public function deleteimageAction()
    {

        $aMatches = array(
            'product' => 'Products',
            'sku' => 'Sku'
        );

        $post = $this->getAjaxPost(true);
        if (isset($post['type']) && isset($post['hash']) && isset($post['id'])) {

            if (!isset($aMatches[$post['type']])) {
                JsonError::getInstance()->setMessage('No class for type ' . $post['type'])->raise(true);
            } else {
                $sClassName = $aMatches[$post['type']];
                $oModel = new $sClassName();
                if (!method_exists($oModel, 'deleteImage')) {
                    JsonError::getInstance()->setMessage('Object does not have proper interface and cannot call deleteImage')->raise(true);
                } else {
                    if ($oModel->deleteImage($post['hash'])) {
                        JsonSuccess::getInstance()->raise(true);
                    } else {
                        JsonError::getInstance()->setMessage('Could not delete image for unknown reasons')->raise(true);
                    }
                }
            }

        } else {
            JsonError::getInstance()->setMessage('Missing data. Unable to proceed with request.')->raise(true);
        }

    }

}

