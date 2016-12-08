Rorm
====
 - Author: Rémy M. Böhler <code@rrelmy.ch>
 - License: MIT
 - Version: 0.1

[![Build Status](https://scrutinizer-ci.com/g/rrelmy/rorm/badges/build.png?b=master)](https://scrutinizer-ci.com/g/rrelmy/rorm/build-status/master)
[![Code Coverage](https://scrutinizer-ci.com/g/rrelmy/rorm/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/rrelmy/rorm/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/rrelmy/rorm/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/rrelmy/rorm/?branch=master)

[![Build Status](https://travis-ci.org/rrelmy/rorm.svg?branch=master)](https://travis-ci.org/rrelmy/rorm)

Requirements
------------
 - PHP 5.3

PHP 5.3 requires a `JsonSerializable` polyfill for the Model.

With PHP 5.5 we could ditch the `QueryIterator` and use `yield`.
Yield seems to have a little bit bigger memory footprint.

Goals
-----
 - Easy to use, easy to extend
 - Minimalistic ORM
 - Fast and low memory footprint
 - compound key support
 - Almost 100% test code coverage

TODO
----
 - check for possible model loaded hook
 - check if `autoId` could be ditched
 - consider `setExpr` (may be bad because until the model is loaded again the data is 'weird')
 - grouping/having support inside the `QueryBuilder`
 - Documentation

Ideas
-----
 - Cache

Usage
-----

### General

| Code                          | Description                            |
| ----------------------------- | -------------------------------------- |
| `Rorm::setDatabase($dbh);`    | Set default database connection        |
| `Rorm::getDatabase();`        | Retrieve the PDO object                |
| `Rorm::isMySQL($dbh)`         | Check for MySQL connection             |
| `Rorm::isSQLite($dbh)`        | Check for SQLite connection            |
| `Rorm::quote($dbh, $value)`   | Returns the quoted value               |
| `Rorm::getIdentifierQuoter()` | Returns a closure to quote identifiers |

### Model

| Code                          | Description                           |
| ----------------------------- | ------------------------------------- |
| `MyModel::getTable();`        | Get the table name                    |
| `MyModel::getDatabase();`     | Get the database connection           |
| `MyModel::create();`          | Create a new instance                 |
| `MyModel::find($id);`         | Find the model with specific `$id`    |
| `MyModel::findAll();`         | Get all available models              |
| `MyModel::query();`           | Create a new `QueryBuilder` instance  |
| `MyModel::customQuery($sql);` | Create a custom query                 |

| Code                          | Description                           |
| ----------------------------- | ------------------------------------- |
| `$model->getId()`             | Get the id of the current model       |
| `$model->hasId()`             | Check if the model has a id specified |
| `$model->save()`              | Save current data to the database     |
| `$model->delete()`            | Delete entry from the database        |
| `$model->getData()`           | Get all data as array                 |
| `$model->setData($data)`      | Overwrite the data                    |
| `$model->get($name)`          | Get property                          |
| `$model->set($name, $value)`  | Set property                          |
| `$model->has($name)`          | Check if data exists                  |
| `$model->remove($name)`       | Remove property                       |
| `$model->copyDataFrom($data)` | Copy data from object or array        |

### QueryBuilder

| Code                                      | Description                           |
| ----------------------------------------- | ------------------------------------- |
| `$query->distinct()`                      | Use `DISTINCT`                        |
| `$query->selectAll()`                     | Select all data (default)             |
| `$query->select($column[, $as])`          | Select specific column                |
| `$query->selectExpr($expr[, $as])`        | Select expression                     |
| `$query->where($column, $value)`          | Add `WHERE c = ?`                     |
| `$query->whereNot($column, $value)`       | Add `WHERE c != ?`                    |
| `$query->whereId($id)`                    | Add `WHERE id = ?`                    |
| `$query->whereExpr($column, $expr)`       | Add `WHERE c = EXPR()`                |
| `$query->whereRaw($where[, $params])`     | Add custom where clause               |
| `$query->whereLt($column, $value)`        | Add `WHERE c < ?`                     |
| `$query->whereLte($column, $value)`       | Add `WHERE c <= ?`                    |
| `$query->whereGt($column, $value)`        | Add `WHERE c > ?`                     |
| `$query->whereGte($column, $value)`       | Add `WHERE c >= ?`                    |
| `$query->whereNull($column)`              | Add `WHERE c IS NULL ?`               |
| `$query->whereNotNull($column)`           | Add `WHERE c IS NOT NULL ?`           |
| `$query->whereIn($column, $data)`         | Add `WHERE c IN (?, ?, ?)`            |
| `$query->orderByAsc($column)`             | Add `ORDER BY c ASC`                  |
| `$query->orderByDesc($column)`            | Add `ORDER BY c DESC`                 |
| `$query->orderByExpr($column[, $params])` | Add `ORDER BY EXPR()`                 |
| `$query->limit(10)`                       | Add `LIMIT 10`                        |
| `$query->offset(10)`                      | Add `OFFSET 10`                       |
| `$query->findColumn()`                    | Retrieve first column of first entry  |
| `$query->findMany()`                      | Retrieve entries via an iterator      |
| `$query->findAll()`                       | Retrieve entries as array             |
| `$query->count()`                         | Count the results                     |


Relations
---------
There is no special support for relationships, but its easy to integrate them yourself.

Error handling
--------------
It is recommended to use the PDO exception error mode.
Rorm has no special error handling and does not catch thrown `PDOException`'s!

    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


Unbuffered queries
------------------
You can use unbuffered queries with the findMany method, but you have to be aware that
no queries can be executed until the iteration is finished.

No special methods are supplied for configure unbuffered queries. You may use the PDO attributes yourself.

Multiple database connections
-----------------------------
You can add multiple database connections to the Rorm config with the `setDatabase($dbh, 'name')` method.

Each model can have a different database connection which can be configure with the `$_connection` property.
