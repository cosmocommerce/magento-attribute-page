<?php
/**
 * @category    Fishpig
 * @package     Fishpig_AttributeSplash
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */
	
	$this->startSetup();

	$this->getConnection()->addColumn($this->getTable('attributesplash_group'), 'layout_update_xml', " TEXT NOT NULL default ''");
	$this->getConnection()->addColumn($this->getTable('attributesplash_page'), 'is_featured', " int(1) unsigned NOT NULL default 0");
	
	$this->endSetup();
