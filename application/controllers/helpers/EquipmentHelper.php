<?php

/**
 * EquipmentHelper.php
 * @author Jeff Peralta - jaydocperalta@gmail.com
 * @copyright Copyright (c) 2017.
 * Date: Tue, 12 Sep 2017
 * Time: 12:52:38 +0800
 *
 * LICENSE : this file is the exclusive property. No unauthorized copy or modification is allowed.
 *
 */
class Zend_Controller_Action_Helper_EquipmentHelper extends Zend_Controller_Action_Helper_Abstract
{
    private $appdb;

    private $status_fields;

    private $config;

    public function init()
    {
        $this->appdb = Zend_Registry::get('appdb');
        $this->status_fields = array('trip_alarm', 'on_off_status', 'maintenance_due');
        $config_helper = new Zend_Controller_Action_Helper_ConfigHelper();
        $this->config = $config_helper->getConfig();
    }

    /**
     * Returns a list of equipment in array format
     * @return mixed
     */
    public function getList()
    {
        $select = $this->appdb->select()
            ->from(array('e' => 'equipment'))
            ->where('e.deleted_at IS NULL');
        $statement = $this->appdb->query($select);
        $results = $statement->fetchAll();
        return $results;
    }

    /**
     * Manually updates the equipment and log the action
     * @param $equipment_id
     * @param $params
     * @return bool
     */
    public function update($equipment_id, $params)
    {
        $today = date("Y-m-d H:i:s");
        $status_name = isset($params['status_name']) ? $params['status_name'] : '';
        $status = isset($params['status']) ? $params['status'] : '';
        $last_maintenance_by = isset($params['last_maintenance_by']) ? $params['last_maintenance_by'] : '';
        $updated_by = isset($params['updated_by']) ? $params['updated_by'] : '';

        $results = false;
        if (in_array($status_name, $this->status_fields)) {
            /**
             * When the maintenance due is ticked off compute other fields such as:
             * last_maintenance, next_maintenance, last_maintenance_by
             */
            if ($status_name == 'maintenance_due' && $status == 0 && $last_maintenance_by != '') {
                $select = $this->appdb->select()
                    ->from(array('e' => 'equipment'))
                    ->where('equipment_id = ?', $equipment_id);
                $statement = $this->appdb->query($select);
                $result = $statement->fetch();

                $next_maintenance = date("Y-m-d H:i:s", strtotime("$today + " . $result['maintenance_duration']));
                $update_fields = array(
                    'updated_at' => $today,
                    'updated_by' => $last_maintenance_by,
                    $status_name => $status,
                    'last_maintenance' => $today,
                    'next_maintenance' => $next_maintenance,
                    'last_maintenance_by' => $last_maintenance_by,
                );

            } else {
                /**
                 * Update of equipment status only
                 */
                $update_fields = array(
                    'updated_at' => $today,
                    'updated_by' => $updated_by,
                    $status_name => $status
                );
            }
        } else {
            /**
             * Other field updates
             */
            $update_fields = $params;
        }

        if (count($update_fields) > 0) {
            /**
             * Update the equipment's status
             */
            $equipmentTable = new Zend_Db_Table('equipment');
            if (is_numeric($equipment_id)) {
                $where = $equipmentTable->getAdapter()->quoteInto('id = ?', $equipment_id);
            } else {
                $where = $equipmentTable->getAdapter()->quoteInto('equipment_id = ?', $equipment_id);
            }

            $equipmentTable->update($update_fields, $where);

            /**
             * Create a log history
             */
            $this->logHistory($equipment_id, ($last_maintenance_by != "" ? "maintenance" : "update"), $update_fields);
            $results = true;
        }

        return $results;
    }

    /**
     * Create a new equipment and log the action
     * @param $params
     * @return mixed
     */
    public function create($params)
    {
        $equipmentTable = new Zend_Db_Table('equipment');
        $equipment_id = $equipmentTable->insert($params);
        $this->logHistory($equipment_id, $type = "create", $params);
        return $equipment_id;
    }

    /**
     * Soft deletes an equipment
     * @param $id
     * @return bool
     */
    public function delete($id)
    {
        $params['deleted_at'] = date("Y-m-d H:i:s");
        $equipmentTable = new Zend_Db_Table('equipment');
        if (is_numeric($id)) {
            $where = $equipmentTable->getAdapter()->quoteInto('id = ?', $id);
        } else {
            $where = $equipmentTable->getAdapter()->quoteInto('equipment_id = ?', $id);
        }
        $equipmentTable->update($params, $where);
        $this->logHistory($id, $type = "delete", $params);
        return true;
    }

