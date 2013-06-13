<?php

namespace Frontend\Controllers;

use Bitfalls\Mailer\Mailer;
use Bitfalls\Phalcon\ControllerBase;
use Bitfalls\Traits\Devlog;
use Bitfalls\Utilities\Parser;
use Phalcon\Logger\Adapter\File;
use Tekapo\Utilities\ProductImporter;

/**
 * Class IndexController
 */
class IndexController extends ControllerBase
{
    use Devlog;

    public function initialize()
    {
        $this->view->setParamToView('showHomeLink', false);
    }

    public function indexAction()
    {


    }

}

