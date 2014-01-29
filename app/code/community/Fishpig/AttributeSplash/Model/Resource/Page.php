<?php
/**
 * @category    Fishpig
 * @package     Fishpig_AttributeSplash
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_AttributeSplash_Model_Resource_Page extends Fishpig_AttributeSplash_Model_Resource_Abstract
{
	public function _construct()
	{
		$this->_init('attributeSplash/page', 'page_id');
	}

	/**
	 * Retrieve select object for load object data
	 * This gets the default select, plus the attribute id and code
	 *
	 * @param   string $field
	 * @param   mixed $value
	 * @return  Zend_Db_Select
	*/
	protected function _getLoadSelect($field, $value, $object)
	{
		$select = parent::_getLoadSelect($field, $value, $object)
			->join(array('_option_table' => $this->getTable('eav/attribute_option')), '`_option_table`.`option_id` = `main_table`.`option_id`', '')
			->join(array('_attribute_table' => $this->getTable('eav/attribute')), '`_attribute_table`.`attribute_id`=`_option_table`.`attribute_id`', array('attribute_id', 'attribute_code', 'frontend_label'));
		
		return $select;
	}
	
	/**
	 * Retrieve the store table name
	 *
	 * @return string
	 */
	public function getStoreTable()
	{
		return $this->getTable('attributeSplash/page_store');
	}

	/**
	 * Retrieve the name of the unique field
	 *
	 * @return string
	 */
	public function getUniqueFieldName()
	{
		return 'option_id';	
	}
 
	/**
	 * Retrieve a collection of products associated with the splash page
	 * @thanks Flat catalog fix:
	 *   http://www.xtreme-vision.net/magento/magento-fishpig-attribute-splash-pages-and-flat-catalog
	 *
	 * @return Mage_Catalog_Model_Resource_Eav_Resource_Product_Collection
	 */	
	public function getProductCollection(Fishpig_AttributeSplash_Model_Page $page)
	{	
		$collection = Mage::getResourceModel('catalog/product_collection')
			->setStoreId($page->getStoreId())
			->addAttributeToFilter('status', 1)
			->addAttributeToFilter('visibility', array('in' => array(
				Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG,
				Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH
			)));

		$alias = $page->getAttributeCode().'_index';

		$collection->getSelect()
			->join(
				array($alias => $this->getTable('catalog/product_index_eav')),
				"`{$alias}`.`entity_id` = `e`.`entity_id`"
				. $this->_getReadAdapter()->quoteInto(" AND `{$alias}`.`attribute_id` = ? ", $page->getAttributeId())
				. $this->_getReadAdapter()->quoteInto(" AND `{$alias}`.`store_id` = ? ", $page->getStoreId())
				. $this->_getReadAdapter()->quoteInto(" AND `{$alias}`.`value` = ?", $page->getOptionId()),
				''
			);
			
		if (!Mage::getStoreConfigFlag('cataloginventory/options/show_out_of_stock', $page->getStoreId())) {
			Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($collection);
		}

		return $collection;
	}
	
	/**
	 * Set required fields before saving model
	 *
	 * @param Mage_Core_Model_Abstract $object
	 * @return $this
	 */
	protected function _beforeSave(Mage_Core_Model_Abstract $object)
	{
		if ($object->getId()) {
			$object->getAttributeModel();

			$object->unsetData('attribute_id');
#			$object->unsetData('option_id');
		}
		
		if (!$object->getData('store_ids')) {
			throw new Exception('Store IDs not set.');
		}

		if (!$this->_pageIsUniqueToStores($object)) {
			throw new Exception('A page already exists for this attribute and store combination.');
		}

		return parent::_beforeSave($object);
	}
	
	/**
	 * Determine whether [ages scope if unique to store
	 *
	 * @param Mage_Core_Model_Abstract $object
	 * @return bool
	 */
	protected function _pageIsUniqueToStores(Mage_Core_Model_Abstract $object)
	{
		if (Mage::app()->isSingleStoreMode() || !$object->hasStoreIds()) {
			$stores = array(Mage_Core_Model_App::ADMIN_STORE_ID);
		}
		else {
			$stores = (array)$object->getData('store_ids');
		}

		$select = $this->_getReadAdapter()
			->select()
			->from(array('main_table' => $this->getMainTable()), 'page_id')
			->join(array('_store' => $this->getStoreTable()), 'main_table.page_id = _store.page_id', '')
			->where('option_id=?', $object->getOptionId())
			->where('_store.store_id IN (?)', $stores)
			->limit(1);

		if ($object->getId()) {
			$select->where('main_table.page_id <> ?', $object->getId());
		}

		return $this->_getWriteAdapter()->fetchOne($select) === false;
	}
	
	/**
	 * Auto-update splash group
	 *
	 * @param Mage_Core_Model_Abstract $object
	 * @return $this
	 */
	protected function _afterSave(Mage_Core_Model_Abstract $object)
	{
		parent::_afterSave($object);
		
		if (!$object->getSkipAutoCreateGroup()) {
			$this->updateSplashGroup($object);
		}
		
		return $this;
	}
	
	/**
	 * Check whether the attribute group exists
	 * If not, create the group
	 *
	 * @param Fishpig_AttributeSPlash_Model_Page $page
	 */
	public function updateSplashGroup(Fishpig_AttributeSplash_Model_Page $page)
	{
		if (!$page->getSplashGroup()) {
			$group = Mage::getModel('attributeSplash/group')
				->setAttributeId($page->getAttributeModel()->getId())
				->setDisplayName($page->getAttributeModel()->getFrontendLabel())
				->setStoreId(0)
				->setIsEnabled(1);

			try {
				$group->save();
			}
			catch (Exception $e) {
				Mage::helper('attributeSplash')->log($e->getMessage());
			}
		}

		return $this;
	}

	/**
	 * Retrieve the group associated with the splash page
	 * This will retrieve the most related group
	 * If there isn't a group for the same store, the admin group will be returned
	 *
	 * @param Fishpig_AttributeSplash_Model_Page $page
	 * @return Fishpig_AttributeSplash_Model_Group|false
	 */
	public function getSplashGroup(Fishpig_AttributeSplash_Model_Page $page)
	{
		$groups = Mage::getResourceModel('attributeSplash/group_collection')
			->addAttributeIdFilter($page->getAttributeModel()->getAttributeId())
			->addStoreFilter($page->getStoreId())
			->setCurPage(1)
			->setPageSize(1)
			->load();

		return count($groups) > 0
			? $groups->getFirstItem()
			: false;
	}

	/**
	 * Get the index table for pags
	 *
	 * @return string
	 */
	public function getIndexTable()
	{
		return $this->getTable('attributeSplash/page_index');
	}
}
