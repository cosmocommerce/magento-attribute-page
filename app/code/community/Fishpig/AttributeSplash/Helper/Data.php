<?php
/**
 * @category    Fishpig
 * @package     Fishpig_AttributeSplash
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */
 
class Fishpig_AttributeSplash_Helper_Data extends Mage_Core_Helper_Abstract
{
	/**
	 * Retrieve a splash page for the product / attribute code combination
	 *
	 * @param Mage_Catalog_Model_Product $product
	 * @param $attributeCode
	 * @return Fishpig_AttributeSplash_Model_Splash|null
	 */
	public function getProductSplashPage(Mage_Catalog_Model_Product $product, $attributeCode)
	{
		$key = $attributeCode . '_splash_page';
		
		if (!$product->hasData($key)) {
			$product->setData($key, false);
			$collection = Mage::getResourceModel('attributeSplash/page_collection')
				->addStoreFilter(Mage::app()->getStore())
				->addAttributeCodeFilter($attributeCode)
				->addProductFilter($product);
			
			$collection->load();
			
			if ($collection->count() >= 1) {
				$splash = $collection->getFirstItem();
				
				if ($splash->getId()) {
					$product->setData($key, $splash);
				}
			}
		}
		
		return $product->getData($key);
	}
	
	/**
	 * Log an error message
	 *
	 * @param string $msg
	 * @return Fishpig_AttributeSplash_Helper_Data
	 */
	public function log($msg)
	{
		Mage::log($msg, false, 'attributeSplash.log', true);

		return $this;
	}
}
