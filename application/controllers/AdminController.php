<?php

/**
 * AdminController.php
 * @author Jeff Peralta - jaydocperalta@gmail.com
 * @copyright Copyright (c) 2017.
 * Date: Tue, 12 Sep 2017
 * Time: 12:52:50 +0800
 *
 * LICENSE : this file is the exclusive property. No unauthorized copy or modification is allowed.
 *
 */
class AdminController extends Zend_Controller_Action
{
    public function init()
    {
        /* Initialize action controller here */
        $config = $this->_helper->ConfigHelper->getConfig();
        foreach($config as $config_name => $config_value){
            $this->view->$config_name = $config_value;
        }
    }

    public function indexAction()
    {
        // action body
        $this->view->equipment_list = $this->_helper->EquipmentHelper->getList();
    }

    public function equipmentAction()
    {
        $this->view->equipment_list = $this->_helper->EquipmentHelper->getList();
    }

    public function equipmentEditAction()
    {
        $id = $this->_request->getParam('id', 0);
        $this->view->equipment = $this->_helper->EquipmentHelper->getEquipment($id);
    }

    public function equipmentCreateAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $params = $this->_request->getParams();
        unset($params['controller']);
        unset($params['action']);
        unset($params['module']);

        $params['trip_alarm'] = 0;
        $params['on_off_status'] = 1;
        $params['maintenance_due'] = 0;
        $params['maintenance_duration'] = '30 days';
        $params['next_maintenance'] = date("Y-m-d H:i:s", strtotime("today + {$params['maintenance_duration']}"));
        $equipment_id = $this->_helper->EquipmentHelper->create($params);

        $success = false;
        if($equipment_id > 0) {
            $success = true;
        }

        echo Zend_Json::encode(array(
            "success" => $success,
            //--Not needed to query the equipment_id since the page will just refresh--
            //"equipment" => $this->_helper->EquipmentHelper->getEquipment($equipment_id)
        ));
    }

    public function equipmentUpdateAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $id = $this->_request->getParam('id', 0);
        $params = $this->_request->getParams();
        unset($params['controller']);
        unset($params['action']);
        unset($params['module']);
        unset($params['id']);

        $success = $this->_helper->EquipmentHelper->update($id, $params);

        if($success) {
            Zend_Controller_Action::_redirect('/admin');
        }else{
            Zend_Controller_Action::_redirect('/admin/equipment-edit?id='.$id);
        }
    }

    public function equipmentDeleteAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $id = $this->_request->getParam('id', 0);
        $success = $this->_helper->EquipmentHelper->delete($id);
        if($success) {
            Zend_Controller_Action::_redirect('/admin');
        }else{
            Zend_Controller_Action::_redirect('/admin');
        }
    }

    public function equipmentStatusCriteriaAction()
    {
        $id = $this->_request->getParam('id', 0);
        $this->view->equipment = $this->_helper->EquipmentHelper->getEquipment($id);
        $this->view->status_criteria_list = $this->_helper->EquipmentHelper->getCriteriaList($id);
    }

    public function equipmentStatusCreateAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $params = $this->_request->getParams();
        unset($params['controller']);
        unset($params['action']);
        unset($params['module']);
        $status_criteria_id = $this->_helper->EquipmentHelper->createStatusCriteria($params);

        $success = false;
        if($status_criteria_id > 0) {
            $success = true;
        }

        echo Zend_Json::encode(array(
            "success" => $success
        ));
    }

    public function equipmentStatusCriteriaEditAction()
    {
        $id = $this->_request->getParam('id', 0);
        $this->view->status_criterion = $this->_helper->EquipmentHelper->getStatusCriteria($id);
        $this->view->equipment = $this->_helper->EquipmentHelper->getEquipment($this->view->status_criterion['equipment_id']);
    }

    public function equipmentStatusCriteriaUpdateAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $id = $this->_request->getParam('id', 0);
        $equipment_id = $this->_request->getParam('equipment_id', 0);
        $params = $this->_request->getParams();
        unset($params['controller']);
        unset($params['action']);
        unset($params['module']);
        unset($params['id']);
        unset($params['equipment_id']);

        $success = $this->_helper->EquipmentHelper->updateStatusCriteria($id, $params);

        if($success) {
            Zend_Controller_Action::_redirect('/admin/equipment-status-criteria?id='.$equipment_id);
        }else{
            Zend_Controller_Action::_redirect('/admin/equipment-edit?id='.$id);
        }
    }

    public function equipmentStatusCriteriaDeleteAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $id = $this->_request->getParam('id', 0);
        $equipment_id = $this->_request->getParam('equipment_id', 0);
        $success = $this->_helper->EquipmentHelper->deleteStatusCriteria($id);
        if($success) {
            Zend_Controller_Action::_redirect('/admin/equipment-status-criteria?id='.$equipment_id);
        }else{
            Zend_Controller_Action::_redirect('/admin/equipment-status-criteria?id='.$equipment_id);
        }
    }

    public function configAction()
    {
        $this->view->success = $this->_request->getParam('success', '');
        $this->view->config_names = array(
             'adam_device_url'
            ,'' //--Spacer--
            ,'adam_device_username'
            ,'adam_device_password'
            ,'netcom_device_username'
            ,'netcom_device_password'
            ,'on_off_status_source'
            ,'nagios_dat_file_location'
            ,'' //--Spacer--
        );
    }

    public function configUpdateAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $params = $this->_request->getParams();

        try{
            $configTable = new Zend_Db_Table('config');
            foreach($params as $name => $value) {
                $where = array();
                $where = $configTable->getAdapter()->quoteInto('name = ?', $name);
                $update_fields = array( 'value' => $value);
                $configTable->update($update_fields, $where);
            }
            $success = 1;
        }catch(Exception $e) {
            $success = 0;
        }

        Zend_Controller_Action::_redirect('/admin/config?success='.$success);
    }

}