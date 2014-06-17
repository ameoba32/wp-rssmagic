<?php
/**
 * RSS_Plugin.php, Main class setup Wordpress hooks
 *
 * @author     Constantin Bosneaga <constantin@bosneaga.com>
 * @copyright  2013-2014 The Author
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 */

class RSS_Plugin extends RSS_Base {

    /**
     * Plugin root file
     *
     * @var
     */
    protected $_rootFile;

    /**
     * Plugin root directory
     *
     * @var
     */
    protected $_rootDir;

    /**
     * Plugin root url
     *
     * @var
     */
    protected $_rootUrl;

    /**
     * Plugin prefix
     *
     * @var
     */
    protected $_pluginPrefix = 'rssmagic';

    /**
     * Plugin name
     *
     * @var
     */
    protected $_pluginName = 'RSS Digest';


    protected static $_instance;

    /**
     * Factory
     */
    public static function build($rootFile) {
        self::$_instance = new RSS_Plugin($rootFile);
        self::$_instance->init();

        return self::$_instance;
    }

    /**
     * @return \RSS_Plugin
     */
    public static function getInstance() {
        return self::$_instance;
    }

    /**
     * Constructor
     *
     * @param $rootFile Main plugin file
     */
    function __construct($rootFile) {
        $this->_rootFile = $rootFile;
        $this->_rootDir = dirname($rootFile);
        $this->_rootUrl = plugin_dir_url($rootFile);
    }

    /**
     * Init plugin
     */
    function init() {
        //session_start();
        add_action('admin_menu', array($this, 'wp_admin_menu'));
        add_action('admin_init', array($this, 'wp_admin_init'));
        add_action('wp_ajax_rssmagic', array($this, 'wp_ajax_rssmagic') );
        add_action('admin_enqueue_scripts', array($this, 'wp_enqueue_scripts'));
        add_filter( 'plugin_action_links_' . plugin_basename($this->_rootFile), array($this, 'wp_action_links'));

        add_action( $this->_tablePrefix . '_hourly_cron', array('RSS_Cron', 'hourly'));

        register_activation_hook($this->_rootFile, array($this, 'wp_activation_hook'));
    }

    /**
     * Registers admin menu for setup
     *
     * @return void
     */
    function wp_admin_menu() {
        add_submenu_page(
            'tools.php',
            'RssMagic',
            'RssMagic',
            'manage_options',
            $this->pageName('setup'),
            array($this, 'wp_setup')
        );
    }

    /**
     * After admin part is init
     *
     * @return void
     */
    function wp_admin_init() {
        $installer = new RSS_Install();
        $installer->checkForUpgrade();
    }

    /**
     * Assets for admin interface
     *
     * @return void
     */
    function wp_enqueue_scripts() {
        wp_enqueue_script('rss-admin-default', $this->_rootUrl . 'assets/js/default.js', array('jquery'));
        wp_enqueue_script('rss-admin-deserialize', $this->_rootUrl . 'assets/js/jquery.deserialize.js', array('jquery'));
        wp_enqueue_style('rss-admin-css', $this->_rootUrl . 'assets/css/style.css');
    }

    function wp_action_links($links) {
        $settings_link = '<a href="' . menu_page_url($this->pageName('setup'), false) . '">'
            . esc_html(__('Settings', 'wpcf7')) . '</a>';

        array_unshift($links, $settings_link);

        return $links;
    }

    /**
     * Called when plugin is activated
     */
    function wp_activation_hook() {
        $installer = new RSS_Install();
        $installer->install();
    }


    function wp_footer() {
        wp_enqueue_script('jquery');
        $options = get_option('fsad_options', array());
    }

    function wp_setup() {
        if (empty($_REQUEST['action'])) $_REQUEST['action'] = 'general';

        if (is_callable(array($this, 'action' . ucfirst($_REQUEST['action']))))
            call_user_func(array($this, 'action' . ucfirst($_REQUEST['action'])));
    }

