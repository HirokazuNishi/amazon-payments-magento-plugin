<?php
/**
 * Amazon Payments Checkout Controller
 *
 * @category    Amazon
 * @package     Amazon_Payments
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/Apache-2.0  Apache License, Version 2.0
 */

abstract class Amazon_Payments_Controller_Checkout extends Mage_Checkout_Controller_Action
{
    protected $_amazonOrderReferenceId;
    protected $_checkoutUrl;

    /**
     * Return Amazon Order Reference Id
     */
    public function getAmazonOrderReferenceId()
    {
        return $this->_amazonOrderReferenceId;
    }

    /**
     * Check query string paramters for order reference and/or access token
     */
    public function preDispatch()
    {
        parent::preDispatch();

        // User clicked "Cancel" on Amazon Login consent form
        if ($this->getRequest()->getParam('error') == 'access_denied') {
            $this->_redirect('checkout/cart');
            return;
        }

        $this->_amazonOrderReferenceId = htmlentities($this->getRequest()->getParam('amazon_order_reference_id'));

        if (!$this->_amazonOrderReferenceId) {
            $this->_amazonOrderReferenceId = Mage::getSingleton('checkout/session')->getAmazonOrderReferenceId();
        }
        else {
            Mage::getSingleton('checkout/session')->setAmazonOrderReferenceId($this->_amazonOrderReferenceId);
        }

        // User is logging in...

        $token = htmlentities($this->getRequest()->getParam('access_token'));

        if ($token) {
            $_amazonLogin = Mage::getModel('amazon_payments/customer');

            if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
                if (!$this->_getConfig()->isGuestCheckout() || !$this->_getOnepage()->getQuote()->isAllowedGuestCheckout()) {
                    $customer = $_amazonLogin->loginWithToken($token, $this->_checkoutUrl);
                }
                // Guest
                else {
                    $amazonProfile = $_amazonLogin->getAmazonProfile($token);
                    if ($amazonProfile && isset($amazonProfile['email'])) {
                        Mage::getSingleton('checkout/session')->setCustomerEmail($amazonProfile['email']);
                        Mage::getSingleton('checkout/session')->setCustomerName($amazonProfile['name']);
                    }
                }
            }

            Mage::getSingleton('checkout/session')->setAmazonAccessToken($token);

            // Full-page redirect (user did not sign in using popup)
            if ($this->getRequest()->getParam('nopopup')) {
                $this->_redirectUrl(Mage::helper('amazon_payments')->getCheckoutUrl(false) . '#access_token=' . $token);
            }
            // Redirect to account page
            else if (Mage::app()->getRequest()->getParams('account') == 'redirect') {
                $this->_redirect('customer/account');
            }
            // Redirect to clean URL
            else if (!$this->getRequest()->getParam('ajax')) {
                $this->_redirect($this->_checkoutUrl, array('_secure' => true));
                return;
            }


        }

    }

    /**
     * Clear Amazon session data
     */
    public function clearSession()
    {
        Mage::helper('amazon_payments/data')->clearSession();
    }

    /**
     * Redirect to account
     */
    public function accountAction()
    {
        // User logged in at order details page and wants to update payment method from async decline
        if ($orderIdRedirect = Mage::getModel('core/cookie')->get('amazonOrderIdRedirect')) {
            Mage::getModel('core/cookie')->delete('amazonOrderIdRedirect');
            $this->_redirect('sales/order/view/order_id/' . $orderIdRedirect);
        } else {
            $this->_redirect('customer/account');
        }
    }

    /**
     * Validate ajax request and redirect on failure
     *
     * @return bool
     */
    protected function _expireAjax()
    {
        if (!$this->_getCheckout()->getQuote()->hasItems()
            || $this->_getCheckout()->getQuote()->getHasError()
            || $this->_getCheckout()->getQuote()->getIsMultiShipping()
        ) {
            $this->_ajaxRedirectResponse();
            return true;
        }
        $action = $this->getRequest()->getActionName();
        if (Mage::getSingleton('checkout/session')->getCartWasUpdated(true)
            && !in_array($action, array('index'))
        ) {
            $this->_ajaxRedirectResponse();
            return true;
        }
        return false;
    }

    /**
     * Get Order by quoteId
     *
     * @throws Mage_Payment_Model_Info_Exception
     * @return Mage_Sales_Model_Order
     */
    protected function _getOrder()
    {
        if (is_null($this->_order)) {
            $this->_order = Mage::getModel('sales/order')->load($this->_getCheckout()->getQuote()->getId(), 'quote_id');
            if (!$this->_order->getId()) {
                throw new Mage_Payment_Model_Info_Exception(Mage::helper('core')->__("Can not create invoice. Order was not found."));
            }
        }
        return $this->_order;
    }


    /**
     * Get Amazon API
     *
     * @return Amazon_Payments_Model_Api
     */
    protected function _getApi() {
        return Mage::getModel('amazon_payments/api');
    }

    /**
     * Get Payments config
     */
    protected function _getConfig() {
        return Mage::getModel('amazon_payments/config');
    }


    /**
     * Send Ajax redirect response
     *
     * @return Amazon_Payments_CheckoutController
     */
    protected function _ajaxRedirectResponse()
    {
        $this->getResponse()
            ->setHeader('HTTP/1.1', '403 Session Expired')
            ->setHeader('Login-Required', 'true')
            ->sendResponse();
        return $this;
    }

    /**
     * Get checkout model
     *
     * @return Amazon_Payments_Model_Type_Checkout
     */
    protected function _getCheckout()
    {
        return Mage::getSingleton('amazon_payments/type_checkout');
    }

    /**
     * Get onepage model
     *
     * @return Amazon_Payments_Model_Type_Checkout
     */
    protected function _getOnepage()
    {
        return Mage::getSingleton('checkout/type_onepage');
    }


    /**
     * Save shipping address based on Amazon Order Reference (e.g. address book click)
     */
    protected function _saveShipping()
    {
        if ($this->getAmazonOrderReferenceId()) {

            $orderReferenceDetails = $this->_getApi()->getOrderReferenceDetails($this->getAmazonOrderReferenceId(), Mage::getSingleton('checkout/session')->getAmazonAccessToken());

            $address = $orderReferenceDetails->getDestination()->getPhysicalDestination();

            // Split name into first/last

            // Find Mage state/region ID
            $regionModel = Mage::getModel('directory/region')->loadByCode($address->getStateOrRegion(), $address->getCountryCode());
            $regionId    = $regionModel->getId();

            // Load region ID by name
            if (!$regionId) {
                $regionModel = Mage::getModel('directory/region')->loadByName($address->getStateOrRegion(), $address->getCountryCode());
                $regionId    = $regionModel->getId();
            }

            $data = Mage::helper('amazon_payments')->transformAmazonAddressToMagentoAddress($address);
            $data['use_for_shipping'] = true;
            $data['region'] = $address->getStateOrRegion();
            $data['region_id'] = $regionId;

            if ($email = Mage::getSingleton('checkout/session')->getCustomerEmail()) {
                $data['email'] = $email;
            }

            // Set billing address (if allowed by scope)
            if ($orderReferenceDetails->getBillingAddress()) {
                $billing = $orderReferenceDetails->getBillingAddress()->getPhysicalAddress();

                $regionModel = Mage::getModel('directory/region')->loadByCode($billing->getStateOrRegion(), $billing->getCountryCode());
                $regionId    = $regionModel->getId();
                $dataBilling = Mage::helper('amazon_payments')->transformAmazonAddressToMagentoAddress($billing);
                $dataBilling['use_for_shipping'] = false;
                $dataBilling['region'] = $billing->getStateOrRegion();
                $dataBilling['region_id'] = $regionId;

                $this->_getCheckout()->saveBilling($dataBilling, null);

            }
            else {
                $this->_getCheckout()->saveBilling($data, null);
            }

            return $this->_getCheckout()->saveShipping($data);
        }
    }
}

