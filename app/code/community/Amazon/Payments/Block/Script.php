<?php
/**
 * Amazon Payments
 *
 * @category    Amazon
 * @package     Amazon_Payments
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/Apache-2.0  Apache License, Version 2.0
 */

class Amazon_Payments_Block_Script extends Mage_Core_Block_Template
{
    /**
     * Disable loading Widgets.js for <default> (every page) if cart is empty
     */
    protected function _afterToHtml($html)
    {
        if ($this->getIsDefault() && Mage::helper('checkout/cart')->getItemsCount() == 0) {
            return;
        }
        else {
            return $html;
        }
    }

    /**
     * Return Widgets.js URL
     */
    public function getWidgetsUrl()
    {
        $_helper = $this->helper('amazon_payments');

        switch ($_helper->getConfig()->getRegion()) {
          case 'uk':
              $staticRegion = 'eu';
              $widgetRegion = 'uk';
              $lpa = 'lpa/';
              break;

          case 'de':
              $staticRegion = 'eu';
              $widgetRegion = 'de';
              $lpa = 'lpa/';
              break;
          case 'jp':
              $staticRegion = 'fe';
              $widgetRegion = 'jp';
              $lpa = 'lpa/';
              break;

          // US
          default:
              $staticRegion = 'na';
              $widgetRegion = 'us';
              $lpa = '';
              break;
        }

        $sandbox = $_helper->isAmazonSandbox() ? 'sandbox/' : '';
        if($widgetRegion=='jp' && $sandbox) {
            return "https://origin-na.ssl-images-amazon.com/images/G/09/EP/offAmazonPayments/{$sandbox}prod/{$lpa}js/Widgets.js?sellerId=" . $_helper->getSellerId();
        } else {
            return "https://static-$staticRegion.payments-amazon.com/OffAmazonPayments/$widgetRegion/{$sandbox}{$lpa}js/Widgets.js?sellerId=" . $_helper->getSellerId();
        }
    }

    /**
     * Return standalone URL
     */
    public function getStandaloneUrl()
    {
        return $this->helper('amazon_payments')->getStandaloneUrl();
    }

    /**
     * Return client ID
     */
    public function getClientId()
    {
        return $this->helper('amazon_payments')->getClientId();
    }

    /**
     * Show modal?
     */
    public function showModal()
    {
        return $this->helper('amazon_payments')->showModal();
    }

    /**
     * Is login pop-up enabled?
     */
    public function isPopup()
    {
        return Mage::helper('amazon_payments/data')->isPopup();
    }

    /**
     * Is enabled?
     */
    public function isEnabled()
    {
        return Mage::helper('amazon_payments/data')->isEnabled();
    }

    /**
     * Is loaded?
     */
    public function isLoaded()
    {
        if ($this->getIsLoaded()) {
            return true;
        }
        $this->setIsLoaded(true);
        return false;
    }


}
