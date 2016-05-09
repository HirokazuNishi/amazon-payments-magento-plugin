<?php
/**
 * Amazon payments
 *
 * @category    Amazon
 * @package     Amazon_Payments
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/Apache-2.0  Apache License, Version 2.0
 */

class Amazon_Payments_Model_System_Config_Backend_Popupcomment extends Mage_Core_Model_Config_Data
{
    /**
     * Return dynamic help/comment text
     *
     */
    public function getCommentText(Mage_Core_Model_Config_Element $element, $currentValue)
    {

        $replace_cleanup = array(':80', ':443');

        if (function_exists('apache_get_modules') && in_array('mod_rewrite', apache_get_modules())) {
            $replace_cleanup[] = 'index.php/';
        }

        // Website level
        if (strlen($code = Mage::getSingleton('adminhtml/config_data')->getWebsite())) {
            $website_id = Mage::getModel('core/website')->load($code)->getId();
            $store_id = Mage::app()->getWebsite($website_id)->getDefaultStore()->getId();
        } else {
            $store_id = 1;
        }

        return '
        <div style="border:1px solid #ccc; color:#666; padding:8px; margin-top:0.5em; font-size:90%">
        If "No," add these URLs in Seller Central under "Allowed Return URLs":<br />
        <ul style="list-style:disc inside">
        <li>' . str_replace($replace_cleanup, '', Mage::getUrl('amazon_payments/customer/authorize', array('_store' => Mage::helper('amazon_payments')->getAdminStoreId(), '_forced_secure' => true))) . '</li>
        <li>' . str_replace($replace_cleanup, '', Mage::getUrl('amazon_payments/checkout/authorize', array('_store' => Mage::helper('amazon_payments')->getAdminStoreId(), '_forced_secure' => true))) . '</li>
        </ul>
        </div>
        <style>#payment_amazon_payments_popupcomment{display:none;}</style>


        ';
    }
}
