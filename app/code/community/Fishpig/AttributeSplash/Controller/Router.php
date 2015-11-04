<?php
/**
 * @category    Fishpig
 * @package    Fishpig_AttributeSplash
 * @license      http://fishpig.co.uk/license.txt
 * @author       Ben Tideswell <ben@fishpig.co.uk>
 */

class Fishpig_AttributeSplash_Controller_Router extends Mage_Core_Controller_Varien_Router_Abstract
{
	/**
	 * Initialize Controller Router
	 *
	 * @param Varien_Event_Observer $observer
	*/
	public function initControllerRouters(Varien_Event_Observer $observer)
	{
		$observer->getEvent()->getFront()->addRouter('attributeSplash', $this);
	}

    /**
     * Validate and Match Cms Page and modify request
     *
     * @param Zend_Controller_Request_Http $request
     * @return bool
     */
    public function match(Zend_Controller_Request_Http $request)
    {

		$this->_request = $request;
		
		if ($this->_match() !== false) {
			$request->setAlias(
				Mage_Core_Model_Url_Rewrite::REWRITE_REQUEST_PATH_ALIAS,
				ltrim($request->getPathInfo(), '/')
			);

			return true;
		}

		return false;
	}
	
	/**
	 * Match the request against all enabled splash routes
	 *
	 * @return bool
	 */
	protected function _match()
	{
		$requestUri = trim($this->_request->getPathInfo(), '/');
		$isDoubleBarrel = strpos($requestUri, '/') !== false;
		$includeGroupUrlKey = Mage::getStoreConfigFlag('attributeSplash/page/include_group_url_key');
		$urlSuffix = rtrim(Mage::getStoreConfig('attributeSplash/seo/url_suffix'), '/');
		
		if ($urlSuffix) {
			if (substr($requestUri, -strlen($urlSuffix)) !== $urlSuffix) {
				return false;
			}
			
			$requestUri = substr($requestUri, 0, -strlen($urlSuffix));
		}

		if ($isDoubleBarrel) {
			// Must be splash page

			if (!$includeGroupUrlKey) {
				// URL contains / but no group key so should be single
				return false;
			}
			
			list($groupUrlKey, $pageUrlKey) = explode('/', $requestUri);
			
			return $this->_loadSplashPage($pageUrlKey, $groupUrlKey);
		}
		
		if ($this->_loadSplashPage($requestUri)) {
			return true;
		}

		return $this->_loadSplashGroup($requestUri);
	}
	
	/**
	 * Load a splash page by it's URL key
	 * If the group URL key is present, this must match
	 *
	 * @param string $pageUrlKey
	 * @param string $groupUrlKey = null
	 * @return bool
	 */
	protected function _loadSplashPage($pageUrlKey, $groupUrlKey = null)
	{
		$pages = Mage::getResourceModel('attributeSplash/page_collection')
			->addStoreFilter(Mage::app()->getStore()->getId())
			->addFieldToFilter('url_key', $pageUrlKey)
			->load();

		if (count($pages) === 0) {
			return false;
		}

		$page = false;

		foreach($pages as $object) {
			if (!$object->getIsEnabled() || !$object->getSplashGroup()) {
				continue;
			}
			
			if (!is_null($groupUrlKey) && $object->getSplashGroup()->getUrlKey() !== $groupUrlKey) {
				continue;
			}
			
			$page = $object;

			break;
		}
		
		if (!$page) {
			return false;
		}

		Mage::register('splash_page', $page);
		Mage::register('splash_group', $page->getSplashGroup());
		
		$this->_request->setModuleName('splash')
			->setControllerName('page')
			->setActionName('view')
			->setParam('id', $page->getId())
			->setParam('group_id', $page->getSplashGroup()->getId());
			
		return true;
	}
	
	/**
	 * Load a splash group by it's URL key
	 *
	 * @param string $groupUrlKey
	 * @return bool
	 */
	protected function _loadSplashGroup($groupUrlKey)
	{
		$group = Mage::getModel('attributeSplash/group')
			->setStoreId(Mage::app()->getStore()->getId())
			->load($groupUrlKey, 'url_key');
		
		
		if (!$group->getId()) {
			return false;
		}
		
		Mage::register('splash_group', $group);
		
		$this->_request->setModuleName('splash')
			->setControllerName('group')
			->setActionName('view')
			->setParam('id', $group->getId());
			
		return true;
	}
}