    /**
     * Returns the equipment information in array format
     * @param string $equipment_id when empty return full list of equipment
     * @return mixed
     */
    public function getEquipment($equipment_id = "")
    {
        $select = $this->appdb->select()
            ->from(array('e' => 'equipment'))
            ->where('e.deleted_at IS NULL');

        if ($equipment_id != "") {
            if (is_numeric($equipment_id)) {
                $select->where('e.id = ?', $equipment_id);
            } else {
                $select->where('e.equipment_id = ?', $equipment_id);
            }
        }

        $statement = $this->appdb->query($select);
        if ($equipment_id != "") {
            $result = $statement->fetch();
        } else {
            $result = $statement->fetchAll();
        }

        return $result;
    }

    /**
     * Returns the equipment's list of criteria in array format
     * @param string $equipment_id
     * @param string $alarm_type
     * @return mixed
     */
    public function getCriteriaList($equipment_id = "", $alarm_type = "")
    {
        $select = $this->appdb->select()
            ->from(array('e' => 'equipment'), array())
            ->joinRight(array('sc' => 'status_criteria'), 'e.id = sc.equipment_id')
            ->where('sc.deleted_at IS NULL')
            ->order('sc.alarm_type ASC');
        if (is_numeric($equipment_id)) {
            $select->where('e.id = ?', $equipment_id);
        } else {
            $select->where('e.equipment_id = ?', $equipment_id);
        }

        if ($alarm_type != "") {
            $select->where('sc.alarm_type = ?', $alarm_type);
        }

        $statement = $this->appdb->query($select);
        $results = $statement->fetchAll();

        return $results;
    }

    /**
     * Creates a new status criteria
     * @param $params
     * @return mixed the id of the newly created status criteria
     */
    public function createStatusCriteria($params)
    {
        $statusCriteriaTable = new Zend_Db_Table('status_criteria');
        $status_criteria_id = $statusCriteriaTable->insert($params);
        $this->logHistory($params['equipment_id'], $type = "create-criteria", $params);
        return $status_criteria_id;
    }

    /**
     * Updates the status criteria
     * @param $id
     * @param $params
     * @return bool true when success
     */
    public function updateStatusCriteria($id, $params)
    {
        $statusCriteriaTable = new Zend_Db_Table('status_criteria');
        $where = $statusCriteriaTable->getAdapter()->quoteInto('id = ?', $id);
        $statusCriteriaTable->update($params, $where);

        if (!isset($params['equipment_id'])) {
            $select = $this->appdb->select()
                ->from(array('sc' => 'status_criteria'))
                ->where('sc.id = ?', $id);
            $statement = $this->appdb->query($select);
            $result = $statement->fetch();
            $params['equipment_id'] = $result['equipment_id'];
        }

        $this->logHistory($params['equipment_id'], $type = "update-criteria", $params);
        return true;
    }

    /**
     * Soft deletes a status criteria
     * @param $id
     * @return bool
     */
    public function deleteStatusCriteria($id)
    {
        $params['deleted_at'] = date("Y-m-d H:i:s");
        $statusCriteriaTable = new Zend_Db_Table('status_criteria');
        $where = $statusCriteriaTable->getAdapter()->quoteInto('id = ?', $id);
        $statusCriteriaTable->update($params, $where);

        if (!isset($params['equipment_id'])) {
            $select = $this->appdb->select()
                ->from(array('sc' => 'status_criteria'))
                ->where('sc.id = ?', $id);
            $statement = $this->appdb->query($select);
            $result = $statement->fetch();
            $params['equipment_id'] = $result['equipment_id'];
        }

        $this->logHistory($params['equipment_id'], $type = "delete-criteria", $params);
        return true;
    }

    /**
     * Returns the equipment status criteria information in array format
     * @param $id
     * @return mixed
     */
    public function getStatusCriteria($id)
    {
        $select = $this->appdb->select()
            ->from(array('sc' => 'status_criteria'))
            ->where('sc.deleted_at IS NULL')
            ->where('sc.id = ?', $id);
        $statement = $this->appdb->query($select);
        $results = $statement->fetch();
        return $results;
    }


