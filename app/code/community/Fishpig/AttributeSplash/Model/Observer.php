<?php
/**
 * @category    Fishpig
 * @package     Fishpig_AttributeSplash
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_AttributeSplash_Model_Observer
{
	/**
	 * Inject links into the top navigation
	 *
	 * @param Varien_Event_Observer $observer
	 * @return $this
	 */
	public function injectTopmenuLinksObserver(Varien_Event_Observer $observer)
	{	
		if (Mage::getStoreConfigFlag('attributeSplash/navigation/enabled')) {
			$groups = Mage::getResourceModel('attributeSplash/group_collection')
				->addStoreFilter(Mage::app()->getStore()->getId())
				->addOrderByName()
				->load();
				
			$this->_injectLinks($groups, $observer->getEvent()->getMenu());
		}
		
		return $this;
	}
	
	/**
	 * Inject links into the top navigation
	 *
	 * @param Mage_Core_Model_Resource_Db_Collection_Abstract $items
	 * @param Varien_Data_Tree_Node $parentNode
	 * @return bool
	 */
	protected function _injectLinks($items, $parentNode)
	{
		if (!$parentNode) {
			return false;	
		}
		
		foreach($items as $item) {
			if (!$item->canIncludeInMenu()) {
				continue;
			}

			$children = $item->getSplashPages();
			
			if ($children && count($children->addOrderByName()->addFieldToFilter('include_in_menu', 1)->load()) === 0) {
				continue;
			}

			$data = array(
				'name' => $item->getName(),
				'id' => $item->getMenuNodeId(),
				'url' => $item->getUrl(),
				'is_active' => $item->isActive(),
			);
			
			if ($data['is_active']) {
				$parentNode->setIsActive(true);
				$buffer = $parentNode;
				
				while($buffer->getParent()) {
					$buffer = $buffer->getParent();
					$buffer->setIsActive(true);
				}
			}
			
			$itemNode = new Varien_Data_Tree_Node($data, 'id', $parentNode->getTree(), $parentNode);
			$parentNode->addChild($itemNode);
			
			if ($children) {
				$this->_injectLinks($children, $itemNode);
			}
		}
		
		return true;
	}
}
