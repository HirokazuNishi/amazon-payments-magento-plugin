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
            $name      = mb_convert_kana($address->getName(), 's', 'utf-8');
            $names = preg_split('/^(.*)\s(.*)$/', $name);
            if(count($names) == 2) {
                $firstName = $names[0];
                $lastName = $names[1];
            } else {
                $firstName = $names[0];
                $lastName = '　';
            }
            //$lastName  = substr($name, strlen($firstName) + 1);

            $city = $address->getCity();
            $address1 = $address->getAddressLine1();
            $address2 = $address->getAddressLine2();

            if(!$city) {
                $city = $address->getAddressLine1();
                $address1 = ($address2 == '') ? '　' : $address2;
                $address2 = '';
            }

            // Find Mage state/region ID
            $regionModel = Mage::getModel('directory/region')->loadByCode($address->getStateOrRegion(), $address->getCountryCode());
            $regionId    = $regionModel->getId();
            if(!$regionId) {
                $regionCollection = Mage::getResourceModel('directory/region_collection')->addCountryFilter($address->getCountryCode());
                foreach($regionCollection as $region) {
                    if($region->getName() == $address->getStateOrRegion()) {
                        $regionId = $region->getId();
                    }
                }
            }

            $data = array(
                'firstname'   => $firstName,
                'lastname'    => ($lastName != '') ? $lastName : '　',
                'street'      => array($address1, $address2),
                'city'        => $city,
                'region'      => $address->getStateOrRegion(),
                'region_id'   => $regionId,
                'postcode'    => $address->getPostalCode(),
                'country_id'  => $address->getCountryCode(),
                'telephone'   => ($address->getPhone()) ? $address->getPhone() : '-', // Mage requires phone number
                'use_for_shipping' => true,
            );

            if ($email = Mage::getSingleton('checkout/session')->getCustomerEmail()) {
                $data['email'] = $email;
            }


            // Set billing address (if allowed by scope)
            if ($orderReferenceDetails->getBillingAddress()) {
                $billing = $orderReferenceDetails->getBillingAddress()->getPhysicalAddress();
                //$data['use_for_shipping'] = false;

                $name      = mb_convert_kana($billing->getName(), 's', 'utf-8');
                $names = preg_split('/^(.*)\s(.*)$/', $name);
                if(count($names) == 2) {
                    $firstName = $names[0];
                    $lastName = $names[1];
                } else {
                    $firstName = $names[0];
                    $lastName = '　';
                }
                //$lastName  = substr($name, strlen($firstName) + 1);

                $city = $billing->getCity();
                $address1 = $billing->getAddressLine1();
                $address2 = $billing->getAddressLine2();

                if(!$city) {
                    $city = $billing->getAddressLine1();
                    $address1 = ($address2 == '') ? '　' : $address2;
                    $address2 = '';
                }

                // Find Mage state/region ID
                $regionModel = Mage::getModel('directory/region')->loadByCode($billing->getStateOrRegion(), $billing->getCountryCode());
                $regionId    = $regionModel->getId();
                if(!$regionId) {
                    $regionCollection = Mage::getResourceModel('directory/region_collection')->addCountryFilter($billing->getCountryCode());
                    foreach($regionCollection as $region) {
                        if($region->getName() == $billing->getStateOrRegion()) {
                            $regionId = $region->getId();
                        }
                    }
                }

                $dataBilling = array(
                    'firstname'   => $firstName,
                    'lastname'    => ($lastName != '') ? $lastName : '.',
                    'street'      => array($address1, $address2),
                    'city'        => $city,
                    'region'      => $billing->getStateOrRegion(),
                    'region_id'   => $regionId,
                    'postcode'    => $billing->getPostalCode(),
                    'country_id'  => $billing->getCountryCode(),
                    'telephone'   => ($billing->getPhone()) ? $billing->getPhone() : '-',
                    'use_for_shipping' => false,
                );

                $this->_getCheckout()->saveBilling($dataBilling, null);

            }
            else {
                $this->_getCheckout()->saveBilling($data, null);
            }

            return $this->_getCheckout()->saveShipping($data);
        }
    }

}

