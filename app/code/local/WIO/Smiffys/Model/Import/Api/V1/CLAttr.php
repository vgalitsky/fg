<?php
/**
 * @package imprtexport_attribute
 * @autor Victor Galitsky
 * @copyright (c) 2013, Victor Galitsky
 */
//******************************************************************************
//------------------------------------------------------------------------------
abstract class CL_ImportExport_Model_Mysql4_Abstract {

    public function getTable($name) {
        return Mage::getSingleton('core/resource')->getTableName($name);
    }

    /**
     * @return Varien_Db_Adapter_Pdo_Mysql
     */
    protected function _getReadAdapter() {
        return Mage::getSingleton('core/resource')->getConnection('core_read');
    }

    /**
     * @return Varien_Db_Adapter_Pdo_Mysql
     */
    protected function _getWriteAdapter() {
        return Mage::getSingleton('core/resource')->getConnection('core_write');
    }

    public function keysDisablePlain($table) {
        $this->_getWriteAdapter()->query("ALTER TABLE {$table} DISABLE KEYS");
        return $this;
    }

    public function keysEnablePlain($table) {
        $this->_getWriteAdapter()->query("ALTER TABLE {$table} ENABLE KEYS");
        return $this;
    }

    public function keysDisable($table) {
        $table = $this->getTable($table);
        $this->_getWriteAdapter()->query("ALTER TABLE {$table} DISABLE KEYS");
        return $this;
    }

    public function keysEnable($table) {
        $table = $this->getTable($table);
        $this->_getWriteAdapter()->query("ALTER TABLE {$table} ENABLE KEYS");
        return $this;
    }

    
    public function rollbackTransaction() {
        $this->_getWriteAdapter()->query("ROLLBACK");
        return $this;
    }

    
    public function beginTransaction() {
        $this->_getWriteAdapter()->query("START TRANSACTION");
        return $this;
    }

    
    public function endTransaction() {
        $this->_getWriteAdapter()->query("COMMIT");
        return $this;
    }

    /**
     * @throws Varien_Db_Exception
     * @param Varien_Db_Select $select
     * @param $table
     * @return string
     */
    public function updateFromSelect(Varien_Db_Select $select, $table) {

        if (!is_array($table)) {
            $table = array($table => $table);
        }

        $keys = array_keys($table);
        $tableAlias = $keys[0];
        $tableName = $table[$keys[0]];

        $query = sprintf('UPDATE %s', $this->_getWriteAdapter()->quoteTableAs($tableName, $tableAlias));

        
        $joinConds = array();
        foreach ($select->getPart(Zend_Db_Select::FROM) as $correlationName => $joinProp) {
            if ($joinProp['joinType'] == Zend_Db_Select::FROM) {
                $joinType = strtoupper(Zend_Db_Select::INNER_JOIN);
            } else {
                $joinType = strtoupper($joinProp['joinType']);
            }
            $joinTable = '';
            if ($joinProp['schema'] !== null) {
                $joinTable = sprintf('%s.', $this->_getWriteAdapter()->quoteIdentifier($joinProp['schema']));
            }
            $joinTable .= $this->_getWriteAdapter()->quoteTableAs($joinProp['tableName'], $correlationName);

            $join = sprintf(' %s %s', $joinType, $joinTable);

            if (!empty($joinProp['joinCondition'])) {
                $join = sprintf('%s ON %s', $join, $joinProp['joinCondition']);
            }

            $joinConds[] = $join;
        }

        if ($joinConds) {
            $query = sprintf("%s\n%s", $query, implode("\n", $joinConds));
        }

        
        $columns = array();
        foreach ($select->getPart(Zend_Db_Select::COLUMNS) as $columnEntry) {
            list($correlationName, $column, $alias) = $columnEntry;
            if (empty($alias)) {
                $alias = $column;
            }
            if (!$column instanceof Zend_Db_Expr && !empty($correlationName)) {
                $column = $this->_getWriteAdapter()->quoteIdentifier(array($correlationName, $column));
            }
            $columns[] = sprintf('%s = %s', $this->_getWriteAdapter()->quoteIdentifier(array($tableAlias, $alias)), $column);
        }

        if (!$columns) {
            throw new Varien_Db_Exception('The columns for UPDATE statement are not defined');
        }

        $query = sprintf("%s\nSET %s", $query, implode(', ', $columns));

        
        $wherePart = $select->getPart(Zend_Db_Select::WHERE);
        if ($wherePart) {
            $query = sprintf("%s\nWHERE %s", $query, implode(' ', $wherePart));
        }

        return $query;
    }

