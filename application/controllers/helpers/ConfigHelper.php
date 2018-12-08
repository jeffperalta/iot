<?php
/**
 * ConfigHelper.php
 * @author Jeff Peralta - jaydocperalta@gmail.com
 * @copyright Copyright (c) 2017.
 * Date: Fri, 15 Sep 2017
 * Time: 14:50:41 +0800
 *
 * LICENSE : this file is the exclusive property. No unauthorized copy or modification is allowed.
 *
 */

class Zend_Controller_Action_Helper_ConfigHelper extends Zend_Controller_Action_Helper_Abstract
{
    private $appdb;

    public function init()
    {
        $this->appdb = Zend_Registry::get('appdb');
    }

    public function getConfig()
    {
        $this->appdb = Zend_Registry::get('appdb');
        $select = $this->appdb->select()
                        ->from(array('c' => 'config'))
                        ->where('c.deleted_at IS NULL');
        $statement = $this->appdb->query($select);
        $results = $statement->fetchAll();

        $config = array();
        $json_configs = array('status_list','status_criteria_operator');

        foreach($results as $r){
            if(in_array($r['name'], $json_configs) && $this->is_JSON(strval($r['value']))) {
                $config[$r['name']] = Zend_Json::decode(strval($r['value']));
            }else{
                $config[$r['name']] = strval($r['value']);
            }
        }

        $config['js_status_list'] = "";
        if(isset($config['status_list'])) {
            foreach($config['status_list'] as $status_name => $status) {
                $js_image = "";
                foreach($status['img'] as $status_value => $img) {
                    if($js_image != '') $js_image .= ",";
                    $js_image .= "$status_value:'$img'";
                }
                if($js_image != "") {
                    if($config['js_status_list'] != '') $config['js_status_list'] .= ",";
                    $config['js_status_list'] .= ''.$status_name.' : {'.$js_image.'}';
                }
            }
        }
        return $config;
    }

    private function is_JSON() {
        call_user_func_array('json_decode',func_get_args());
        return (json_last_error()===JSON_ERROR_NONE);
    }

}