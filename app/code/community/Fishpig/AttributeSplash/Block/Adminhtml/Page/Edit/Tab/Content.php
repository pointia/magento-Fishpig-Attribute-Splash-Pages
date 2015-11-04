<?php
/**
 * @category    Fishpig
 * @package     Fishpig_AttributeSplash
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_AttributeSplash_Block_Adminhtml_Page_Edit_Tab_Content extends Fishpig_AttributeSplash_Block_Adminhtml_Page_Edit_Tab_Abstract
{
	/**
	 * Prepare the form
	 *
	 * @return $this
	 */
	protected function _prepareForm()
	{
		parent::_prepareForm();
		
		$fieldset = $this->getForm()->addFieldset('splash_page_content', array(
			'legend'=> $this->helper('adminhtml')->__('Content'),
			'class' => 'fieldset-wide',
		));

		$htmlConfig = Mage::getSingleton('cms/wysiwyg_config')->getConfig(array(
			'add_widgets' => false,
			'add_variables' => false,
			'add_image' => false,
			'files_browser_window_url' => $this->getUrl('adminhtml/cms_wysiwyg_images/index')
		));

		$fieldset->addField('short_description', 'editor', array(
			'name' => 'short_description',
			'label' => $this->helper('adminhtml')->__('Short Description'),
			'title' => $this->helper('adminhtml')->__('Short Description'),
			'style' => 'width:100%; height:200px;'
		));
		
		$fieldset->addField('description', 'editor', array(
			'name' => 'description',
			'label' => $this->helper('adminhtml')->__('Description'),
			'title' => $this->helper('adminhtml')->__('Description'),
			'style' => 'width:100%; height:400px;',
			'config' => $htmlConfig,
		));

		$this->getForm()->setValues($this->_getFormData());

		return $this;
	}
}