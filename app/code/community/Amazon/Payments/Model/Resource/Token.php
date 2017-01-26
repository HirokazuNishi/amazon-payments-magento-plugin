<?php
/**
 * Amazon Payments
 *
 * @category    Amazon
 * @package     Amazon_Payments
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/Apache-2.0  Apache License, Version 2.0
 */

class Amazon_Payments_Model_Resource_Token extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('amazon_payments/token', 'token_id');
    }
}