    function wp_ajax_rssmagic() {
        if (empty($_REQUEST['ajaxaction'])) $_REQUEST['ajaxaction'] = 'general';
        try {
            if (is_callable(array($this, 'action' . ucfirst($_REQUEST['ajaxaction']))))
                call_user_func(array($this, 'action' . ucfirst($_REQUEST['ajaxaction'])));
        } catch (Exception $e) {
            $this->ajaxResponse($e->getCode(), 'Unhandled exception: ' . $e->getMessage());
        }
    }


    /**
     * Admin CMS pages
     *
     */

    function actionGeneral() {
        $this->actionFeedlist();
    }

    function actionFeedlist() {
        $this->setTemplateVariable('page', 'feed');
        $list = new RSS_Feed_List();
        $list->prepare_items();
        $this->render('feedlist', array('list' => $list));
    }

    function actionAddfeed() {
        $feed = new RSS_Feed();
        if (isset($_POST['_wpnonce'])) {
            $feed->save($_REQUEST);
            $this->ajaxResponse(0, 'Feed added', array(), '?page=' . $this->pageName('setup') . '&action=feedlist');
        } else {
            $this->setTemplateVariable('data', $feed->load($_REQUEST['id']));
        }
        $this->setTemplateVariable('page', 'feed');
        $this->render('addfeed');
    }

    function actionFeedinfo() {
        $feed = new RSS_Feed();
        $feedInfo = $feed->getInfo($this->request('furl'));
        $this->ajaxResponse(is_array($feedInfo)?0:1, '', $feedInfo);
    }

    function actionEditfeed() {
        $this->actionAddfeed();
    }

    function actionDeletefeed() {
        $feed = new RSS_Feed();
        $feedInfo = $feed->delete($this->request('id'));
        $this->ajaxResponse(0, 'Deleted', array(), '?page=' . $this->pageName('setup') . '&action=feedlist');
    }

    function actionViewfeed() {
        $feed = new RSS_Feed();

        $list = new RSS_Item_List($this->request('id'));
        $list->prepare_items();

        $this->setTemplateVariable('page', 'viewfeed');
        $this->setTemplateVariable('feed', $feed->load($this->request('id')));
        $this->render('viewfeed', array('list' => $list));
    }


    /**
     * Update now support
     */
    function actionUpdatenow() {
        $feed = new RSS_Feed();

        $updateList = $feed->getList(
            array(
                'fid' => $this->request('id')
            )
        );

        $this->setTemplateVariable('page', 'updatenow');
        $this->render('updatenow', array('updateList' => $updateList));
    }

    function actionUpdatenowfeed() {
        $feed = new RSS_Feed();
        $stats = $feed->downloadOne($this->request('id'));
        $this->ajaxResponse(0, '', $stats);
    }



    function actionFeedback() {
        $this->setTemplateVariable('page', 'feedback');
        $this->render('feedback');
    }

    /**
     * Creates RSS digest
     *
     */
    function actionDigest() {
        $feed = new RSS_Feed();

        if (isset($_REQUEST['_wpnonce'])) {
            $this->setOption('digest_template', $this->request('template'));
            $this->setOption('digest_interval', $this->request('interval'));
            $this->setOption('digest_category', $this->request('category'));
            $this->setOption('digest_title', $this->request('title'));
            $postId = $feed->createDigest();
            $this->ajaxResponse(0, 'Digest created', array(), "post.php?action=edit&post={$postId}");
        }

        $this->setTemplateVariable('page', 'digest');
        $this->render('digest', array(
            'settings' => array(
                'template' => $this->getOption('digest_template'),
                'interval' => $this->getOption('digest_interval'),
                'category' => $this->getOption('digest_category'),
                'title' => $this->getOption('digest_title'),
            )
        ));
    }


    function getVersion() {
        $data = get_plugin_data($this->_rootFile);
        return $data['Version'];
    }

}