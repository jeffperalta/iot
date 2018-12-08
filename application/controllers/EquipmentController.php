<?php

/**
 * EquipmentController.php
 * @author Jeff Peralta - jaydocperalta@gmail.com
 * @copyright Copyright (c) 2017.
 * Date: Tue, 12 Sep 2017
 * Time: 12:52:54 +0800
 *
 * LICENSE : this file is the exclusive property. No unauthorized copy or modification is allowed.
 *
 */
class EquipmentController extends Zend_Controller_Action
{
    private $config;

    public function init()
    {
        /* Initialize action controller here */
        $config = $this->_helper->ConfigHelper->getConfig();
        $this->config = $config;
        foreach($config as $config_name => $config_value){
            $this->view->$config_name = $config_value;
        }
    }

    public function indexAction()
    {
        // action body
    }

    public function updateEquipmentAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
        $equipment_id = $this->_request->getParam('equipment_id', '');
        $results = $this->_helper->EquipmentHelper->updateStatus($equipment_id);
        print_r(Zend_Json::encode($results));
    }

    public function updateAllAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
        $results = $this->_helper->EquipmentHelper->updateAllStatus();
        print_r(Zend_Json::encode($results));
    }

    public function historyAction()
    {
        $equipment_id = $this->_request->getParam('id', '');
        $start_date = $this->_request->getParam('start_date', date("Y-m-d"));
        $end_date = $this->_request->getParam('end_date', date("Y-m-d"));
        if(trim($start_date) == "") $start_date = date("Y-m-d");
        if(trim($end_date) == "") $end_date = date("Y-m-d");
        $this->view->equipment_id = $equipment_id;
        $this->view->history = $this->_helper->EquipmentHelper->getHistoryList($equipment_id, $start_date, $end_date);
        $this->view->start_date = $start_date;
        $this->view->end_date = $end_date;
    }

    public function checkAction(){
        $this->_helper->viewRenderer->setNoRender();
        $equipment_id = $this->_request->getParam('equipment_id', 'ST-01');
        $equipment_type = $this->_request->getParam('equipment_type', '');
        $equipment_info = $this->_helper->EquipmentHelper->getEquipment($equipment_id);

        if($equipment_type == '') {
            $temp = explode("-",$equipment_id);
            $equipment_type = isset($temp[0]) ? $temp[0] : '';
        }

        $doc_status = $this->_helper->DocQueryHelper->getMachineStatus($equipment_id);
        $doc_status = isset($doc_status[0]) ? $doc_status[0] : array();
        $exclude_proc_error = array('', '0', '100');

        $result_trip_alarm = false;
        if(isset($doc_status['Process_Error'])) {
            if(!in_array(trim($doc_status['Process_Error']), $exclude_proc_error)){
                $result_trip_alarm = true;
            }
        }else{
            $result_trip_alarm = true;
        }

        $result_on_off = false;
        switch(strtolower($this->config['on_off_status_source'])){
            case 'netcom':
                $ip_address = $equipment_info['ip_address'];
                if(trim($ip_address) != ''){
                    $result_on_off = $status = $this->_helper->EquipmentHelper->checkCriteria('http://'+$ip_address+'/cgi-bin/oper/devstat/pacs2000dstat.cgi', 'contains', 'Status: Active<br>');
                }
            break;

            case 'nagios':
                $result_on_off = $status = $this->_helper->EquipmentHelper->getNagiosStatus($equipment_id);
            break;

            default:
                $result_on_off = false;
        }

        echo $result_trip_alarm ?   "trip_alarm=FAILED;"    : "trip_alarm=OK;";
        echo $result_on_off ?       "on_off=OK;"            : "on_off=FAILED;";
    }

}