    /**
     * Checks if there is a need to change the current status of an equipment.
     * @param $equipment_id the equipment to check status
     * @param string $alarm_type can be use to check only specific alarm type
     * @return mixed an array containing
     * 1. the alarm_type(s) of an equipment whether turned on/off
     * 2. the list of criteria and results
     */
    public function checkStatus($equipment_id, $alarm_type = "all")
    {
        /**
         * Retrieve the status criteria for each equipment
         * If the alarm_type is specified, retrieve only those criteria that belong to that type.
         */
        $select = $this->appdb->select()
            ->from(array('e' => 'equipment'))
            ->joinRight(array('c' => 'status_criteria'), 'e.id = c.equipment_id', array('status_criteria_id' => 'c.id', 'alarm_type', 'operand', 'operator', 'value'))
            ->where('e.equipment_id = ?', $equipment_id)
            ->where('e.deleted_at IS NULL')
            ->where('c.deleted_at IS NULL');
        if (strtolower($alarm_type) != 'all') {
            $select->where('c.alarm_type = ?', $alarm_type);
        }
        $statement = $this->appdb->query($select);
        $status_criteria_results = $statement->fetchAll();

        $trace = array();
        foreach ($status_criteria_results as $status_criteria) {
            $operand = $status_criteria['operand'];

            /**
             * Replace place holders in the operand with info from the equipment.
             */
            $operand = str_replace(':equipment_id', $status_criteria['equipment_id'], $operand);
            $operand = str_replace(':next_maintenance', $status_criteria['next_maintenance'], $operand);

            /**
             * Compute if there is an equipment alarm that needs to be turned on/off
             */
            $run_date = date('Y-m-d H:i:s');
            $criteria_results = $this->checkCriteria($operand, $status_criteria['operator'], $status_criteria['value']);
            if (isset($results[$status_criteria['alarm_type']])) {
                $results[$status_criteria['alarm_type']] = (($results[$status_criteria['alarm_type']] && $criteria_results) ? 1 : 0);
            } else {
                $results[$status_criteria['alarm_type']] = (($criteria_results) ? 1 : 0);
            }

            /**
             * Trace the equipment status and the criteria that made the changes
             */
            $trace[$status_criteria['alarm_type']][$status_criteria['status_criteria_id']] = array(
                'run_date' => $run_date,
                'operand' => $operand,
                'operator' => $status_criteria['operator'],
                'value' => $status_criteria['value'],
                'results' => (($criteria_results) ? 1 : 0)
            );
        }

        /**
         * Return the trace for further action :
         * 1. actual update of the equipment table
         * 2. log history
         */
        $results['status_criteria'] = $trace;
        return $results;
    }

    /**
     * Update the equipment status based from the recommendation of the checkStatus() method
     * @param $equipment_id the equipment to update status
     * @param string $alarm_type can be use to update only specific alarm type
     * @return mixed refer to the return value of the checkStatus()
     */
    public function updateStatus($equipment_id, $alarm_type = "all")
    {
        $status_fields_to_update = $this->status_fields; //--See init() for full list of status_field.--

        /**
         * Get the current status of each equipment alarm
         */
        $status_results = $this->checkStatus($equipment_id, $alarm_type);

        /**
         * Prepare the update sql statement
         */
        $update_values = array();
        foreach ($status_fields_to_update as $status_name) {
            if (isset($status_results[$status_name])) {
                $update_values[$status_name] = $status_results[$status_name];
            }
        }

        if (count($update_values) > 0) {
            $update_values['updated_at'] = date("Y-m-d H:i:s");

            /**
             * Get the equipment status before the update
             */
            $prev_equipment_info = $this->getEquipment($equipment_id);

            /**
             * Update the equipment table for each status_field.
             * See init() for full list of status_field.
             */
            $equipmentTable = new Zend_Db_Table('equipment');
            $where = $equipmentTable->getAdapter()->quoteInto('equipment_id = ?', $equipment_id);
            $equipmentTable->update($update_values, $where);

            /**
             * Create a log history only when there are changes in the status.
             * Payload is the results from the checkStatus() method
             */
            $with_changes = false;
            foreach ($this->status_fields as $status_field) {
                if (isset($update_values[$status_field]) && $update_values[$status_field] != $prev_equipment_info[$status_field]) {
                    $with_changes = true;
                    break;
                }
            }

            if ($with_changes) $this->logHistory($equipment_id, "status", $status_results);
        }

        return $status_results;
    }

    /**
     * Updates the status of all equipment and returns the same result as the checkStatus()
     * @return array refer to the return value of the checkStatus()
     * On this case, the equipment_id is the base index
     */
    public function updateAllStatus()
    {
        /**
         * Get all equipment
         */
        $equipment_results = $this->getEquipment();
        $results = array();

        /**
         * For each equipment, update its status and prepare the results with equipment_id as the base index
         */
        foreach ($equipment_results as $equipment) {
            $results[$equipment['equipment_id']] = $this->updateStatus($equipment['equipment_id'], 'all');
        }

        return $results;
    }

