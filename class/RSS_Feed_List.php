<?php
/**
 * RSS_Feed_List.php, feed edit table
 *
 * @author     Constantin Bosneaga <constantin@bosneaga.com>
 * @copyright  2013-2014 The Author
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 */

// Init section
if (!class_exists('WP_List_Table')) require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');


class RSS_Feed_List extends WP_List_Table {

    function __construct() {
        parent::__construct(array(
            'singular' => 'feed',
            'plural' => 'feeds',
            'ajax' => false)
        );
    }

    function prepare_items() {
        $plugin = RSS_Plugin::getInstance();

        $columns = array(
            'cb' => '<input type="checkbox" />',
            'title' => 'Title',
            'update' => 'Last update',
        );
        $hidden = array();
        $sortable = array();
        $this->_column_headers = array($columns, $hidden, $sortable);

        $this->items = array();
        foreach($plugin->db()->get_results('select * FROM wp_rssmagic_feed', ARRAY_A) as $row) {
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
            'updatenow' => 'Update',
            'delete' => 'Delete',
        );
        return $actions;
    }

    function column_default($item, $column_name) {
        return '';
    }

    function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="id[]" value="%d" />',
            $item['fid']
        );
    }

    function column_title($item) {
        $plugin = RSS_Plugin::getInstance();

        $edit_link = add_query_arg(array('action' => 'editfeed', 'id' => $item['fid']), menu_page_url($plugin->pageName('setup'), false));
        $update_link = add_query_arg(array('action' => 'updatenow', 'id[]' => $item['fid']), menu_page_url($plugin->pageName('setup'), false));
        $preview_link = add_query_arg(array('action' => 'viewfeed', 'id' => $item['fid']), menu_page_url($plugin->pageName('setup'), false));

        $actions = array(
            'edit' => '<a href="' . $edit_link . '">Edit</a>',
            'update' => '<a href="' . $update_link . '">Update</a>',
            'preview' => '<a href="' . $preview_link . '" target="_BLANK">View</a>',
            'delete' => '<a href="#" data-post="ajaxaction=deletefeed&id=' . $item['fid'] . '" class="ajax confirm">Delete</a>',
        );

        $a = sprintf('<a class="row-title" href="%s" title="%s">%s</a>',
            $edit_link,
            esc_attr($item['ftitle']),
            esc_html($item['ftitle'])
        );
        return '<strong>' . $a . '</strong> ' . $this->row_actions($actions);
    }

    function column_update($item) {
        return ucfirst($item['fupdated']);
    }

}

