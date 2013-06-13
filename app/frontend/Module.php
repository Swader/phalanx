<?php

namespace Frontend;

use Bitfalls\Phalcon\ModuleConfig;
use Phalcon\Mvc\View;

class Module extends ModuleConfig
{
    /**
     * @param \Phalcon\DiInterface $di
     */
    public function registerServices($di) {
        parent::registerServices($di);
        $view = $di->get('view');
        $view->setVar('bLoadModuleMenu', false);
        $di->set('view', $view);
    }
}