    /**
     * Defines how the operator in the status_criteria dbtable behaves
     * @param $operand
     * @param $operator
     * @param $value
     * @return bool
     */
    public function checkCriteria($operand, $operator, $value)
    {
        $results = false;
        switch (strtolower($operator)) {
            case '=':
                $results = ($operand == $value);
                break;

            case '!=':
                $results = ($operand != $value);
                break;

            case 'contains':
                if ($this->isNetcomUrl($operand)) {
                    $html = $this->getNetcomResultPage($operand);
                } else {
                    $operand = $this->toProperLocalUrl($operand);
                    $html = (trim($operand) != "" ? file_get_contents($operand) : "");
                }
                $results = (strpos($html, $value) !== false);
                break;

            case '!contains':
                if ($this->isNetcomUrl($operand)) {
                    $html = $this->getNetcomResultPage($operand);
                } else {
                    $operand = $this->toProperLocalUrl($operand);
                    $html = (trim($operand) != "" ? file_get_contents($operand) : "");
                }
                $results = (strpos($html, $value) === false);
                break;

            case 'duewithin':
                $today = date('Y-m-d');
                $due_date = date('Y-m-d', strtotime($operand . "- $value"));
                $results = ($today >= $due_date);
                break;
        }

        return $results;
    }

    /**
     * Determines if the url points to a netcom
     * @param $url
     * @return bool
     */
    private function isNetcomUrl($url)
    {
        return (strpos($url, 'cgi-bin') !== false);
    }

    /**
     * Returns the source of the netcom device
     * @param $url
     * @return mixed
     */
    private function getNetcomResultPage($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_USERPWD, $this->config['netcom_device_username'] . ":" . $this->config['netcom_device_password']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        return $response;
    }

    /**
     * Checks if local URL
     * @param $url
     * @return bool
     */
    private function isLocalUrl($url)
    {
        return ($url[0] == '/');
    }

    private function toProperLocalUrl($url)
    {
        $results = $url;
        if($this->isLocalUrl($url)) {
            $http_host = (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'ibms.dev:8899');
            $request_scheme = (isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http');
            $results = "$request_scheme://$http_host$url";
        }
        return $results;
    }


    /**
     * Logs the update done in the equipments table
     * @param $equipment_id equipment that was modified
     * @param string $type of modification done
     * @param array $results the payload saved as JSON format in the DB
     * @return int history id
     */
    public function logHistory($equipment_id, $type = "status", $results = array())
    {
        try{
            if(!is_numeric($equipment_id)) {
                $select = $this->appdb->select()->from('equipment')->where('equipment_id = ?', $equipment_id);
                $statement = $this->appdb->query($select);
                $equipment = $statement->fetch();
            }else{
                $equipment['id'] = $equipment_id;
            }

            $params = array(
                  'created_at' => date("Y-m-d H:i:s") //--Now--
                , 'equipment_id' => $equipment['id']
                , 'type' => $type
                , 'results' => Zend_Json::encode($results)
            );
            $historyTable = new Zend_Db_Table('history');
            $history_id = $historyTable->insert($params);
        }catch(Exception $e) {
            $history_id = 0;
        }

        return $history_id;
    }

    /**
     * Returns a list of equipment changes done within a given period in array format
     * @param $equipment_id
     * @param string $start_date
     * @param string $end_date
     * @return mixed
     */
    public function getHistoryList($equipment_id, $start_date = "", $end_date = "")
    {
        $start_date = (trim($start_date) == '' ? date('Y-m-d') : $start_date) . ' 00:00:00';
        $end_date = (trim($end_date) == '' ? date('Y-m-d') : $end_date) . ' 23:59:59';

        $select = $this->appdb->select()
                    ->from(array('h' => 'history'))
                    ->joinLeft(array('e' => 'equipment'), 'h.equipment_id = e.id', array('e.equipment_id', 'e.equipment_desc'));
        if($equipment_id != "") {
            if(!is_numeric($equipment_id)) {
                $select->where('e.equipment_id = ?', $equipment_id);
            }else{
                $select->where('h.equipment_id = ?', $equipment_id);
            }
        }

        $select->where('h.created_at >= ?', $start_date)
               ->where('h.created_at <= ?', $end_date);
        $select->order(array('h.created_at DESC'));

        $statement = $this->appdb->query($select);
        $results = $statement->fetchAll();

        return $results;
    }

    /**
     * Checks the Nagios status.dat file to determine if the equipment is ON/OFF
     * @param $equipment_id
     * @return bool
     */
    public function getNagiosStatus($equipment_id)
    {
        $results = false;
        if(trim($equipment_id) != '' && trim($this->config['nagios_dat_file_location']) != ''){
            $nagios_file = fopen($this->config['nagios_dat_file_location'], "r") or die("Unable to open file!");
            $contents = fread($nagios_file,filesize($this->config['nagios_dat_file_location']));
            fclose($nagios_file);

            if(trim($contents) != ""){
                $contents = str_replace("hoststatus","", $contents);
                $contents = str_replace("}","{", $contents);
                $contents = explode("{", $contents);

                foreach($contents as $equipment_text){
                    if(trim($equipment_text) != ""
                        && strpos($equipment_text, "host_name=$equipment_id") !== false
                        && strpos($equipment_text, "plugin_output=PING OK") !== false) {
                        $results = true;
                        break;
                    }
                }
            }
        }

        return $results;
    }

}