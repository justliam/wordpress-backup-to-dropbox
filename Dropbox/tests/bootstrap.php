<?php

/**
 * A bootstrap for the Dropbox SDK unit tests
 * @link https://github.com/BenTheDesigner/Dropbox/tree/master/tests
 */

// Restrict access to the command line
if (PHP_SAPI !== 'cli') {
    exit('setup.php must be run via the command line interface');
}

// Set error reporting
error_reporting(-1);
ini_set('display_errors', 'On');
ini_set('html_errors', 'Off');
session_start();

// Register a simple autoload function
function dropbox_api_autoloader($class)
{
    require_once '../Dropbox/'. implode('/', explode('_', $class)) . '.php');
}

spl_autoload_register('dropbox_api_autoloader');
