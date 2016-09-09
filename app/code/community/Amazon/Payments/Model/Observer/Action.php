<?php
/**
 * Amazon Payments
 *
 * @category    Amazon
 * @package     Amazon_Payments
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/Apache-2.0  Apache License, Version 2.0
 */

class Amazon_Payments_Model_Observer_Action
{
    /**
     * Redirect to secure cart? (required for Amazon button)
     */
    private function _shouldRedirectCart()
    {
        return (Mage::getSingleton('amazon_payments/config')->isEnabled()
            && Mage::getSingleton('amazon_payments/config')->isSecureCart()
            && strpos(Mage::getStoreConfig('web/secure/base_url'), 'https') !== false);
    }

    /**
     * Event: controller_action_predispatch_checkout_cart_index
     *
     * Redirect to HTTPS cart page
     */
    public function secureCart(Varien_Event_Observer $observer)
    {
        if ($this->_shouldRedirectCart() && !Mage::app()->getStore()->isCurrentlySecure()) {
            $redirectUrl = Mage::getUrl('checkout/cart/', array('_forced_secure' => true));
            Mage::app()->getResponse()
                ->setRedirect($redirectUrl)
                ->sendResponse();
            exit;
        }
    }

    /**
     * Event: controller_response_redirect
     *
     * Redirect to HTTPS cart page
     */
    public function responseRedirect(Varien_Event_Observer $observer)
    {
        $event = $observer->getEvent();
        if ($this->_shouldRedirectCart() && $event->getTransport() && $event->getTransport()->getUrl() == Mage::getUrl('checkout/cart/')) {
            $observer->getEvent()->getTransport()->setUrl(Mage::getUrl('checkout/cart/', array('_forced_secure' => true)));
        }
    }
}