    protected function _getReplaceSqlQuery($tableName, array $columns, array $values) {
        $tableName = $this->_getWriteAdapter()->quoteIdentifier($tableName, true);
        $columns = array_map(array($this->_getWriteAdapter(), 'quoteIdentifier'), $columns);
        $columns = implode(',', $columns);
        $values = implode(', ', $values);

        $insertSql = sprintf('REPLACE INTO %s (%s) VALUES %s', $tableName, $columns, $values);

        return $insertSql;
    }

    public function replaceMultiple($table, array $data) {
        $row = reset($data);

        if (!is_array($row)) {
            Mage::throwException("Row should be an array in " . __METHOD__);
        }

        // validate data array
        $cols = array_keys($row);
        $insertArray = array();
        foreach ($data as $row) {
            $line = array();
            if (array_diff($cols, array_keys($row))) {
                throw new Zend_Db_Exception('Invalid data for insert');
            }
            foreach ($cols as $field) {
                $line[] = $row[$field];
            }
            $insertArray[] = $line;
        }
        unset($row);

        return $this->replaceArray($table, $cols, $insertArray);
    }

    /**
     * Insert array to table based on columns definition
     *
     * @param   string $table
     * @param   array $columns
     * @param   array $data
     * @return  int
     * @throws  Zend_Db_Exception
     */
    public function replaceArray($table, array $columns, array $data) {
        $values = array();
        $bind = array();
        $columnsCount = count($columns);
        foreach ($data as $row) {
            if ($columnsCount != count($row)) {
                throw new Zend_Db_Exception('Invalid data for insert');
            }
            $values[] = $this->_prepareInsertData($row, $bind);
        }

        $insertQuery = $this->_getReplaceSqlQuery($table, $columns, $values);

        $stmt = $this->_getWriteAdapter()->query($insertQuery, $bind);
        $result = $stmt->rowCount();
        return $result;
    }

    protected function _prepareInsertData($row, &$bind) {
        if (is_array($row)) {
            $line = array();
            foreach ($row as $value) {
                if ($value instanceof Zend_Db_Expr) {
                    $line[] = $value->__toString();
                } else {
                    $line[] = '?';
                    $bind[] = $value;
                }
            }
            $line = implode(', ', $line);
        } elseif ($row instanceof Zend_Db_Expr) {
            $line = $row->__toString();
        } else {
            $line = '?';
            $bind[] = $row;
        }
        return sprintf('(%s)', $line);
    }

}

class CL_ImportExport_Model_Mysql4_Attribute extends CL_ImportExport_Model_Mysql4_Abstract {

    /**
     * @var int
     */
    protected $_entityTypeId = null;
    protected $_defaultAttributeSetId = null;

    /**
     * Constants
     */

    const TABLE_ENTITY_ATTR = "eav/entity_attribute";
    const TABLE_ATTRIBUTE_SET = "eav/attribute_set";

    public function setDefaultAttributeSetId($arg) {
        $this->_defaultAttributeSetId = (int) $arg;
        return $this;
    }

    /**
     * @return int
     */
    public function getDefaultAttributeSetId() {
        if (is_null($this->_defaultAttributeSetId)) {
            $this->_defaultAttributeSetId = Mage::getModel('catalog/product')->getResource()->getEntityType()->getDefaultAttributeSetId();
        }
        return $this->_defaultAttributeSetId;
    }

    public function getAttributeSetNames($entityTypeId = false) {
        if (false === $entityTypeId) {
            $entityTypeId = $this->getEntityTypeId();
        }
        $table = $this->getTable(self::TABLE_ATTRIBUTE_SET);
        $select = $this->_getReadAdapter()->select();
        $select->from(
                $table, array('attribute_set_id', 'attribute_set_name')
        )->where(
                'entity_type_id = ?', $entityTypeId
        );
        $result = array();
//        var_dump($select->__toString());
        $rowset = $this->_getReadAdapter()->fetchAll($select);
        foreach ($rowset as $row) {
            $result[$row['attribute_set_name']] = $row['attribute_set_id'];
        }
        return $result;
    }

    /**
     * @param bool $forceReload
     * @return int
     */
    public function getEntityTypeId($forceReload = false) {
        if ($forceReload || !$this->_entityTypeId) {
            $this->_entityTypeId = (int) Mage::getModel("eav/entity_type")
                            ->loadByCode("catalog_product")
                            ->getId();
        }
        return $this->_entityTypeId;
    }

