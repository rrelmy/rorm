<?php
/**
 * @author: remy
 */

error_reporting(E_ALL);
ini_set('display_errors', true);

require 'vendor/autoload.php';

// boot database connections
require 'boot-mysql.php';
require 'boot-sqlite.php';
