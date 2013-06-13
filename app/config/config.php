<?php

/**
 * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 * Development environments
 */
const ENVIRONMENT_PRODUCTION = 'production';
const ENVIRONMENT_DEVELOPMENT = 'development';
const ENVIRONMENT_MAINTENANCE = 'maintenance';

define ('ENVIRONMENT', ENVIRONMENT_DEVELOPMENT);
/**
 * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 */

$aModules = array('frontend', 'admin');

return new \Phalcon\Config(array(
    'database' => array(
        'adapter' => 'Mysql',
        'host' => 'localhost',
        'username' => 'myuser',
        'password' => 'mypass',
        'dbname' => 'mydb',
    ),
    'application' => array(
        'controllersDir' => __DIR__ . '/../../app/controllers/',
        'modelsDir' => __DIR__ . '/../../app/models/',
        'viewsDir' => __DIR__ . '/../../app/views/',
        'viewsDir_frontend' => __DIR__ . '/../../app/frontent/views/',
        'viewsDir_admin' => __DIR__ . '/../../app/admin/views/',
        'pluginsDir' => __DIR__ . '/../../app/plugins/',
        'libraryDir' => __DIR__ . '/../../app/library/',
        'cacheDir' => __DIR__ . '/../../app/cache/',
        'logDir' => __DIR__ . '/../../logs/logger/',
        'picturesDir' => __DIR__ . '/../../public/cdn/images/',
        'baseUri' => '/mysite.com/',
        'siteUrl' => 'http://mysite.com',
        'siteName' => 'My Site Name'
    )
));
