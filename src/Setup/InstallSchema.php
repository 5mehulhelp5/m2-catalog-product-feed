<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductFeed\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Zend_Db_Exception;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * @throws Zend_Db_Exception
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context): void
    {
        $setup->startSetup();

        $connection = $setup->getConnection();

        $attributeTableName = $setup->getTable('catalog_product_feed_attribute');

        if ($connection->isTableExists($attributeTableName)) {
            $connection->dropTable($attributeTableName);
        }

        $attributeTable = $connection->newTable($attributeTableName);

        $attributeTable->addColumn(
            'attribute_id',
            Table::TYPE_INTEGER,
            10,
            [
                'identity' => true,
                'unsigned' => true,
                'nullable' => false,
                'primary'  => true
            ],
            'Primary identifier of attribute'
        );
        $attributeTable->addColumn(
            'eav_attribute_id',
            Table::TYPE_INTEGER,
            10,
            [
                'unsigned' => true,
                'nullable' => true
            ],
            'EAV attribute id'
        );
        $attributeTable->addColumn(
            'custom_field',
            Table::TYPE_TEXT,
            255,
            [
                'nullable' => true
            ],
            'Custom field'
        );
        $attributeTable->addColumn(
            'fixed_value',
            Table::TYPE_TEXT,
            255,
            [
                'nullable' => true
            ],
            'Fixed value'
        );
        $attributeTable->addColumn(
            'value_format',
            Table::TYPE_TEXT,
            255,
            [
                'nullable' => true
            ],
            'Value format'
        );
        $attributeTable->addColumn(
            'position',
            Table::TYPE_SMALLINT,
            3,
            [
                'nullable' => true,
                'default'  => 0
            ],
            'Position in feed'
        );
        $attributeTable->addColumn(
            'attribute_code',
            Table::TYPE_TEXT,
            255,
            [
                'nullable' => true,
            ],
            'Target attribute code'
        );
        $attributeTable->addColumn(
            'is_product',
            Table::TYPE_SMALLINT,
            1,
            [
                'nullable' => false,
                'unsigned' => true,
                'default'  => 0
            ],
            'Product flag'
        );
        $attributeTable->addColumn(
            'is_variant',
            Table::TYPE_SMALLINT,
            1,
            [
                'nullable' => false,
                'unsigned' => true,
                'default'  => 0
            ],
            'Variant flag'
        );
        $attributeTable->addColumn(
            'use_for_delta',
            Table::TYPE_SMALLINT,
            1,
            [
                'nullable' => false,
                'unsigned' => true,
                'default'  => 0
            ],
            'Delta flag'
        );
        $attributeTable->addColumn(
            'character_data',
            Table::TYPE_SMALLINT,
            1,
            [
                'nullable' => true,
                'unsigned' => true,
                'default'  => 0
            ],
            'Character data'
        );
        $attributeTable->addColumn(
            'strip_tags',
            Table::TYPE_SMALLINT,
            1,
            [
                'nullable' => true,
                'unsigned' => true,
                'default'  => 0
            ],
            'Strip tags'
        );
        $attributeTable->addColumn(
            'integration',
            Table::TYPE_TEXT,
            255,
            [
                'nullable' => false
            ],
            'Integration'
        );
        $attributeTable->addColumn(
            'created_at',
            Table::TYPE_DATETIME,
            null,
            [
                'nullable' => false
            ],
            'Creation date'
        );
        $attributeTable->addColumn(
            'updated_at',
            Table::TYPE_DATETIME,
            null,
            [
                'nullable' => false
            ],
            'Update date'
        );

        $attributeTable->addIndex(
            $setup->getIdxName(
                $attributeTableName,
                ['attribute_code', 'integration']
            ),
            ['attribute_code', 'integration'],
            ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
        );

        $connection->createTable($attributeTable);

        $changeLogTableName = $setup->getTable('catalog_product_feed_indexer_change_log');

        if ($connection->isTableExists($changeLogTableName)) {
            $connection->dropTable($changeLogTableName);
        }

        $changeLogTable = $connection->newTable($changeLogTableName);

        $changeLogTable->addColumn(
            'change_log_id',
            Table::TYPE_INTEGER,
            10,
            [
                'identity' => true,
                'unsigned' => true,
                'nullable' => false,
                'primary'  => true
            ],
            'Primary identifier of change log'
        );
        $changeLogTable->addColumn(
            'entity_type_id',
            Table::TYPE_SMALLINT,
            5,
            [
                'nullable' => false
            ],
            'Magento entity type id'
        );
        $changeLogTable->addColumn(
            'entity_id',
            Table::TYPE_INTEGER,
            10,
            [
                'unsigned' => true,
                'nullable' => false
            ],
            'Magento entity id'
        );
        $changeLogTable->addColumn(
            'created_at',
            Table::TYPE_DATETIME,
            null,
            [
                'nullable' => false
            ],
            'Creation date'
        );
        $changeLogTable->addColumn(
            'updated_at',
            Table::TYPE_DATETIME,
            null,
            [
                'nullable' => false
            ],
            'Update date'
        );

        $changeLogTable->addIndex(
            $setup->getIdxName(
                $changeLogTableName,
                ['entity_type_id']
            ),
            ['entity_type_id'],
            ['type' => AdapterInterface::INDEX_TYPE_INDEX]
        );
        $changeLogTable->addIndex(
            $setup->getIdxName(
                $changeLogTableName,
                ['entity_type_id', 'entity_id']
            ),
            ['entity_type_id', 'entity_id'],
            ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
        );

        $connection->createTable($changeLogTable);

        $setup->endSetup();
    }
}
