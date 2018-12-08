<?php

/**
 * MaintenanceController.php
 * @author Jeff Peralta - jaydocperalta@gmail.com
 * @copyright Copyright (c) 2017. 
 * Date: Tue, 12 Sep 2017
 * Time: 12:53:07 +0800
 *
 * LICENSE : this file is the exclusive property. No unauthorized copy or modification is allowed.
 *
 */
class MaintenanceController extends Zend_Controller_Action
{
     public function init()
    {
        /* Initialize action controller here */
        $config = $this->_helper->ConfigHelper->getConfig();
        unset($config['status_list']['trip_alarm']);
        unset($config['status_list']['on_off_status']);
        foreach($config as $config_name => $config_value){
            $this->view->$config_name = $config_value;
        }
    }

    public function indexAction()
    {
        // action body
        $this->view->equipment_list = $this->_helper->EquipmentHelper->getList();
    }

    public function updateAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $equipment_id = $this->_request->getParam('equipment_id', '');
        $recompute_status = $this->_request->getParam('recompute_status', 0);
        $params = $this->_request->getParams();
        unset($params['controller']);
        unset($params['action']);
        unset($params['module']);
        unset($params['equipment_id']);
        unset($params['recompute_status']);

        $success = $this->_helper->EquipmentHelper->update($equipment_id, $params);
        if($success) {
            if($recompute_status) {
                $this->_helper->EquipmentHelper->updateStatus($equipment_id, 'maintenance_due');
            }
        }

        echo Zend_Json::encode(array(
            "success" => $success,
            "equipment" => $this->_helper->EquipmentHelper->getEquipment($equipment_id)
        ));
    }
}