<?php

defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');

class plgSystemGoodHead extends JPlugin {
    
    protected $_head = null;

    public function plgSystemGoodHead(&$subject, $config) {
        parent::__construct($subject, $config);

        $this->_plugin = JPluginHelper::getPlugin('system', 'goodhead');
        $this->_params = new JParameter($this->_plugin->params);
    }

    /**
     * Method to catch the onAfterRender event.
     *
     * @return  boolean  True on success
     *
     * @since   2.5
     */
    public function onAfterRender() {
        // Set the variables
        $input = JFactory::getApplication()->input;

        // Use this plugin only in site application, and check if we should insert data into the html header
        if (JFactory::getApplication()->isAdmin() || JFactory::getDocument()->getType() !== 'html' || $input->get('tmpl', '', 'cmd') === 'component') {
            return true;
        }
        
        // Collect the data
        
        
        // Generate the HTML
        $this->_head = '<ben saidso="true" />';

        // Adjust the buffer.
        $buffer = JResponse::getBody();
        $pos = strrpos($buffer, "<title>");
        $buffer = substr($buffer, 0, $pos) . $this->_head . "\n  " . substr($buffer, $pos);
        JResponse::setBody($buffer);

        return true;

//        $web_property_id = $this->params->get('web_property_id', '');
    }

}
