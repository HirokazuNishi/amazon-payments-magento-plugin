<?php
/**
 * Login with Amazon
 *
 * @category    Amazon
 * @package     Amazon_Payments
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/Apache-2.0  Apache License, Version 2.0
 */

class Amazon_Payments_Model_Login extends Mage_Core_Model_Abstract
{

    private $http_client_config = array(
            'maxredirects' => 2,
            'timeout' => 30);

    public function _construct()
    {
        parent::_construct();
        $this->_init('amazon_payments/login');
    }

    /**
     * Perform an API request to Amazon Login API
     *
     * @param string $path
     *    REST path e.g. user/profile
     * @param array $postParams
     *    POST paramaters
     * @return result
     */
    public function request($path, array $postParams = array())
    {
        $config = Mage::getSingleton('amazon_payments/config');

        $sandbox = $config->isSandbox() ? 'sandbox.' : '';

        switch (Mage::helper('amazon_payments')->getRegion()) {
            case 'uk':
                $tld = 'co.uk';
                break;
            case 'de':
                $tld = 'de';
                break;
            case 'jp':
                $tld = 'co.jp';
                break;
            default:
                $tld = 'com';
                break;
        }

        $client = new Zend_Http_Client();
        if(Mage::helper('amazon_payments')->getRegion() == 'jp') {
            if($sandbox) {
                $sandbox = '-'. $sandbox;
            } else {
                $sandbox = '.';
            }
            $client->setUri("https://api{$sandbox}amazon.$tld/$path");
        } else {
            $client->setUri("https://api.{$sandbox}amazon.$tld/$path");
        }
        $client->setConfig($this->http_client_config);
        $client->setMethod($postParams ? 'POST' : 'GET');

        foreach ($postParams as $key => $value) {
            $client->setParameterPost($key, $value);
        }

        try {
            $response = $client->request();
        } catch (Zend_Http_Client_Adapter_Exception $e) {
            Mage::logException($e);
            return;
        }

        $data = $response->getBody();

        try {
            $data = Zend_Json::decode($data, true);
        }
        catch (Exception $e) {
            Mage::logException($e);
        }

        if (empty($data)) {
            return false;
        }
        else {
            return $data;
        }
    }
}