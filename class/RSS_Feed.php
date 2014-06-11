<?php
/**
 * RSS_Feed.php, feed related operation
 *
 * @author     Constantin Bosneaga <constantin@bosneaga.com>
 * @copyright  2013-2014 The Author
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 */

class RSS_Feed extends RSS_Base {

    /**
     * Save feed to database
     *
     * @param array $data Feed data from form
     */
    function save($data) {
        if (empty($data['fid'])) {
            $this->db()->insert(
                $this->getTableName('feed'),
                array(
                    'furl' => $data['furl'],
                    'ftitle' => $data['ftitle'],
                )
            );
        } else {
            $this->db()->update(
                $this->getTableName('feed'),
                array(
                    'furl' => $data['furl'],
                    'ftitle' => $data['ftitle'],
                ),
                array('fid' => $data['fid'])
            );
        }
    }

    /**
     * Load feed by ID
     *
     * @param int $id Feed Identifier
     * @return mixed
     */
    function load($id) {
        return $this->db()->get_row(
            $this->db()->prepare(
                'select * FROM ' . $this->getTableName('feed') . ' WHERE fid = %d',
                $id
            ),
            ARRAY_A
        );
    }

    /**
     * Delete feed from database
     *
     * @param int $feedId
     */
    function delete($feedId) {
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

    /**
     * Returns feed information by URL
     *
     * @param string $url Feed url
     * @return array|null
     */
    function getInfo($url) {
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


    /**
     * Downloads all feed data
     */
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
     * Updates all feeds using smart update feature based on how frequently feed updates
     */
    function updateAll() {
        $this->log('Updating all feeds');

        $sql = '
            SELECT
                feed.fid,
                feed.fupdated,
                min(item.fdatetime) as min_date,
                max(item.fdatetime) as max_date,
                count(item.fid) as item_count
            FROM ' . $this->getTableName('feed') . ' as feed
            LEFT JOIN ' . $this->getTableName('item') . ' as item ON (feed.fid = item.ffeed_id)
            GROUP BY feed.fid';
        $feedList = $this->db()->get_results($sql, ARRAY_A);

        $start = time();

        foreach($feedList as $feed) {
            $lastUpdate = new DateTime($feed['fupdated']);

            // Calculate next update time
            if ($feed['item_count'] > 0) {
                // Feed is alive
                $timeSpan = strtotime($feed['max_date']) - strtotime($feed['min_date']);
                $updateInterval = intval($timeSpan/$feed['item_count']);
            } else {
                $updateInterval = 86400;
            }
            // Update at least once per day
            if ($updateInterval >= 86400) {
                $updateInterval = 86400;
            }
            // But no more than once in hour
            if ($updateInterval <= 3600) {
                $updateInterval = 3600;
            }
            $nextUpdate = clone $lastUpdate;
            $nextUpdate->add(new DateInterval("PT{$updateInterval}S"));

            // If next update is already due - update
            if ($nextUpdate < new DateTime()) {
                $this->downloadOne($feed['fid']);
            }

            // Limit update time to one hour max
            if (time() - $start > 3600) {
                $this->log('Update feed is running too long, breaking');
                break;
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
        $this->log('Updating feed', array('fid' => $feedId));

        $feedRow = $this->db()->get_row(
            $this->db()->prepare(
                'select * FROM ' . $this->getTableName('feed') . ' WHERE fid = %d',
                $feedId
            ),
            ARRAY_A
        );

        if (!is_array($feedRow)) {
            return "Not found";
        }

        $feed = fetch_feed( $feedRow['furl'] );

        // Mark as updated
        $this->db()->update(
            $this->getTableName('feed'),
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

            // Check if feed is already inserted
            $alreadyExists = $this->db()->get_row(
                $this->db()->prepare(
                    'select * FROM ' . $this->getTableName('item') . ' WHERE ffeed_id = %d AND furl = %s',
                    $feedRow['fid'],
                    $item->get_link()
                ),
                ARRAY_A
            );
            if (isset($alreadyExists['fid'])) {
                continue;
            }

            $this->db()->insert(
                $this->getTableName('item'),
                array(
                    'ffeed_id' => $feedRow['fid'],
                    'ftitle' => $item->get_title(),
                    'fauthor' => $item->get_author()?$item->get_author()->get_name():null,
                    'fdescription' => $item->get_description(),
                    'furl' => $item->get_link(),
                    'fdatetime' => $item->get_date('Y-m-d H:i:s'),
                )
            );
            $updated++;
        }

        return ' downloaded ' . $updated . '.';
    }

    /**
     * Returns list of all feeds
     *
     * @param array $options
     * @return mixed
     */
    function getList($options = array()) {
        $plugin = RSS_Plugin::getInstance();

        $feedList = $plugin->db()->get_results(
            'select * FROM ' . $plugin->getTableName('feed') . ' ORDER BY fupdated ASC',
            ARRAY_A
        );

        return $feedList;
    }

    /**
     * Creates digest using options
     *
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

