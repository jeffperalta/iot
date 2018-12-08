<?php

/**
 * AlarmController.php
 * @author Jeff Peralta - jaydocperalta@gmail.com
 * @copyright Copyright (c) 2017.
 * Date: Thu, 21 Sep 2017
 * Time: 16:50:42 +0800
 *
 * LICENSE : this file is the exclusive property. No unauthorized copy or modification is allowed.
 *
 */
class AlarmController extends Zend_Controller_Action
{
    public function init()
    {

    }

    public function indexAction()
    {
        $view = $this->_request->getParam('view', '');
        $equipment_id = $this->_request->getParam('equipment_id', 'ST-01,ST-02,ST-03,ST-04,ST-05,PST-01,PST-02,WA-01,WA-02,WA-03,WA-04,WA-05,WA-06,WA-07,WA-08,CW-01,CW-02');

        $results = "OK";
        $equipment_status = $this->_helper->DocQueryHelper->getMachineStatus($equipment_id);
        //echo"<pre>";print_r( $equipment_status);die;

        $this->view->view_type = $view;
        $this->view->results = $results;
        $this->view->equipment_status = $equipment_status;
    }
}