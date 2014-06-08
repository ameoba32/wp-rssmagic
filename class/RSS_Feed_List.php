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
            'singular' => 'ad',
            'plural' => 'ads',
            'ajax' => false)
        );

    }

    function prepare_items() {
        global $wpdb;

        $columns = array(
            'cb' => '<input type="checkbox" />',
            'title' => 'Title',
            'update' => 'Last update',
        );
        $hidden = array();
        $sortable = array();
        $this->_column_headers = array($columns, $hidden, $sortable);

        $this->items = array();
        foreach($wpdb->get_results('select * FROM wp_rssmagic_feed', ARRAY_A) as $row) {
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

        $edit_link = add_query_arg(array('action' => 'editfeed', 'id' => $item['fid']), menu_page_url($plugin->pageName('setup'), false));
        $preview_link = add_query_arg(array('action' => 'viewfeed', 'id' => $item['fid']), menu_page_url($plugin->pageName('setup'), false));

        $actions = array(
            'edit' => '<a href="' . $edit_link . '">Edit</a>',
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

    function saveFeed($data) {
        global $wpdb;

        if (empty($data['fid'])) {
            $wpdb->insert(
                "wp_rssmagic_feed",
                array(
                    'furl' => $data['furl'],
                    'ftitle' => $data['ftitle'],
                )
            );
        } else {
            $wpdb->update("wp_rssmagic_feed",
                array(
                    'furl' => $data['furl'],
                    'ftitle' => $data['ftitle'],
                ),
                array('fid' => $data['fid'])
            );
        }
    }

    function loadFeed($id) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "select * FROM wp_rssmagic_feed WHERE fid = %d",
                $id
            ),
            ARRAY_A
        );
    }

    function deleteFeed($feedId) {
        global $wpdb;

        $wpdb->delete(
            "wp_rssmagic_item",
            array(
                'ffeed_id' => $feedId,
            )
        );

        $wpdb->delete(
            "wp_rssmagic_feed",
            array(
                'fid' => $feedId,
            )
        );
    }


    function getFeedInfo($url) {
        $feed = fetch_feed( $url );
        if ($feed instanceof SimplePie) {
            return array(
                'title' => $feed->get_title(),
                'description' => $feed->get_description(),
            );
        } else {
            return null;
        }
    }

    function download() {
        $plugin = RSS_Plugin::getInstance();

            $feedList = $plugin->db()->get_results(
                'select * FROM ' . $plugin->getTableName('feed') . ' ORDER BY fupdated ASC',
                ARRAY_A
            );

        foreach($feedList as $rowFeed) {
            $feed = fetch_feed( $rowFeed['furl'] );

            $plugin->db()->update(
                $plugin->getTableName('feed'),
                array(
                    'fupdated' => date('Y-m-d H:i:s'),
                ),
                array('fid' => $rowFeed['fid'])
            );

            if (!$feed instanceof SimplePie) {
                continue;
            }

            $updated = 0;
            $itemQty = $feed->get_item_quantity();
            for ($i = 0; $i < $itemQty; $i++) {
                $item = $feed->get_item($i);

                $updated += $plugin->db()->insert(
                    $plugin->getTableName('item'),
                    array(
                        'ffeed_id' => $rowFeed['fid'],
                        'ftitle' => $item->get_title(),
                        'fauthor' => $item->get_author()?$item->get_author()->get_name():null,
                        'fdescription' => $item->get_description(),
                        'furl' => $item->get_link(),
                        'fdatetime' => $item->get_date('Y-m-d H:i:s'),
                    )
                );
            }
        }
    }


    /**
     * Update feed using Id
     *
     * @param int $feedId Feed identifier
     * @return string
     */

    function downloadOne($feedId) {
        $plugin = RSS_Plugin::getInstance();

        $feedRow = $plugin->db()->get_row(
            $plugin->db()->prepare(
                'select * FROM ' . $plugin->getTableName('feed') . ' WHERE fid = %d',
                $feedId
            ),
            ARRAY_A
        );

        if (!is_array($feedRow)) {
            return "Not found";
        }

        $feed = fetch_feed( $feedRow['furl'] );

        // Mark as updated
        $plugin->db()->update(
            $plugin->getTableName('feed'),
            array(
                'fupdated' => date('Y-m-d H:i:s'),
            ),
            array('fid' => $feedRow['fid'])
        );

        if (!$feed instanceof SimplePie) {
            return "Error";
        }


        $itemQty = $feed->get_item_quantity();
        $updated = 0;
        for ($i = 0; $i < $itemQty; $i++) {
            $item = $feed->get_item($i);

            $updated += $plugin->db()->insert(
                $plugin->getTableName('item'),
                array(
                    'ffeed_id' => $feedRow['fid'],
                    'ftitle' => $item->get_title(),
                    'fauthor' => $item->get_author()?$item->get_author()->get_name():null,
                    'fdescription' => $item->get_description(),
                    'furl' => $item->get_link(),
                    'fdatetime' => $item->get_date('Y-m-d H:i:s'),
                )
            );
        }

        return ' downloaded ' . $updated . '.';
    }

    function getList($options = array()) {
        $plugin = RSS_Plugin::getInstance();

        $feedList = $plugin->db()->get_results(
            'select * FROM ' . $plugin->getTableName('feed') . ' ORDER BY fupdated ASC',
            ARRAY_A
        );

        return $feedList;
    }

    /**
     * @return int post id
     */
    function createDigest() {
        global $wpdb;
        $plugin = RSS_Plugin::getInstance();

        $interval = $plugin->getOption('digest_interval');
        if ($interval == 'week') {
            $interval = new DateTime('-1 week');
        } else {
            $interval = new DateTime();
        }

        $feedList = array();
        foreach($wpdb->get_results('select * FROM ' . $plugin->getTableName('feed'), ARRAY_A) as $row) {
            $feed = $row;
            $feed['items'] = $wpdb->get_results(
                $wpdb->prepare(
                    'select * FROM ' . $plugin->getTableName('item') . ' WHERE ffeed_id = %d AND fdatetime >= %s ORDER BY fdatetime ASC',
                    $row['fid'],
                    $interval->format('Y-m-d H:i:s')
                ),
                ARRAY_A
            );

            // Skip empty feeds
            if (count($feed['items']) > 0) {
                 $feedList[] = $feed;
            }
        }

        $loader = new Twig_Loader_String();
        $twig = new Twig_Environment($loader);

        $content = $twig->render(
            $plugin->getOption('digest_template'),
            array('feeds' => $feedList)
        );

        // Save post
        $post = array(
            'post_date' => date('Y-m-d H:i:s'),
            'post_title' => $plugin->getOption('digest_title'),
            //'post_name' => $this->__translit(wp_strip_all_tags($rss_item['title'])),
            'post_content' => $content,
            'post_excerpt' => '',
            'post_status' => 'draft',
            'post_type' => 'post',
            'post_category' => array($plugin->getOption('digest_category'))
        );

        $post['id'] = wp_insert_post($post);
        //update_post_meta($post['id'], 'url', $rss_item['link']);
        //update_post_meta($post['id'], 'original', $page);
        // Create post
        return $post['id'];
    }

}

