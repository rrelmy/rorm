<?php

use Rorm\Rorm;

/**
 * database connection
 *
 * we do it inside a function to prevent leaking the $dbh instance to the global namespace
 * if the instance is in the global namespace phpunit fails because it tries to serialize it
 */
$setupDatabaseMySQL = function () {
    $dbh = new PDO('mysql:host=localhost;dbname=rorm', 'rorm', 'secret');
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    Rorm::setDatabase($dbh);


    // init database
    $dbh->exec('DROP TABLE IF EXISTS test_basic;');
    $dbh->exec('DROP TABLE IF EXISTS rormtest_test_compound;');

    $dbh->exec(
        'CREATE TABLE test_basic (
            id INT UNSIGNED AUTO_INCREMENT,
            name VARCHAR(255),
            email VARCHAR(255) UNIQUE,
            number DECIMAL(10,2),
            modified TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            active TINYINT(1) DEFAULT 0,
            deleted TINYINT(1) DEFAULT 0,
            PRIMARY KEY(id)
        );'
    );

    $dbh->exec(
        'CREATE TABLE rormtest_test_compound (
            foo_id INT UNSIGNED,
            bar_id INT UNSIGNED,
            name VARCHAR(255),
            rank INT UNSIGNED,
            PRIMARY KEY(foo_id, bar_id)
        );'
    );

};
$setupDatabaseMySQL();
