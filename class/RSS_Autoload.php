<?php
/**
 * RSS_Autoload.php, registr class loader
 *
 * @author     Constantin Bosneaga <constantin@bosneaga.com>
 * @copyright  2013-2014 The Author
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 */

spl_autoload_register(
    function ($class_name) {
        if (0 !== strpos($class_name, 'RSS')) {
            return;
        }

        $classFile = dirname(__FILE__) . DIRECTORY_SEPARATOR
            . $class_name . '.php';
        if (file_exists(($classFile))) {
            include_once $classFile;
        }
    }
);
