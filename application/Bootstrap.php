<?php
/**
 * Bootstrap.php
 * @author Jeff Peralta - jaydocperalta@gmail.com
 * @copyright Copyright (c) 2017.
 * Date: Tue, 12 Sep 2017
 * Time: 12:55:07 +0800
 *
 * LICENSE : this file is the exclusive property. No unauthorized copy or modification is allowed.
 *
 */

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    protected function _initDb()
    {
        // Set the db resources
        $this->bootstrap('multidb');
    	$resource = $this->getPluginResource('multidb');
    	Zend_Registry::set("multidb", $resource);
    	Zend_Registry::set("appdb", $resource->getDb('appdb'));
    	Zend_Registry::set("docdb", $resource->getDb('docdb'));
    }

    protected function _initAutoloaders(){
		// instantiate the loader
		$loader = Zend_Loader_Autoloader::getInstance();

		//specify class namespaces you want to be auto-loaded.
		// 'Zend_' and 'ZendX_' are included by default
		$class_path = array(
            'Library_'
        );

        $loader->registerNamespace($class_path);

        //  optional argument if you want the auto-loader to load ALL namespaces
        $loader->setFallbackAutoloader(true);
	}

}

