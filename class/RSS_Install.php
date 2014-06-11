<?php
/**
 * RSS_Install.php, handle plugin initial install as well as updates
 *
 * @author     Constantin Bosneaga <constantin@bosneaga.com>
 * @copyright  2013-2014 The Author
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 */


class RSS_Install extends RSS_Base  {

    /**
     * Constructor
     *
     * @param $rootFile Main plugin file
     */
    function __construct() {
    }

    /**
     * Checks if plugin was updated and initiates upgrade procedure
     */
    function checkForUpgrade()
    {
        $plugin = RSS_Plugin::getInstance();
        if ($plugin->getVersion() != $this->getOption('version')) {
            $this->install();
            $this->setOption('version', $plugin->getVersion());
        }
    }

    /**
     * Installs or updates plugin database schema
     */
    function install() {
        $patchLevel = $this->getOption('patchLevel', 1);

        // Apply patches until no more levels, save applied patch level
        while ($this->patch($patchLevel)) {
            $patchLevel = $patchLevel + 1;
            $this->setOption('patchLevel', $patchLevel);
        }
    }

    /**
     * Database changes by version
     *
     * @param int $level Level to apply
     * @return bool if level was applied or not
     */
    function patch($level) {
        $result = true;

        switch ($level) {
            case 1:
                $sql = "
                CREATE TABLE IF NOT EXISTS `{$this->_tablePrefix}_feed` (
                  `fid` int(11) NOT NULL AUTO_INCREMENT,
                  `furl` varchar(200) DEFAULT NULL,
                  `ftitle` varchar(200) DEFAULT NULL,
                  `fdescription` varchar(200) CHARACTER SET utf8 DEFAULT NULL,
                  `fupdated` datetime DEFAULT NULL,
                  PRIMARY KEY (`fid`)
                ) CHARSET=utf8;";
                $this->db()->query($sql);

                $sql = "
                CREATE TABLE IF NOT EXISTS `{$this->_tablePrefix}_item` (
                  `fid` int(11) NOT NULL AUTO_INCREMENT,
                  `ffeed_id` int(11) NOT NULL,
                  `ftitle` varchar(200) DEFAULT NULL,
                  `fauthor` varchar(200) DEFAULT NULL,
                  `fdatetime` datetime DEFAULT NULL,
                  `fdescription` varchar(200) DEFAULT NULL,
                  `furl` varchar(250) DEFAULT NULL,
                  PRIMARY KEY (`fid`),
                  UNIQUE KEY `ffeed_id` (`ffeed_id`,`furl`)
                  )DEFAULT CHARSET=utf8;
                ";
                $this->db()->query($sql);
                break;
            case 2:
                // Install Cron for automatic updates
                wp_schedule_event( time(), 'hourly', $this->_tablePrefix . '_hourly_cron' );
                break;
            default:
                $result = false;
                break;
        }
        return $result;
    }

}

