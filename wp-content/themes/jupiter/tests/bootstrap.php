<?php
global $abb_phpunit;
$abb_phpunit = true;

define('WordPressPath', '/Users/R3za/Documents/Project/Artbees/Web/Jupiter/');

require_once WordPressPath . 'includes/functions.php';
require_once __DIR__ . '/../vendor/autoload.php';

function _manually_load_environment()
{
	// Add your theme …

	// Update array with plugins to include ...
	// $plugins_to_active = array(
	// 	‘your - plugin / your - plugin . php’,
	// );
	// update_option('active_plugins', $plugins_to_active);

}
tests_add_filter('muplugins_loaded', '_manually_load_environment');

require  WordPressPath . 'includes/bootstrap.php';