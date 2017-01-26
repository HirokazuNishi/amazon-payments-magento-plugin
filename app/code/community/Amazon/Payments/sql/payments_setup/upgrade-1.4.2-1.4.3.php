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

$amazon_token_table = $installer->getTable('amazon_payments/token');

if ($installer->getConnection()->isTableExists($amazon_token_table) != true) {
    $amazon_table = $installer->getConnection()
        ->newTable($amazon_token_table)
        ->addColumn('token_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'identity' => true,
            'unsigned' => true,
            'nullable' => false,
            'primary'  => true
        ), 'Token ID')
        ->addColumn('customer_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array (
            'nullable' => true,
            'unsigned' => true
        ), 'Customer Entity ID')
        ->addColumn('amazon_uid', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array (
            'nullable' => false,
        ), 'Amazon User ID')
        ->addColumn('amazon_billing_agreement_id', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array (
            'nullable' => false,
        ), 'Amazon Billing Agreement ID')
        ->addColumn('shipping_method', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array (
            'nullable' => false,
        ), 'Shipping Method')
        ->addIndex($installer->getIdxName('amazon_payments_token', array('customer_id')), array('customer_id'))
        ->addIndex($installer->getIdxName('amazon_payments_token', array('amazon_uid'), Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE), array('amazon_uid'));

    $installer->getConnection()->createTable($amazon_table);

    $installer->getConnection()->addConstraint(
        'fk_amazon_payments_token_customer_entity_id',
        $installer->getTable('amazon_payments_token'),
        'customer_id',
        $installer->getTable('customer/entity'),
        'entity_id',
        'cascade',
        'restrict'
    );
}

$installer->endSetup();