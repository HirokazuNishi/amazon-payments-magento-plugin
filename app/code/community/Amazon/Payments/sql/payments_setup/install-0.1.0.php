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

/**
 * Add 'enable_amazonpayments' attribute to the 'eav/attribute' table
 */
$installer->addAttribute(Mage_Catalog_Model_Product::ENTITY, 'disable_amazonpayments', array(
    'group'             => 'Prices',
    'type'              => 'int',
    'backend'           => '',
    'frontend'          => '',
    'label'             => 'Disable Purchase with Amazon Payments',
    'input'             => 'select',
    'class'             => '',
    'source'            => 'eav/entity_attribute_source_boolean',
    'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'visible'           => true,
    'required'          => false,
    'user_defined'      => false,
    'default'           => '0',
    'searchable'        => false,
    'filterable'        => false,
    'comparable'        => false,
    'visible_on_front'  => false,
    'unique'            => false,
    'apply_to'          => '',
    'is_configurable'   => false
));

/**
 * Create amazon_login table
 */
$amazon_login_table = $installer->getTable('amazon_payments/login');

if ($installer->getConnection()->isTableExists($amazon_login_table) != true) {

    $amazon_table = $installer->getConnection()
        ->newTable($installer->getTable('amazon_payments/login'))
        ->addColumn('login_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'identity' => true,
            'unsigned' => true,
            'nullable' => false,
            'primary'  => true
        ), 'Login ID')
        ->addColumn('customer_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array (
            'nullable' => false,
            'unsigned' => true
        ), 'Customer Entity ID')
        ->addColumn('amazon_uid', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array (
            'nullable' => true,
            'unsigned' => true
        ), 'Amazon User ID')
        ->addIndex($installer->getIdxName('amazon_payments/login', array('customer_id')), array('customer_id'))
        ->addIndex($installer->getIdxName('amazon_payments/login', array('amazon_uid')), array('amazon_uid'));

    $installer->getConnection()->createTable($amazon_table);

    $installer->getConnection()->addConstraint(
        'fk_amazon_login_customer_entity_id',
        $installer->getTable('amazon_payments/login'),
        'customer_id',
        $installer->getTable('customer/entity'),
        'entity_id',
        'cascade',
        'restrict'
    );
}

$installer->endSetup();