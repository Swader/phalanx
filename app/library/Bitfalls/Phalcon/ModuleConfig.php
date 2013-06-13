<?php

namespace Bitfalls\Phalcon;

use Phalcon\Dispatcher;
use Phalcon\Mvc\ModuleDefinitionInterface;
use Phalcon\Mvc\View;

/**
 * This is an extensible Module configurator for Multi Module apps. Just extend and give it a namespace
 * equal to module name, and it should work.
 *
 * Class ModuleConfig
 */
class ModuleConfig implements ModuleDefinitionInterface
{

    /** @var string */
    protected $sReflectionPath = '';
    /** @var null */
    protected $mConfig = null;

    public function registerAutoloaders()
    {
    }

    /**
     * Register specific services for the module
     */
    public function registerServices($di)
    {

        /** @var Dispatcher $dispatcher */
        $dispatcher = $di->get('dispatcher');
        $sModuleName = explode(DIRECTORY_SEPARATOR, trim($this->getReflectionPath(), '/'));
        $sModuleName = array_pop($sModuleName);
        $dispatcher->setDefaultNamespace(ucfirst($sModuleName)."\Controllers\\");
        $di->set('dispatcher', $dispatcher);

        //Registering the view component
        /** @var View $view */
        $view = $di->get('view');
        $path = $this->getReflectionPath();
        $view->setViewsDir($path . 'views/');
        $view->setLayoutsDir('../../views/layouts/');
        $view->setPartialsDir('../../views/shared/');
        $view->setVar('moduleName', $sModuleName);
        $view->setVar('bLoadModuleMenu', true);
        $di->set('view', $view);
    }

    /**
     * @param null $sPath
     * @return mixed
     */
    public function getConfig($sPath = null)
    {
        if (!$this->mConfig) {
            if (!$sPath) {
                $sPath = $this->getReflectionPath() . '/config/config.php';
            }
            if (is_readable($sPath)) {
                $this->mConfig = include_once $sPath;
            }
        }
        return $this->mConfig;
    }

    /**
     * @return mixed|string
     */
    protected function getReflectionPath()
    {
        if (empty($this->sReflectionPath)) {
            $oReflectionClass = new \ReflectionClass($this);
            $this->sReflectionPath = str_replace('Module.php', '', $oReflectionClass->getFileName());
        }
        return $this->sReflectionPath;
    }
}