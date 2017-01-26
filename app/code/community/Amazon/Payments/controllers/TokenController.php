<?php
/**
 * Amazon Payments
 *
 * @category    Amazon
 * @package     Amazon_Payments
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/Apache-2.0  Apache License, Version 2.0
 */

class Amazon_Payments_TokenController extends Mage_Core_Controller_Front_Action
{

    /**
     * Display Ajax cart
     */
    public function ajaxcartAction()
    {
        header('X-Frame-Options: ALLOW-FROM ' . Mage::getBaseUrl());

        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * Token checkout
     */
    public function checkoutAction()
    {
        header('Access-Control-Allow-Origin: ' . Mage::getUrl('',array('_secure'=>true)));
        header('Access-Control-Allow-Methods: GET, POST');
        header('Access-Control-Allow-Headers: Content-Type');


        $redirectUrl =  Mage::getUrl('checkout/onepage/success');


        $token = Mage::getModel('amazon_payments/token')->getBillingAgreement();

        if ($amazonBillingAgreementId = $token->getAmazonBillingAgreementId()) {
            $_api = Mage::getModel('amazon_payments/api');

            $quote = Mage::getSingleton('checkout/session')->getQuote();

            $quote->setSendCconfirmation(1);

            $orderDetails = $_api->getBillingAgreementDetails($amazonBillingAgreementId, Mage::getSingleton('checkout/session')->getAmazonAccessToken());

            $address = $orderDetails->getDestination()->getPhysicalDestination();

            // Split name into first/last
            $name      = $address->getName();
            $firstName = substr($name, 0, strrpos($name, ' '));
            $lastName  = substr($name, strlen($firstName) + 1);

            // Find Mage state/region ID
            $regionModel = Mage::getModel('directory/region')->loadByCode($address->getStateOrRegion(), $address->getCountryCode());
            $regionId    = $regionModel->getId();

            $data = array(
                'customer_address_id' => '',
                'firstname'   => $firstName,
                'lastname'    => $lastName,
                //'street'      => array($address->getAddressLine1(), $address->getAddressLine2()),
                'street'      => $address->getAddressLine1(),
                'city'        => $address->getCity(),
                'region_id'   => $regionId,
                'postcode'    => $address->getPostalCode(),
                'country_id'  => $address->getCountryCode(),
                'telephone'   => ($address->getPhone()) ? $address->getPhone() : '-', // Mage requires phone number
                'use_for_shipping' => true,
            );

            if ($email = Mage::getSingleton('checkout/session')->getCustomerEmail()) {
                $data['email'] = $email;
            }


            $quote->getBillingAddress()->addData($data);
            $shippingAddress = $quote->getShippingAddress()->addData($data);

            // Collect Rates and Set Shipping & Payment Method
            $shippingAddress->setCollectShippingRates(true)
                           ->collectShippingRates()
                           ->setShippingMethod($token->getShippingMethod())
                           ->setPaymentMethod('amazon_payments');

            // Set Sales Order Payment
            $quote->getPayment()->importData(array(
                'method' => 'amazon_payments',
                'additional_information' => array(
                    'order_reference' => $amazonBillingAgreementId,
                    'billing_agreement_id' => $amazonBillingAgreementId,
                ),
            ));

            // Collect Totals & Save Quote
            $quote->collectTotals()->save();

            // Create Order From Quote
            $service = Mage::getModel('sales/service_quote', $quote);

            try {
                $service->submitAll();

                $order = $service->getOrder();

                // Set checkout session values for success page
                Mage::getSingleton('checkout/session')->setLastQuoteId($quote->getId())
                    ->setLastSuccessQuoteId($quote->getId())
                    ->setLastOrderId($order->getId())
                    ->setRedirectUrl($redirectUrl)
                    ->setLastRealOrderId($order->getIncrementId())
                    ;

                // Clear cart
                foreach($quote->getItemsCollection() as $item){
                    Mage::getSingleton('checkout/cart')->removeItem($item->getId())->save();
                }

            }
            catch (Exception $e) {
                $redirectUrl = Mage::helper('core/http')->getHttpReferer() ? Mage::helper('core/http')->getHttpReferer() : Mage::getUrl();
                Mage::getSingleton('core/session')->addError($e->getMessage());

                if (strpos($redirectUrl, 'ajaxcart') !== false) {
                    $redirectUrl = Mage::getUrl();
                }
            }

            // Use JS to redirect from ajaxcart modal
            $this->getResponse()->setHeader('Content-Type', 'text/html')
              ->setBody("<script>window.top.location.href = '$redirectUrl';</script>");

        }


    }

}
