<?php
/**
 * TestController.php
 * @author Jeff Peralta - jaydocperalta@gmail.com
 * @copyright Copyright (c) 2017.
 * Date: Tue, 12 Sep 2017
 * Time: 12:53:11 +0800
 *
 * LICENSE : this file is the exclusive property. No unauthorized copy or modification is allowed.
 *
 */

class TestController extends Zend_Controller_Action
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

    public function updateAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $equipment_id = $this->_request->getParam('equipment_id', '');
        $params = $this->_request->getParams();
        unset($params['controller']);
        unset($params['action']);
        unset($params['module']);
        unset($params['equipment_id']);

        $success = $this->_helper->EquipmentHelper->update($equipment_id, $params);

        echo Zend_Json::encode(array(
            "success" => $success,
            "equipment" => $this->_helper->EquipmentHelper->getEquipment($equipment_id)
        ));
    }

    public function criteriaAction()
    {

        $equipment_id = "WA-01";
        $results = $this->_helper->EquipmentHelper->checkCriteria('http://ibms.dev:8899/test/doc-query?equipment_id='.$equipment_id, 'contains', '[machine_running] => 1')
                && $this->_helper->EquipmentHelper->checkCriteria('http://192.168.0.112', 'contains', 'html lang="en-US"')
                && $this->_helper->EquipmentHelper->checkCriteria('http://test.com', 'contains', '<div id="message">INNOVATIVE IT SOLUTIONS<br>');
        $this->view->results = ($results ? 'on' : 'off');
        $this->view->equipment_id = $equipment_id;
    }

    public function docQueryAction()
    {
        $equipment_id = $this->_request->getParam('equipment_id', 'ST-01,ST-02,ST-03,ST-04,ST-05,PST-01,PST-02,WA-01,WA-02,WA-03,WA-04,WA-05,WA-06,WA-07,WA-08,CW-01,CW-02');
        $equipment_status = $this->_helper->DocQueryHelper->getMachineStatus($equipment_id);

        $results = array();
        foreach($equipment_status as $status) {
            if($status['Batch_Status'] == 1){
                $results[]['machine_running'] = 1;
            }else{
                $results[]['machine_running'] = 0;
            }
            if($status['Process_Error'] == 1 || $status['Process_Native_Error'] == 1) {
                $results[]['error_occured'] = 1;
            }
        }

        $this->view->results = $results;
    }

}

