<?php
/**
 * @category    Fishpig
 * @package     Fishpig_AttributeSplash
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_AttributeSplash_Block_Layer_View extends Mage_Catalog_Block_Layer_View
{
	/**
	 * Returns the layer object for the attributeSplash model
	 *
	 * @return Fishpig_AttributeSplash_Model_Layer
	 */
	public function getLayer()
	{
		return Mage::getSingleton('attributeSplash/layer');
	}
}
