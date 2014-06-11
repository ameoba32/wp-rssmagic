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
     * Runs on hourly basis
     *
     */
    function hourly() {
        $this->log('Starting cron');
        $feed = new RSS_Feed();
        $feed->updateAll();
    }

}
