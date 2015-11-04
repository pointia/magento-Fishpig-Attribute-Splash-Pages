<?php
/**
 * @category    Fishpig
 * @package     Fishpig_AttributeSplash
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_AttributeSplash_PageController extends Mage_Core_Controller_Front_Action
{
	public function viewAction()
	{
		if ($splashPage = $this->_initSplashPage()) {
			$this->_applyCustomViewLayout($splashPage);
			$this->_setCurrentCategory();
			$this->_injectNavigation();

			if ($rootBlock = $this->getLayout()->getBlock('root')) {
				$rootBlock->addBodyClass('splash-page-' . $splashPage->getId());

				if ($splashPage->getAttributeCode()) {
					$rootBlock->addBodyClass('splash-page-' . $splashPage->getAttributeCode());
				}
			}

			$this->renderLayout();
		}
		else {
			$this->_forward('noRoute');
		}
	}
	
	/**
	 * Apply custom layout handles to the splash page
	 *
	 * @param Fishpig_AttribtueSplash_Model_Page $splashPage
	 * @return Fishpig_AttribtueSplash_PageController
	 */
	protected function _applyCustomViewLayout(Fishpig_AttributeSplash_Model_Page $splashPage)
	{
		$update = $this->getLayout()->getUpdate();
		
		$update->addHandle('default');
		$this->addActionLayoutHandles();
		$update->addHandle('attributesplash_page_view_' . $splashPage->getId());
		$update->addHandle('attributesplash_page_view_' . $splashPage->getAttributeModel()->getAttributeCode());

		$this->loadLayoutUpdates();
		
		$update->addUpdate($splashPage->getLayoutUpdateXml());

		$this->generateLayoutXml()->generateLayoutBlocks();

		$this->_isLayoutLoaded = true;

		return $this;	
	}
	
	/**
	 * Sets the current category as the current stores default category
	 */
	protected function _setCurrentCategory()
	{
		if (!Mage::registry('current_category')) {
			Mage::register('current_category', Mage::helper('attributeSplash')->getBaseSplashCategory(), true);
		}
		
		return $this;
	}
	
	/**
	 * Initialise the Splash Page model
	 *
	 * @return false|Fishpig_AttributeSplash_Model_Page
	 */
	protected function _initSplashPage()
	{
		Mage::dispatchEvent('attributeSplash_controller_page_init_before', array('controller_action' => $this));
		
		$splashPageId = (int) $this->getRequest()->getParam('id', false);

		if (!$splashPageId) {
			return false;
		}
		
		$splashPage = Mage::getModel('attributeSplash/page')->setStoreId(Mage::app()->getStore()->getId())->load($splashPageId);
		
		if (!$splashPage->canDisplay()) {
			return false;
		}
		
		Mage::register('splash_page', $splashPage);
		
		if ($group = $splashPage->getSplashGroup()) {
			Mage::register('splash_group', $group);	
		}

		try {
			Mage::dispatchEvent('attributeSplash_controller_page_init_after', array('page' => $splashPage, 'splash_page' => $splashPage, 'controller_action' => $this));		
		}
		catch(Mage_Core_Exception $e) {
			Mage::helper('attributeSplash')->log($e->getMessage());
			return false;
		}

		return $splashPage;
	}
	
	/**
	 * Injects the navigation into the page
	 * The navigation should be selected in the module config and NOT via XML
	 */
	protected function _injectNavigation()
	{
		$navOption = Mage::getStoreConfig('attributeSplash/frontend/navigation');

		if ($navOption == Fishpig_AttributeSplash_Model_System_Config_Source_Navigation::USE_LAYERED_NAVIGATION) {
			$blockType = 'attributeSplash/layer_view';
			$defaultTemplate = 'catalog/layer/view.phtml';
		}
		elseif ($navOption == Fishpig_AttributeSplash_Model_System_Config_Source_Navigation::USE_CATALOG_NAVIGATION) {
			$blockType = 'catalog/navigation';
			$defaultTemplate = 'catalog/navigation/left.phtml';
		}
		else {
			return $this;
		}

		if ($navContainer = $this->_getNavigationContainer()) {
			if ($navContainerBlock = $this->getLayout()->getBlock($navContainer)) {
				$template = ($configTemplate = Mage::getStoreConfig('attributeSplash/frontend/navigation_template')) ? $configTemplate : $defaultTemplate;
				$block = $this->getLayout()->createBlock($blockType, 'catalog.leftnav', array('template' => $template, 'before' => '-'));

				if ($block) {
					$block->setTemplate($template);
					$navContainerBlock->insert($block);
				}
			}
		}
		
		return $this;
	}

	/**
	 * Gets the block reference name for the navigation
	 * Uses the following hierarchy:
	 * Custom block name (config), auto detect from template code, 'left'
	 *
	 * @return string
	 */
	protected function _getNavigationContainer()
	{
		if ($container = trim(Mage::getStoreConfig('attributeSplash/frontend/navigation_container'))) {
			return $container;
		}
		
		$template = Mage::getStoreConfig('attributeSplash/frontend/template');
		
		foreach(array('left', 'right') as $side) {
			if (strpos($template, $side) !== false) {
				return $side;
			}
		}
		
		return 'left';
	}
}
