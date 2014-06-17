<?php
/**
 * RSS_Cron.php, cron operation
 *
 * @author     Constantin Bosneaga <constantin@bosneaga.com>
 * @copyright  2013-2014 The Author
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 */

class RSS_Cron extends RSS_Base {

    /**
     * Runs on hourly basis
     *
     */
    static public function hourly() {
        $feed = new RSS_Feed();
        $feed->log('Starting cron');
        $feed->updateAll();
    }

}