    /**
     * @param bool $entityTypeId
     * @return void
     */
    public function getAllAttributeSetIds($entityTypeId = false) {
        if (false === $entityTypeId) {
            $entityTypeId = $this->getEntityTypeId();
        }
        $table = $this->getTable(self::TABLE_ENTITY_ATTR);
        $select = $this->_getReadAdapter()->select();
        $select->from($table, array('attribute_set_id'))
                ->distinct()
                ->where('entity_type_id = ?', $entityTypeId);

        return $this->_getReadAdapter()->fetchCol($select);
    }

    public function getFilterableAttributeCodes() {
        $select = $this->_getReadAdapter()->select();
        $select->from(
                array('main_table' => $this->getTable('catalog/eav_attribute'))
        )->join(
                array('help_table' => $this->getTable('eav/attribute')), "main_table.attribute_id = help_table.attribute_id AND main_table.is_filterable", array('attribute_code')
        );
        return $this->_getReadAdapter()->fetchCol($select);
    }

}

class CL_ImportExport_Model_Mysql4_Attribute_Options extends CL_ImportExport_Model_Mysql4_Abstract {

    /**
     * @var array
     */
    protected $_attributeOptions = null;

    protected function _insertAttributeNode($attributeId) {
        $this->_attributeOptions[$attributeId] = array();
        return $this;
    }

    
    protected function _insertOption($attributeId, $optionId, $value) {
        $this->_attributeOptions[$attributeId][$value] = $optionId;
        return $this;
    }

    /**
     * Constructor
     */
    public function __construct() {
        $this->getAttributeOptions(true);
    }

    /**
     * @param $attributeId
     * @param $options
     * @return
     */
    public function insertAttributeOptions($attributeId, $options) {
        if (is_scalar($options)) {
            $options = array($options);
        } elseif (is_object($options)) {
            Mage::throwException("Options arg in " . __METHOD__ . " can't be an object!");
        }
        // Empty array case
        if (!$options) {
            return;
        }
        $this->getAttributeOptions();
        $table = $this->getTable('eav/attribute_option');
        $tableValues = $this->getTable('eav/attribute_option_value');
        foreach ($options as $value) {
            $value = trim(ucwords( strtolower( $value ) ));
            $insert = array("attribute_id" => $attributeId);
            $this->_getWriteAdapter()->insert($table, $insert);
            $optionId = $this->_getWriteAdapter()->fetchOne("SELECT LAST_INSERT_ID()");
            if (!$optionId) {
                Mage::throwException("Can't insert to {$table}");
            }
            $insert = array('option_id' => $optionId, 'store_id' => 0, 'value' => $value);
            $this->_getWriteAdapter()->insert($tableValues, $insert);
            //$valueId = $this->_getWriteAdapter()->fetchOne("SELECT LAST_INSERT_ID()");
            $this->_insertOption($attributeId, $optionId, $value);
        }
    }

    /**
     * @param bool $forceReload
     * @return null
     */
    public function getAttributeOptions($forceReload = false) {
        if ($forceReload || is_null($this->_attributeOptions)) {
            $select = $this->_getReadAdapter()->select();
            $select->from(
                    array('q' => $this->getTable("eav/attribute")), array(
                'attribute_id',
                'attribute_code',
                'frontend_input',
                'backend_type',
                    )
            )->joinLeft(
                    array('qo' => $this->getTable('eav/attribute_option')), 'q.attribute_id = qo.attribute_id', array('option_id')
            )->joinLeft(
                    array('qv' => $this->getTable("eav/attribute_option_value")), 'qo.option_id = qv.option_id', array('value', 'value_id', new Zend_Db_Expr('IFNULL(`qv`.`store_id`, 0) AS `store_id`'))
            )->where(
                    'q.backend_type = "int"'
            )->where(
                    'q.frontend_input = "select" OR q.frontend_input = "multiselect"'
            )->where(
                    'store_id = ?', 0
            );
            $this->_attributeOptions = array();
            foreach ($this->_getReadAdapter()->fetchAll($select) as $row) {
                if (strlen($row['value']) && (int) $row['option_id'] !== 0) {
                    $this->_insertOption((int) $row['attribute_id'], (int) $row['option_id'], (string) $row['value']);
                } else {
                    $this->_insertAttributeNode((int) $row['attribute_id']);
                }
            }
        }
        return $this->_attributeOptions;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function hasOptionValuesByAttrId($id) {
        return isset($this->_attributeOptions[$id]);
    }

    /**
     * @param $id
     * @return array|null
     */
    public function getOptionValuesByAttrId($id) {
        if ($this->hasOptionValuesByAttrId($id)) {
            return $this->_attributeOptions[$id];
        }
        return null;
    }

    /**
     * @param $attrId
     * @param $optionValue
     * @return |null
     */
    public function getAttrOptionId($attrId, $optionValue) {
        if (!$this->hasOptionValuesByAttrId($attrId)) {
            return null;
        }
        return isset($this->_attributeOptions[$attrId][$optionValue]) ? $this->_attributeOptions[$attrId][$optionValue] : null;
    }

}

class CL_ImportExport_Model_Mysql4_Attribute_Map {

