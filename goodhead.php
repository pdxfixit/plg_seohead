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

    private $_fields = array();
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
        $this->_params = new JForm($this->_plugin->params);
        $this->getFieldsFromXML();
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
     * Method to get fields from the XML file for simplicity.
     *
     * @access  private
     * @return  void
     * @since   1.0
     */
    private function getFieldsFromXML() {
        // get the filename of the XML manifest
        $file = explode(DIRECTORY_SEPARATOR, __FILE__);
        array_pop($file);
        $file[] = 'goodhead.xml';
        $file = implode(DIRECTORY_SEPARATOR, $file);

        // parse the XML manifest, so we know what the fields are.
        $dom = new DOMDocument;
        $dom->loadXML(file_get_contents($file));
        if ($dom) {
            $xml = simplexml_import_dom($dom);
            $fieldsetsObject = (array) $xml->config[0]->fields[0];
            $fieldsets = (array) $fieldsetsObject['fieldset'];
            foreach ($fieldsets as $fieldsetObject) {
                $fieldset = (array) $fieldsetObject;
                unset($fieldset['@attributes']);
                
                if (is_array($fieldset['field'])) {
                    $preparedFieldset = $fieldset['field'];
                } else {
                    $preparedFieldset = array();
                    $preparedFieldset[] = $fieldset['field'];
                }
                
                foreach ($preparedFieldset as $fieldObject) {
                    $fieldArray = (array) $fieldObject;
                    $field = $fieldArray['@attributes'];
                    $this->_fields[] = (string) $field['name'];
                }
            }
            die(var_export($this->_fields, 1));
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
