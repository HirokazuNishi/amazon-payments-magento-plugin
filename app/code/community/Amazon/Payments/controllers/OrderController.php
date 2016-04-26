<?php
/**
 * Amazon Payments Order Controller
 *
 * @category    Amazon
 * @package     Amazon_Payments
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/Apache-2.0  Apache License, Version 2.0
 */

class Amazon_Payments_OrderController extends Mage_Core_Controller_Front_Action
{

    /**
     * Confirm order and re-authorize
     */
    public function confirmAction()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        $order = Mage::getModel('sales/order')->load($orderId);

        $orderUrl = Mage::getUrl('sales/order/view', array(
            'order_id'       => $order->getId(),
            '_store'         => $order->getStoreId(),
        ));

        if ($order->getPayment()->getMethod() == 'amazon_payments' && $order->getState() == Mage_Sales_Model_Order::STATE_HOLDED) {
            $orderReferenceId = $order->getPayment()->getAdditionalInformation('order_reference');

            $_api = Mage::getModel('amazon_payments/api')->setStore($order->getStoreId());

            try {
                $apiResult = $_api->confirmOrderReference($orderReferenceId);

                // Re-authorize
                Mage::getModel('amazon_payments/async')->syncOrderStatus($order, true);

                Mage::getSingleton('core/session')->addSuccess(Mage::helper('checkout')->__('Thank you for updating your payment method.'));

            }
            catch (Exception $e) {
                Mage::getSingleton('core/session')->addError(Mage::helper('checkout')->__('Please try another payment method.'));
                Mage::logException($e);
            }
        }

        $this->_redirect('sales/order/view/order_id/' . $order->getId());
    }

}
