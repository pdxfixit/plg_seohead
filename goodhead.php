<?php

defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');

class plgSystemGoodHead extends JPlugin {

    function plgSystemGoodHead(&$subject, $config) {
        parent::__construct($subject, $config);

        $this->_plugin = JPluginHelper::getPlugin('system', 'goodhead');
        $this->_params = new JParameter($this->_plugin->params);
    }

    /**
     * Method to catch the onAfterDispatch event.
     *
     * This is where we setup the click-through content highlighting for.
     * The highlighting is done with JavaScript so we just
     * need to check a few parameters and the JHtml behavior will do the rest.
     *
     * @return  boolean  True on success
     *
     * @since   2.5
     */
    public function onAfterDispatch() {
        // Check that we are in the site application.
        if (JFactory::getApplication()->isAdmin()) {
            return true;
        }

        // Set the variables
        $input = JFactory::getApplication()->input;
        $extension = $input->get('option', '', 'cmd');

        // Check if the highlighter is enabled.
        if (!JComponentHelper::getParams($extension)->get('highlight_terms', 1)) {
            return true;
        }

        // Check if the highlighter should be activated in this environment.
        if (JFactory::getDocument()->getType() !== 'html' || $input->get('tmpl', '', 'cmd') === 'component') {
            return true;
        }

        // Get the terms to highlight from the request.
        $terms = $input->request->get('highlight', null, 'base64');
        $terms = $terms ? unserialize(base64_decode($terms)) : null;

        // Check the terms.
        if (empty($terms)) {
            return true;
        }

        // Clean the terms array
        $filter = JFilterInput::getInstance();

        $cleanTerms = array();
        foreach ($terms as $term) {
            $cleanTerms[] = $filter->clean($term, 'string');
        }

        // Activate the highlighter.
        JHtml::_('behavior.highlighter', $cleanTerms);

        // Adjust the component buffer.
        $doc = JFactory::getDocument();
        $buf = $doc->getBuffer('component');
        $buf = '<br id="highlighter-start" />' . $buf . '<br id="highlighter-end" />';
        $doc->setBuffer($buf, 'component');

        return true;
    }

    function onAfterRender() {
        $mainframe = JFactory::getApplication();

        $web_property_id = $this->params->get('web_property_id', '');
        //do some logic to add UA- if not already there

        if ($web_property_id == '' || $mainframe->isAdmin() || strpos($_SERVER["PHP_SELF"], "index.php") === false) {
            return;
        }

        $buffer = JResponse::getBody();

        $google_analytics_javascript = '<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(["_setAccount", "' . $web_property_id . '"]);
  _gaq.push(["_trackPageview"]);

  (function() {
    var ga = document.createElement("script"); ga.type = "text/javascript"; ga.async = true;
    ga.src = ("https:" == document.location.protocol ? "https://ssl" : "http://www") + ".google-analytics.com/ga.js";
    var s = document.getElementsByTagName("script")[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>';

        $pos = strrpos($buffer, "</head>");

        if ($pos > 0) {
            $buffer = substr($buffer, 0, $pos) . $google_analytics_javascript . substr($buffer, $pos);

            JResponse::setBody($buffer);
        }

        return true;
    }

}
