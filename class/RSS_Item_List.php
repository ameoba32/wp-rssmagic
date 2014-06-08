<?php
/**
 * RSS_Item_List.php, feed edit table
 *
 * @author     Constantin Bosneaga <constantin@bosneaga.com>
 * @copyright  2013-2014 The Author
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 */

// Init section
if (!class_exists('WP_List_Table')) require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');


class RSS_Item_List extends WP_List_Table {

    private $feedId;

    function __construct($feedId) {
        parent::__construct(array(
            'singular' => 'ad',
            'plural' => 'ads',
            'ajax' => false)
        );
        $this->feedId = $feedId;
    }

    function prepare_items() {
        global $wpdb;

        $columns = array(
            'cb' => '<input type="checkbox" />',
            'title' => 'Title',
            'datetime' => 'Datetime',
        );
        $hidden = array();
        $sortable = array();
        $this->_column_headers = array($columns, $hidden, $sortable);

        $this->items = array();
        $sql =
            $wpdb->prepare(
                'select * FROM wp_rssmagic_item WHERE ffeed_id = %d ORDER BY fdatetime DESC',
                $this->feedId
            );
        foreach($wpdb->get_results($sql, ARRAY_A) as $row) {
            $this->items[] = $row;
        }

        $per_page = 10;
        $total_items = count($this->items);
        $total_pages = ceil($total_items / $per_page);

        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'total_pages' => $total_pages,
            'per_page' => $per_page)
        );
    }

    function get_columns() {
        return get_column_headers(get_current_screen());
    }

    /*
function get_sortable_columns() {
    $columns = array(
        'title' => array( 'title', true ),
        'author' => array( 'author', false ),
        'date' => array( 'date', false ) );

    return $columns;
}
    */

    function get_bulk_actions() {
        $actions = array(
            'delete' => 'Delete'
        );
        return $actions;
    }

    function column_default($item, $column_name) {
        return '';
    }

    function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            $this->_args['singular'],
            $item->id);
    }

    function column_title($item) {
        $plugin = RSS_Plugin::getInstance();

        $preview_link = add_query_arg(array('action' => 'viewfeed', 'id' => $item['fid']), menu_page_url($plugin->pageName('setup'), false));

        $actions = array(
            'view' => '<a target="_BLANK" href="' . $item['furl'] . '">View</a>',
            'delete' => '<a href="#" data-post="ajaxaction=deletefeed&id=' . $item['fid'] . '" class="ajax confirm">Delete</a>',
        );

        $a = sprintf('<a class="row-title" href="%s" title="%s">%s</a>',
            $item['furl'],
            esc_attr($item['ftitle']),
            esc_html($item['ftitle'])
        );
        return '<strong>' . $a . '</strong> ' . $this->row_actions($actions);
    }

    function column_datetime($item) {
        return $item['fdatetime'];
    }

}


