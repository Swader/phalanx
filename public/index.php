<?php

error_reporting(E_ALL);

try {

    /**
     * Read the configuration
     */
    $config = include __DIR__ . "/../app/config/config.php";

    /**
     * Read auto-loader
     */
    include __DIR__ . "/../app/config/loader.php";

    /**
     * Read services
     */
    include __DIR__ . "/../app/config/services.php";

    /**
     * Handle the request
     */
    $application = new \Phalcon\Mvc\Application();
    $application->setDI($di);

    $application->registerModules(
        array(
            'frontend' => array(
                'className' => 'Frontend\Module',
                'path' => '../app/frontend/Module.php',
            ),
            'admin' => array(
                'className' => 'Admin\Module',
                'path' => '../app/admin/Module.php',
            )
        )
    );

    set_exception_handler('\Bitfalls\Exceptions\Handler::handle');

    echo $application->handle()->getContent();

} catch (Phalcon\Exception $e) {
    echo $e->getMessage();
} catch (PDOException $e) {
    echo $e->getMessage();
}