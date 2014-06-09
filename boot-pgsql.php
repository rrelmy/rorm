<?php

use Rorm\Rorm;

/**
 * database connection
 *
 * we do it inside a function to prevent leaking the $dbh instance to the global namespace
 * if the instance is in the global namespace phpunit fails because it tries to serialize it
 */
$setupDatabaseMySQL = function () {
    $dbh = new PDO('pgsql:dbname=rorm', 'rorm', 'secret');
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    Rorm::setDatabase($dbh, 'pgsql');


    // init database
    $dbh->exec('DROP TABLE IF EXISTS test_basic;');
    $dbh->exec('DROP TABLE IF EXISTS test_compound;');

    $dbh->exec(
        'CREATE TABLE test_basic (
            id SERIAL,
            name VARCHAR(255),
            number DECIMAL(10,2),
            modified TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            active BOOLEAN DEFAULT FALSE,
            deleted BOOLEAN DEFAULT FALSE
        );'
    );

    $dbh->exec(
        'CREATE TABLE test_compound (
            foo_id INTEGER,
            bar_id INTEGER ,
            name VARCHAR(255),
            rank INTEGER,
            PRIMARY KEY(foo_id, bar_id)
        );'
    );

};
$setupDatabaseMySQL();
