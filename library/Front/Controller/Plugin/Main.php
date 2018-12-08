<?php

/**
 * Main.php
 * @author Jeff Peralta - jaydocperalta@gmail.com
 * @copyright Copyright (c) 2017. 
 * Date: Mon, 16 Oct 2017
 * Time: 14:31:12 +0800
 *
 * LICENSE : this file is the exclusive property. No unauthorized copy or modification is allowed.
 *
 */
class Front_Controller_Plugin_Main extends Zend_Controller_Plugin_Abstract
{
    public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request)
    {

    }

    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        Zend_Controller_Action_HelperBroker::addPath(APPLICATION_PATH . '/controllers/helpers');
    }

    public function dispatch(Zend_Controller_Request_Abstract $request)
    {

    }

    public function dispatchLoopShutdown()
    {

    }
}