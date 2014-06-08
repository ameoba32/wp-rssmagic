<?php
/**
 * RSS_Base.php, base class for all plugin classes, contain common code
 *
 * @author     Constantin Bosneaga <constantin@bosneaga.com>
 * @copyright  2013-2014 The Author
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 */

class RSS_Base {

    /**
     * Database table prefix
     *
     * @var
     */
    protected $_tablePrefix = 'wp_rssmagic';

    /**
     * Default layout for rendering
     *
     * @var string
     */
    private $_layout = 'layout';

    /**
     * Global template variables
     *
     * @var array
     */
    private $_templateVariable = array();


    /**
     * Set global variable to be used in all templates
     *
     * @param string $variable Variable name
     * @param mixed  $value    Variable value
     */
    function setTemplateVariable($variable, $value) {
        $this->_templateVariable[$variable] = $value;
    }

    /**
     * Render template using layout and variables
     *
     * @param string $template     Template name
     * @param array  $variableList Variables
     */
    function render($template, $variableList = array()) {
        ob_start();

        extract($this->_templateVariable);
        extract($variableList);

        // Pass plugin variable
        $plugin = RSS_Plugin::getInstance();

        require_once join (
            DIRECTORY_SEPARATOR,
            array(
                $this->_rootDir,
                'template',
                $template . '.php'
            )
        );
        $content = ob_get_clean();

        include_once join (
            DIRECTORY_SEPARATOR,
            array(
                $this->_rootDir,
                'template',
                $this->_layout . '.php'
            )
        );
    }

    /**
     * Send standardized AJAX response to browser
     *
     * @param int    $code     Return code
     * @param string $message  Error message
     * @param array  $data     Any data to pass
     * @param string $redirect URL to redirect after AJAX call
     */
    function ajaxResponse($code, $message, $data = array(), $redirect = '') {
        $response = array(
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'redirect' => $redirect,
        );
        wp_send_json($response);
    }

    /**
     * Check nonce for AJAX forms
     *
     * @param string $nonce Nonce string
     */
    function verifyNonce($nonce) {
        if (!wp_verify_nonce($_POST['_wpnonce'])) {
            $this->ajaxResponse(-1, 'Invalid nonce');
        }
    }

    /**
     * Database table prefix
     *
     * @return string
     */
    public function getTableName($name) {
        return $this->_tablePrefix . '_' .$name;
    }

    /**
     * Get plugin options
     *
     * @param string $name    Option name
     * @param mixed  $default Default value, if no option was found
     * @return null
     */
    function getOption($name, $default = null) {
        $options = get_option($this->_tablePrefix.'_options');
        if (isset($options[$name])) {
            return $options[$name];
        } else {
            return $default;
        }
    }

    /**
     * Set plugin options
     *
     * @param string $name  Option name
     * @param mixed  $value Data
     */
    function setOption($name, $value) {
        $options = get_option($this->_tablePrefix.'_options');
        if (!is_array($options)) {
            $options = array();
        }
        $options[$name] = $value;
        update_option($this->_tablePrefix.'_options', $options);
    }

    /**
     * Returns external variable from request (GET, POST)
     *
     * @param null $variable
     * @return array|null
     */
    function request($variable = null) {
        static $request;

        if ($request === null) {
            $request = array_map('stripslashes_deep', $_REQUEST);
        }

        if ($variable == null) {
            return $request;
        }

        if (isset($request[$variable])) {
            return $request[$variable];
        } else {
            return null;
        }
    }

    /**
     * Shortcut to Wordpress database object
     *
     * @return \wpdb
     */
    function db() {
        global $wpdb;
        return $wpdb;
    }

    /**
     * Helper to generate page name for admin menu
     *
     * @param $slug
     * @return string
     */
    function pageName($slug) {
        return $this->_pluginPrefix . '_' . $slug;
    }

    /**
     * Returns plugin name
     *
     * @return string
     */
    public function getPluginName() {
        return $this->_pluginName;
    }
}

