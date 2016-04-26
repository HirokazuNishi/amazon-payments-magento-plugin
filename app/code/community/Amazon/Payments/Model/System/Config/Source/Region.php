<?php
/**
 * Amazon Login
 *
 * @category    Amazon
 * @package     Amazon_Login
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/Apache-2.0  Apache License, Version 2.0
 */

class Amazon_Payments_Model_System_Config_Source_Region
{
    public function toOptionArray()
    {
        return array(
            array('value'=>'us', 'label'=>Mage::helper('adminhtml')->__('United States')),
            //array('value'=>'eu', 'label'=>Mage::helper('adminhtml')->__('Europe')),
            array('value'=>'jp', 'label'=>Mage::helper('adminhtml')->__('Japan')),
            //array('value'=>'uk', 'label'=>Mage::helper('adminhtml')->__('United Kingdom')),
            //array('value'=>'de', 'label'=>Mage::helper('adminhtml')->__('Germany')),
        );
    }
}
