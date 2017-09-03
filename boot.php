<?php
/**
 * @author: remy
 */
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require 'vendor/autoload.php';

// boot database connections
require 'boot-mysql.php';
require 'boot-sqlite.php';
