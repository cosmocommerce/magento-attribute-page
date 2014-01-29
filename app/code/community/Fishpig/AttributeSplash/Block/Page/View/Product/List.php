<?php
/**
 * @category    Fishpig
 * @package     Fishpig_AttributeSplash
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_AttributeSplash_Block_Page_View_Product_List extends Mage_Catalog_Block_Product_List
{
	/**
	 * Retrieves the current layer product collection
	 *
	 * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
	 */
	protected function _getProductCollection()
	{
		if (is_null($this->_productCollection)) {
			$this->_productCollection = Mage::getSingleton('attributeSplash/layer')->getProductCollection();
		}
		
		return $this->_productCollection;
	}
}
