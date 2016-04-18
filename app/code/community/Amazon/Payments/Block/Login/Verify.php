<?php
/**
 * Amazon Login
 *
 * @category    Amazon
 * @package     Amazon_Payments
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/Apache-2.0  Apache License, Version 2.0
 */

class Amazon_Payments_Block_Login_Verify extends Mage_Core_Block_Template
{
    public function getEmail()
    {
        $profile = $this->helper('amazon_payments')->getAmazonProfileSession();
        return $profile['email'];
    }

    public function getPostActionUrl()
    {
        return $this->helper('amazon_payments')->getVerifyUrl() . '?redirect=' . htmlentities($this->getRequest()->getParam('redirect'));
    }

    public function getForgotPasswordUrl()
    {
         return $this->helper('customer')->getForgotPasswordUrl();
    }

}
