<?php
/**
 * Amazon Payments
 *
 * @category    Amazon
 * @package     Amazon_Payments
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/Apache-2.0  Apache License, Version 2.0
 */

class Amazon_Payments_Block_Checkout extends Mage_Core_Block_Template
{

    /**
     * Return seller ID
     */
    public function getSellerId()
    {
        return $this->helper('amazon_payments')->getSellerId();
    }

    /**
     * Getter
     *
     * @return float
     */
    public function getQuoteBaseGrandTotal()
    {
        return (float)Mage::getSingleton('checkout/session')->getQuote()->getBaseGrandTotal();
    }

    /**
     * Is debug mode?
     */
    public function isDebugMode()
    {
        return Mage::getSingleton('amazon_payments/config')->isDebugMode();
    }

    /**
     * Is tokenized payments enabled?
     */
    public function isTokenEnabled()
    {
        return Mage::getSingleton('amazon_payments/config')->isTokenEnabled();
    }

    /**
     * Is tokenized payments required?
     */
    public function isTokenRequired()
    {
        return $this->isTokenEnabled() && Mage::getSingleton('amazon_payments/config')->isTokenRequired();
    }

    /**
     * Show coupon/discount code?
     */
    public function isShowCoupon()
    {
        return Mage::getSingleton('amazon_payments/config')->isShowCoupon();
    }

}
