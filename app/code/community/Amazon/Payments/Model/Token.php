<?php
/**
 * Amazon Payments
 *
 * @category    Amazon
 * @package     Amazon_Payments
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/Apache-2.0  Apache License, Version 2.0
 */

class Amazon_Payments_Model_Token extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('amazon_payments/token');
    }

    /**
     * Get token info (Amazon billing agreement ID and shipping method)
     */
    public function getBillingAgreement($customerId = null, $amazonUid = null)
    {
        if ($customerId) {
            $row = $this->load($customerId, 'customer_id');
        }
        elseif ($amazonUid) {
            $row = $this->load($amazonUid, 'amazon_uid');
        }
        else {
            $amazonProfile = Mage::getSingleton('customer/session')->getAmazonProfile();
            $amazonUid = $amazonProfile['user_id'];
            $row = $this->load($amazonUid, 'amazon_uid');
        }

        return $row;
    }

    /**
     * Save token (Amazon billing agreement ID)
     */
    public function saveBillingAgreementId($amazonBillingAgreementId, $shippingMethod, $amazonUid = null)
    {
        if (!$amazonUid) {
            $amazonProfile = Mage::getSingleton('customer/session')->getAmazonProfile();
            $amazonUid = $amazonProfile['user_id'];
        }

        $row = $this->load($amazonUid, 'amazon_uid');

        if ($row->getTokenId())  {
            $row
                ->setAmazonBillingAgreementId($amazonBillingAgreementId)
                ->setShippingMethod($shippingMethod)
                ->save();
        }
        else {
            if (Mage::getSingleton('customer/session')->isLoggedIn()) {
              $customerId = Mage::getSingleton('customer/session')->getCustomer()->getId();
            }
            else {
              $customerId = null;
            }

            $this->setAmazonBillingAgreementId($amazonBillingAgreementId)
                 ->setAmazonUid($amazonUid)
                 ->setCustomerId($customerId)
                 ->setShippingMethod($shippingMethod)
                 ->save();
        }
    }

}