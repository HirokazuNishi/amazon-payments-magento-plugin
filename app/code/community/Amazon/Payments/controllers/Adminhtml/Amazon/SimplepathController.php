<?php
/**
 * Amazon Payments SimplePath
 *
 * @category    Amazon
 * @package     Amazon_Payments
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/Apache-2.0  Apache License, Version 2.0
 */

class Amazon_Payments_Adminhtml_Amazon_SimplepathController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Return SimplePath URL with regenerated key-pair
     */
    public function spurlAction()
    {
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(Mage::getSingleton('amazon_payments/simplePath')->getSimplepathUrl());
    }

    /**
     * Detect whether Amazon credentials are set (polled by Ajax)
     */
    public function pollAction()
    {
        // Keypair is destroyed when credentials are saved
        $shouldRefresh = !Mage::getStoreConfig(Amazon_Payments_Model_SimplePath::CONFIG_XML_PATH_PUBLIC_KEY, 0) ? 1 : 0;

        if ($shouldRefresh) {
            Mage::getModel('amazon_payments/simplePath')->autoEnable();
        }

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody($shouldRefresh);
    }

    /**
     * Import config values via clipboard
     */
    public function importAction()
    {
        $response = array();

        $value = trim($this->getRequest()->getParam('json'));

        if ($value) {
            $value = str_replace('&quot;', '"', $value);
            $_simplePath = Mage::getModel('amazon_payments/simplePath');

            $json = $_simplePath->decryptPayload($value);

            if ($json === true) {
                Mage::getSingleton('adminhtml/session')->addSuccess("Login and Pay with Amazon credentials imported.");
            } else if ($json) {
                Mage::getSingleton('adminhtml/session')->addSuccess("Login and Pay with Amazon credentials imported.");
            }

            $response['success'] = true;
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
        }
    }

}
