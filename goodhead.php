<?php

defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');

/**
 * Plugin class for adding extra fields to the HTML <head>.
 *
 * @package	Joomla.Plugin
 * @subpackage	System.goodhead
 */
class plgSystemGoodHead extends JPlugin {

    private $_fields = array('basic', 'opengraph', 'dc', 'other');
    protected $_head = null;

    /**
     * Object Constructor.
     *
     * @access	public
     * @param	object	The object to observe -- event dispatcher.
     * @param	object	The configuration object for the plugin.
     * @return	void
     * @since	1.0
     */
    public function plgSystemGoodHead(&$subject, $config) {
        parent::__construct($subject, $config);

        $this->_plugin = JPluginHelper::getPlugin('system', 'goodhead');
        
        if (strpos(JVERSION, '2.5')) { // Joomla! 2.5
            $this->_params = new JParameter($this->_plugin->params);
        } else { // In Joomla! Platform 12.1, JParameter has been replaced with JForm.
            $this->_params = new JForm($this->_plugin->params);
        }
    }

    /**
     * Method to collect data from the parameters.
     * 
     * @access  private
     * @return  void
     * @since   1.0
     */
    private function collectData() {
        foreach ($this->_fields as $field) {
            $data[$field] = $this->params->get($field . '_field', '');
        }

        foreach ($data as $name => $content) {
            $this->_head .= $this->metaTag($name, $content);
        }
    }

    /**
     * Method to create the meta tags.
     *
     * @access  private
     * @param   string  Name for the meta tag
     * @param   string  Content for the meta tag
     * @return  string  Meta tag
     * @since   1.0
     */
    private function metaTag($name, $content) {
        return '<meta name="' . $name . '" content="' . $content . '" />' . "\n  ";
    }

    /**
     * Method to catch the onAfterRender event.
     *
     * @access  public
     * @return  boolean  True on success
     * @since   1.0
     */
    public function onAfterRender() {
        // Set the variables
        $input = JFactory::getApplication()->input;

        // Use this plugin only in site application, and check if we should insert data into the html header
        if (JFactory::getApplication()->isAdmin() || JFactory::getDocument()->getType() !== 'html' || $input->get('tmpl', '', 'cmd') === 'component') {
            return true;
        }

        // Collect the data
        $this->collectData();

        // Adjust the buffer.
        $buffer = JResponse::getBody();
        $pos = strrpos($buffer, "<title>");
        $buffer = substr($buffer, 0, $pos) . $this->_head . substr($buffer, $pos);
        JResponse::setBody($buffer);

        return true;
    }

}
