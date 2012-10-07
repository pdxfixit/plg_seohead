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

    private $_currentURI = null;
    private $_documentTitle = null;
    private $_documentType = null;
    private $_dublinCoreFlag = false;
    private $_fields = array();
    private $_locale = null;
    private $_metaDesc = null;
    private $_metaKeys = null;
    private $_openGraphFlag = false;
    private $_siteName = null;
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

        // Set variables
        $app = & JFactory::getApplication();
        $doc = & JFactory::getDocument();
        $this->_currentURI = JURI::current();
        $this->_locale = $this->prepareLocaleTag($doc->getLanguage());
        $this->_documentType = $doc->getType();
        $this->_dublinCoreFlag = $this->params->get('dublincore_flag', 0);
        $this->_metaDesc = $app->getCfg('MetaDesc');
        $this->_metaKeys = $app->getCfg('MetaKeys');
        $this->_openGraphFlag = $this->params->get('opengraph_flag', 0);
        $this->_siteName = $app->getCfg('sitename');

        // Collect the data
        $this->getFieldsFromXML();
    }

    /**
     * Method to prepare and create Dublin Core tags.
     * Reference: http://dublincore.org/documents/dc-html/
     *
     * @access  private
     * @return  void
     * @since   1.0
     */
    private function generateDublinCoreTags() {
        $this->_head .= $this->linkTag('schema.DC', 'http://purl.org/dc/elements/1.1/');
        $this->_head .= $this->linkTag('schema.DCTERMS', 'http://purl.org/dc/terms/');
        $this->_head .= $this->metaTag('DC.title', $this->_documentTitle);
        $this->_head .= $this->metaTag('DC.creator', $this->params->get('business'));
        $this->_head .= $this->metaTag('DC.subject', $this->_metaKeys);
        $this->_head .= $this->metaTag('DC.description', $this->_metaDesc);
        $this->_head .= $this->metaTag('DC.publisher', $this->params->get('business'));
        $this->_head .= $this->metaTag('DC.publisher.address', $this->params->get('email'));
        $this->_head .= $this->metaTag('DC.contributor', $this->params->get('contact'));
        $this->_head .= $this->metaTag('DC.type', $this->params->get('Text'), 'DCTERMS.DCMIType');
        $this->_head .= $this->metaTag('DC.format', 'text/html');
        $this->_head .= $this->metaTag('DC.identifier', $this->_currentURI, 'DCTERMS.DCMIType');
        $this->_head .= $this->metaTag('DC.rights', 'Copyright &copy; ' . $this->params->get('established') . ' &ndash; ' . date('Y') . ', ' . $this->params->get('business') . '. All rights reserved.');
    }

    /**
     * Method to prepare and create <link> tags.
     *
     * @access  private
     * @return  void
     * @since   1.0
     */
    private function generateLinkTags() {
        $this->_head .= $this->linkTag('canonical', $this->_currentURI);
    }

    /**
     * Method to prepare and create Open Graph tags.
     * Reference: http://ogp.me/
     * Reference: https://developers.facebook.com/docs/opengraphprotocol/
     *
     * @access  private
     * @return  void
     * @since   1.0
     */
    private function generateOpenGraphTags() {
        if ($_SERVER['REQUEST_URI'] == '/') {
            // for the root of the domain only
            $type = 'website';
        } else {
            $type = $this->params->get('opengraph_type', 'article');
        }

        // Set variables
        $imagePath = $this->_currentURI . 'images/' . $this->params->get('image');
        $imageData = getimagesize($imagePath);

        // required
        $this->_head .= $this->metaTag('og:url', $this->_currentURI, null, true);
        $this->_head .= $this->metaTag('og:title', $this->_documentTitle, null, true);
        $this->_head .= $this->metaTag('og:type', $type, null, true);
        $this->_head .= $this->metaTag('og:image:url', $imagePath, null, true);
        $this->_head .= $this->metaTag('og:image:type', $imageData['mime'], null, true);
        $this->_head .= $this->metaTag('og:image:height', $imageData[1], null, true);
        $this->_head .= $this->metaTag('og:image:width', $imageData[0], null, true);
        // FUTURE FEATURE: Support og:image:secure_url
        // optional
        $this->_head .= $this->metaTag('og:audio', $this->params->get('audio'), null, true);
        $this->_head .= $this->metaTag('og:description', $this->_metaDesc, null, true);
        // The word that appears before this object's title in a sentence.
        // An enum of (a, an, the, "", auto). If auto is chosen, the consumer
        // of your data should chose between "a" or "an". Default is "" (blank).
        // $this->_head .= $this->metaTag('og:determiner', null, null, true);
        $this->_head .= $this->metaTag('og:locale', $this->_locale, null, true);
        // FUTURE FEATURE: detect available langauge versions of the current page, and list them here
        // $this->_head .= $this->metaTag('og:locale:alternate', $this->_otherLocales, null, true);
        $this->_head .= $this->metaTag('og:site_name', $this->_siteName, null, true);
        $this->_head .= $this->metaTag('og:video', $this->params->get('video'), null, true);

        // other
        $this->_head .= $this->metaTag('og:email', $this->params->get('email'), null, true);
        $this->_head .= $this->metaTag('og:phone_number', $this->params->get('phone'), null, true);
        $this->_head .= $this->metaTag('og:fax_number', $this->params->get('fax'), null, true);
        // To associate the page with your Facebook account, add the additional
        // property fb:admins to your page with a comma-separated list of the
        // user IDs or usernames of the Facebook accounts who own the page.
        // See: https://developers.facebook.com/docs/opengraphprotocol/
        // $this->_head .= $this->metaTag('fb:admins', null, null, true);
        $this->_head .= $this->metaTag('og:latitude', $this->params->get('latitude'), null, true);
        $this->_head .= $this->metaTag('og:longitude', $this->params->get('longitude'), null, true);
        $this->_head .= $this->metaTag('og:street-address', $this->params->get('address'), null, true);
        $this->_head .= $this->metaTag('og:locality', $this->params->get('city'), null, true);
        $this->_head .= $this->metaTag('og:region', $this->params->get('state'), null, true);
        $this->_head .= $this->metaTag('og:postal-code', $this->params->get('zip'), null, true);
        $this->_head .= $this->metaTag('og:country-name', $this->params->get('country'), null, true);
    }

    /**
     * Method to prepare and create all other supported meta tags.
     *
     * @access  private
     * @return  void
     * @since   1.0
     */
    private function generateOtherMetaTags() {
        $lat = $this->params->get('latitude');
        $long = $this->params->get('longitude');
        if (!empty($lat) && !empty($long)) {
            $this->_head .= $this->metaTag('ICBM', $lat . ', ' . $long);
            $this->_head .= $this->metaTag('geo.position', $lat . ';' . $long);
        }
        $this->_head .= $this->metaTag('geo.placename', $this->params->get('city') . ', ' . $this->params->get('state') . ' ' . $this->params->get('country'));
        $this->_head .= $this->metaTag('google-site-verification', $this->params->get('googleverify'));
        $this->_head .= $this->metaTag('alexaVerifyID', $this->params->get('alexaverify'));
        $this->_head .= $this->metaTag('blogcatalog', $this->params->get('blogcatalog'));
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
        $pathParts = explode(DIRECTORY_SEPARATOR, __FILE__);
        $scriptFilename = array_pop($pathParts);
        $filenameParts = explode('.', $scriptFilename);
        $pathParts[] = implode('.', array($filenameParts[0], 'xml'));
        $filePath = implode(DIRECTORY_SEPARATOR, $pathParts);

        // parse the XML manifest, so we know what the fields are.
        $dom = new DOMDocument;
        $dom->loadXML(file_get_contents($filePath));
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
        }
    }

    /**
     * Method to create link tags.
     *
     * @access  private
     * @param   string  Rel attribute for the link tag
     * @param   string  Href attribute for the link tag
     * @return  string  Link tag
     * @since   1.0
     */
    private function linkTag($rel, $href) {
        return '<link rel="' . $rel . '" href="' . $href . '" />' . "\n  ";
    }

    /**
     * Method to create meta tags.
     *
     * @access  private
     * @param   string  Name attribute for the meta tag
     * @param   string  Content attribute for the meta tag
     * @param   string  Scheme attribute for the meta tag
     * @param   boolean Flag used for OpenGraph meta tags. changes "name" attribute to "property"
     * @return  string  Meta tag
     * @since   1.0
     */
    private function metaTag($name, $content, $scheme = null, $openGraph = false) {
        if (empty($content)) {
            return;
        }

        if ($openGraph === true) {
            $attribute = 'property';
        } else {
            $attribute = 'name';
        }

        if (!empty($scheme)) {
            $scheme = ' scheme="' . $scheme . '"';
        }

        return '<meta ' . $attribute . '="' . $name . '" content="' . $content . '"' . $scheme . ' />' . "\n  ";
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
        $buffer = JResponse::getBody();
        $input = JFactory::getApplication()->input;
        $this->_documentTitle = JFactory::getDocument()->getTitle();

        // Use this plugin only in site application, and check if we should insert data into the html header
        if (JFactory::getApplication()->isAdmin() || JFactory::getDocument()->getType() !== 'html' || $input->get('tmpl', '', 'cmd') === 'component') {
            return true;
        }

        // Prepare the data.
        $this->generateLinkTags();

        if ($this->_openGraphFlag) {
            // Add the xmlns:og to the <html> tag
            $patterns = array(chr(1) . '(<html.*)(>)' . chr(1) . 'i');
            $replace = array('${1}' . ' xmlns:og="http://ogp.me/ns#"' . '${2}');
            $buffer = preg_replace($patterns, $replace, $buffer);

            $this->generateOpenGraphTags();
        }

        if ($this->_dublinCoreFlag) {
            // Add the profile attribute to the <head> tag
            $patterns = array(chr(1) . '(<head.*)(>)' . chr(1) . 'i');
            $replace = array('${1}' . ' profile="http://dublincore.org/documents/2008/08/04/dc-html/"' . '${2}');
            $buffer = preg_replace($patterns, $replace, $buffer);

            $this->generateDublinCoreTags();
        }
        #$this->prepareData();

        $this->generateOtherMetaTags();

        // Get custom meta data
        $other = $this->params->get('other');
        // TODO: SANITIZE $other
        $this->_head .= $other;

        // Adjust the buffer.
        $pos = strrpos($buffer, "<title>");
        // FUTURE FEATURE: Double-check that there aren't any duplicate tags.
        $buffer = substr($buffer, 0, $pos) . $this->_head . substr($buffer, $pos);
        JResponse::setBody($buffer);

        return true;
    }

    /**
     * Method to prepare data for injection.
     *
     * @access  public
     * @return  void
     * @since   1.0
     */
    private function prepareData() {
        foreach ($this->_fields as $field) {
            $data[$field] = $this->params->get($field . '_field', '');
        }

        foreach ($data as $name => $content) {
            $this->_head .= $this->metaTag($name, $content);
        }
    }

    /**
     * Method to prepare data for injection.
     *
     * @access  public
     * @return  void
     * @since   1.0
     */
    private function prepareLocaleTag($before) {
        $parts = explode('-', $before);

        if (strtolower($parts[1]) == 'gb' && strtolower(substr($this->params->get('country'), 2)) == 'us') {
            $parts[1] = 'US';
        } else {
            $parts[1] = strtoupper($parts[1]);
        }

        $after = implode('_', $parts);

        return $after;
    }

}
