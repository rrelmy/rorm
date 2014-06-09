<?php
/**
 * @author: remy
 */

error_reporting(E_ALL);
ini_set('display_errors', true);

// setup
set_include_path(
    __DIR__ . '/Tests/' . PATH_SEPARATOR .
    __DIR__ . '/TestsMySQL/' . PATH_SEPARATOR .
    __DIR__ . '/TestsSQLite/' . PATH_SEPARATOR .
    get_include_path()
);

// primitive psr-4 auto loader
spl_autoload_register(
    function ($class) {
        // namespaces
        $namespaceTest = 'RormTest\\';

        if (strpos($class, $namespaceTest) === 0) {
            $class = substr($class, strlen($namespaceTest));
            $directories = array(
                __DIR__ . '/Tests/',
                __DIR__ . '/TestsMySQL/',
                __DIR__ . '/TestsSQLite/',
                __DIR__ . '/TestsPostgreSQL/',
            );
        } else {
            $directories = explode(PATH_SEPARATOR, get_include_path());
        }

        $classFile = '/' . str_replace(array('_', '\\'), '/', $class) . '.php';
        foreach ($directories as $path) {
            if (is_file($path . $classFile)) {
                require $path . $classFile;
                return true;
            }
        }

        return false;
    }
);

// include Rorm
require 'include.php';

// boot database connections
require 'boot-mysql.php';
require 'boot-pgsql.php';
require 'boot-sqlite.php';
