<?php

include_once '../vendor/autoload.php';

$loader = new \Phalcon\Loader();

/**
 * We're a registering a set of directories taken from the configuration file
 */

$aNamespaceArray = array();
foreach ($aModules as $i => &$sModule) {
    $aNamespaceArray[ucfirst($sModule).'\Controllers'] = '../app/'.$sModule.'/controllers/';
}

$loader->registerNamespaces($aNamespaceArray);

$loader->registerDirs(
	array(
		$config->application->controllersDir,
		$config->application->modelsDir,
        $config->application->libraryDir
	)
)->register();