<?php
/*
Plugin Name: RssMagic
Description: Rss plugin
Author: Constantin Bosneaga
Author URI: http://a32.me/
Email: constantin@bosneaga.com
Version: 1.2
*/

// Simple pie is included in wordpress
include_once(ABSPATH . WPINC . '/feed.php');

// Twig templates
require_once 'phar://'. __DIR__ . '/lib/twig.phar/Twig/Autoloader.php';
Twig_Autoloader::register();

require_once 'class'. DIRECTORY_SEPARATOR . 'RSS_Autoload.php';


$rssPlugin = RSS_Plugin::build(__FILE__);
