<?php

/**
 * AdamController.php
 * @author Jeff Peralta - jaydocperalta@gmail.com
 * @copyright Copyright (c) 2017.
 * Date: Tue, 19 Sep 2017
 * Time: 13:14:45 +0800
 *
 * LICENSE : this file is the exclusive property. No unauthorized copy or modification is allowed.
 *
 */
class AdamController extends Zend_Controller_Action
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
        $this->_helper->viewRenderer->setNoRender();
        //--Retrieve the seeddata--
        $url = $this->config['adam_device_url'];
        //$ch = curl_init($url);
		//curl_setopt($ch, CURLOPT_TIMEOUT, 1);
		//curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
		//curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$ch = curl_init();
		$options = array(
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING       => "",
            CURLOPT_AUTOREFERER    => true,
            CURLOPT_CONNECTTIMEOUT => 120,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_MAXREDIRS      => 10,
        );
        curl_setopt_array($ch, $options);
        curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		if($http_code >= "400" || $http_code == "0"){
		    $response = "CMS:OFF";
		}else{
		    $str_needle = '<input type="hidden" name="seeddata" value="';
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            $seeddata = substr($response, strpos($response, $str_needle) + strlen($str_needle), 8);

            //--Compute auth data--
            $username = $this->config['adam_device_username'];
            $password = $this->config['adam_device_password'];
            $authdata = md5($seeddata.":".$username.":".$password);

            //--Login to ADAM device--
            $ch = curl_init($url.'/login.cgi');
            curl_setopt($ch,CURLOPT_POST,1);
            curl_setopt($ch,CURLOPT_POSTFIELDS,'seeddata='.$seeddata.'&authdata='.$authdata);
            $response = curl_exec($ch);
		}

        echo $response;

    }
}