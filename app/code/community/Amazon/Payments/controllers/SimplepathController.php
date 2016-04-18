<?php

/**
 * Amazon Payments
 *
 * @category    Amazon
 * @package     Amazon_Payments
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/Apache-2.0  Apache License, Version 2.0
 */

class Amazon_Payments_SimplepathController extends Mage_Core_Controller_Front_Action
{
    /**
     * Simplepath callback
     */
    public function indexAction()
    {
        $url = parse_url(Amazon_Payments_Model_SimplePath::API_ENDPOINT_DOWNLOAD_KEYS);

        header('Access-Control-Allow-Origin: https://' . $url['host']);
        header('Access-Control-Allow-Methods: GET, POST');
        header('Access-Control-Allow-Headers: Content-Type');

        $payloadJson = Mage::app()->getRequest()->getParam('payload');

        $this->getResponse()->setHeader('Content-type', 'application/json');

        try {
            if ($payloadJson) {
                $_simplePath = Mage::getModel('amazon_payments/simplePath');
                $json = $_simplePath->decryptPayload($payloadJson, false);

                if ($json) {
                    $this->getResponse()->setBody(Zend_Json::encode(array('result' => 'success')));
                }
            } else {
                $this->getResponse()->setHeader('HTTP/1.0','400',true);
                $this->getResponse()->setBody(Zend_Json::encode(array('result' => 'error', 'message' => 'payload parameter not found.')));
            }

        } catch (Exception $e) {
            $this->getResponse()->setHeader('HTTP/1.0','400',true);
            $this->getResponse()->setBody(Zend_Json::encode(array('result' => 'error', 'message' => $e->getMessage())));
        }
    }


}