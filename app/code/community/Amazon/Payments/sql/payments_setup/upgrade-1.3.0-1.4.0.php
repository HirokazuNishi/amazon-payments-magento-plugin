<?php
/**
 * Amazon Payments
 *
 * @category    Amazon
 * @package     Amazon_Payments
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/Apache-2.0  Apache License, Version 2.0
 */


$installer = $this;

$installer->startSetup();

$db = $installer->getConnection();

// Encrypt keys
$select = $db->select()
    ->from(Mage::getSingleton('core/resource')->getTableName('core_config_data'))
    ->where('path IN (?)', array('payment/amazon_payments/access_secret', 'payment/amazon_payments/client_secret'));

foreach ($db->fetchAll($select) as $row) {
    $db->update(Mage::getSingleton('core/resource')->getTableName('core_config_data'), array('value' => Mage::helper('core')->encrypt(trim($row['value']))), 'config_id=' . $row['config_id']);
}

$installer->endSetup();
