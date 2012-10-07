<?php

defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');

class plgSystemGoodHead extends JPlugin {

    private $_fields = array('basic', 'opengraph', 'dc', 'other');
    protected $_head = null;

    public function plgSystemGoodHead(&$subject, $config) {
        parent::__construct($subject, $config);

        $this->_plugin = JPluginHelper::getPlugin('system', 'goodhead');
        $this->_params = new JParameter($this->_plugin->params);
    }

    private function collectData() {
        foreach ($this->_fields as $field) {
            $data[$field] = $this->params->get($field . '_field', '');
        }

        foreach ($data as $name => $content) {
            $this->_head .= $this->metaTag($name, $content);
        }
    }

    private function metaTag($name, $content) {
        return '<meta name="' . $name . '" content="' . $content . '" />' . "\n  ";
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
        $this->collectData();

        // Adjust the buffer.
        $buffer = JResponse::getBody();
        $pos = strrpos($buffer, "<title>");
        $buffer = substr($buffer, 0, $pos) . $this->_head . substr($buffer, $pos);
        JResponse::setBody($buffer);

        return true;
    }

}
