<?php
/**
 * Validate Client ID and Client Secret
 *
 * @category    Amazon
 * @package     Amazon_Payments
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/Apache-2.0  Apache License, Version 2.0
 */

class Amazon_Payments_Model_System_Config_Backend_Enabled extends Mage_Core_Model_Config_Data
{
    /**
     * Validate data
     */
    public function save()
    {

        $data = $this->_getCredentials();
        $isEnabled = $this->getValue();

        if ($isEnabled) {
            if ($data['seller_id']['value'] && !ctype_alnum($data['seller_id']['value'])) {
                Mage::getSingleton('core/session')->addError(Mage::helper('adminhtml')->__('Error: Please verify your Seller ID (alphanumeric characters only).'));
            }
        }
        return parent::save();
    }

    /**
     * Return dynamic help/comment text
     */
    public function getCommentText(Mage_Core_Model_Config_Element $element, $currentValue)
    {
        $version = Mage::getConfig()->getModuleConfig("Amazon_Payments")->version;

        // @see Amazon_Payments_Model_SimplePath->saveToConfig()
        $enabledMessage = Mage::getSingleton('adminhtml/session')->getEnableMessage();
        if ($enabledMessage) {
            $enabledMessage = '<div style="color:red">' . $enabledMessage . '</div>';
            Mage::getSingleton('adminhtml/session')->unsEnableMessage();
        }

        // SimplePath
        return "v$version

        $enabledMessage

        <!-- SimplePath -->
        <script>
          var AmazonSp = " . Zend_Json::encode(Mage::getSingleton('amazon_payments/simplePath')->getJsonAmazonSpConfig()) . ";
        </script>
        ";
    }

    /**
     * Return credentials
     */
    private function _getCredentials()
    {
        $groups = $this->getData('groups');
        return $groups['ap_credentials']['fields'];
    }

    /**
     * Return Widgets.js URL
     */
    public function getMwsSellerApiUrl()
    {
        $region = Mage::getStoreConfig('amazon_login/settings/region');
        switch ($region) {
          case 'uk':
              $tld = 'co.uk';
              break;

          case 'de':
              $tld = 'de';
              break;

          case 'jp':
              $tld = 'jp';
              break;

          // US
          default:
              $tld = 'com';
              break;
        }

        return "https://mws.amazonservices.".$tld."/Sellers/2011-07-01";
    }

}
