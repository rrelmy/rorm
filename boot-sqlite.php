<?php

use Rorm\Rorm;

/**
 * database connection
 *
 * we do it inside a function to prevent leaking the $dbh instance to the global namespace
 * if the instance is in the global namespace phpunit fails because it tries to serialize it
 */
$setupDatabaseSQLite = function () {
    // create sqlite database
    $dbh = new PDO('sqlite::memory:');
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    Rorm::setDatabase($dbh, 'sqlite');

    // setup database
    $dbh->exec('DROP TABLE IF EXISTS modelsqlite');
    $dbh->exec(
        'CREATE TABLE modelsqlite (
             rowid INTEGER PRIMARY KEY AUTOINCREMENT,
             name TEXT NOT NULL,
             email TEXT UNIQUE,
             number REAL,
             active INTEGER,
             deleted INTEGER
        );'
    );
    $dbh->exec('DROP TABLE IF EXISTS modelsqlitecompound');
    $dbh->exec(
        'CREATE TABLE modelsqlitecompound (
            foo_id INTEGER,
            bar_id INTEGER,
            name TEST,
            rank INTEGER,
            PRIMARY KEY(foo_id, bar_id)
        );'
    );
};
$setupDatabaseSQLite();