    /**
     * @var array
     */
    protected $_mapById = array();
    protected $_mapByCode = array();
    protected $_linearById = array();
    protected $_linearByCode = array();

    /**
     *
     */
    public function __construct() {
        $this->loadAll();
    }

    
    public function loadAll() {
        foreach ($this->_getResource()->getAllAttributeSetIds() as $setId) {
            $this->loadAttributeSet($setId, true);
        }
        return $this;
    }

    /**
     * @param bool $attrSetId
     * @return bool
     */
    public function hasAttributeSet($attrSetId = false) {
        if (false === $attrSetId) {
            $attrSetId = $this->_getResource()->getDefaultAttributeSetId();
        }
        return array_key_exists($attrSetId, $this->_mapById);
    }

    /**
     * @param bool $attrSetId
     * @return array
     */
    public function getAttributeSet($attrSetId = false) {
        if (false === $attrSetId) {
            $attrSetId = $this->_getResource()->getDefaultAttributeSetId();
        }
        if ($this->hasAttributeSet($attrSetId)) {
            return $this->_mapById[$attrSetId];
        }
        $this->loadAttributeSet($attrSetId);
        if (!$this->hasAttributeSet($attrSetId)) {
            return array();
        }
        return $this->_mapById[$attrSetId];
    }

    
    public function loadAttributeSet($attrSetId = false, $force = false) {
        if (false === $attrSetId) {
            $attrSetId = $this->_getResource()->getDefaultAttributeSetId();
        }
        if ($force || false === $this->hasAttributeSet($attrSetId)) {
            $collection = Mage::getResourceModel("eav/entity_attribute_collection");
            $collection->setEntityTypeFilter($this->_getResource()->getEntityTypeId());
            $collection->setAttributeSetFilter($attrSetId);
            $collection->load();
            foreach ($collection as $item) {
                $this->_mapById[$attrSetId][$item->getId()] = $item;
                $this->_mapByCode[$attrSetId][$item->getAttributeCode()] = $item;
                $this->_linearByCode[$item->getAttributeCode()] = $item;
                $this->_linearById[$item->getId()] = $item;
            }
        }
        return $this;
    }

    public function getDefaultAttributeSetId() {
        return $this->_getResource()->getDefaultAttributeSetId();
    }

    protected $_resource = null;

    
    protected function _getResource() {
        if (is_null($this->_resource)) {
            $this->_resource = new CL_ImportExport_Model_Mysql4_Attribute();
        }
        return $this->_resource;
    }

    /**
     * @return Mage_Eav_Model_Entity_Attribute_Collection
     */
    protected function _spawnCollection() {
        return Mage::getResourceModel("eav/entity_attribute_collection");
    }

    /**
     * @param $code
     * @return bool
     */
    public function hasAttributeByCode($code) {
        return isset($this->_linearByCode[$code]);
    }

    /**
     * @param $id
     * @return bool
     */
    public function hasAttributeById($id) {
        return isset($this->_linearById[$id]);
    }

    /**
     * @param $code
     * @return mixed|null
     */
    public function getAttributeByCode($code) {
        if ($this->hasAttributeByCode($code)) {
            return $this->_linearByCode[$code];
        }
        return null;
    }

    /**
     * @param $id
     * @return mixed|null
     */
    public function getAttributeById($id) {
        if ($this->hasAttributeById($id)) {
            return $this->_linearById[$id];
        }
        return null;
    }

    /**
     * @param $attrCode
     * @param bool $attributeSetId
     * @return bool
     */
    public function hasAttributeCodeInSet($attrCode, $attributeSetId = false) {
        if (false === $attributeSetId) {
            $attributeSetId = $this->_getResource()->getDefaultAttributeSetId();
        }
        return isset($this->_mapByCode[$attributeSetId][$attrCode]);
    }

    /**
     * @param $attrId
     * @param bool $attributeSetId
     * @return bool
     */
    public function hasAttributeIdInSet($attrId, $attributeSetId = false) {
        if (false === $attributeSetId) {
            $attributeSetId = $this->_getResource()->getDefaultAttributeSetId();
        }
        return isset($this->_mapById[$attributeSetId][$attrId]);
    }

}
