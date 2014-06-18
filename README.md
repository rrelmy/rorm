Rorm
====
 - Author: Rémy M. Böhler <code@rrelmy.ch>
 - License: GPL v3
 - Version: 0.1

Requirements
------------
 - PHP 5.3

PHP 5.3 requires a JsonSerializable poly fill for the Model.

With PHP 5.5 we could ditch the QueryIterator and use yield.
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
 - check if public properties/methods are clear named
 - fetchColumn
 - setData/getData -> addData or just leave copyDataFrom?
 - check for possible model loaded hook
 - check if autoId could be ditched
 - consider `setExpr` (may be bad because until the model is loaded again the data is 'weird')
 - grouping/having
 - Documentation

Ideas
-----
 - Cache

Usage
-----
 - For now you should read through the test suite for Model

Relations
---------
There is not special support for relations, but its easy to integrate it yourself.

Error handling
--------------
It is a good idea to use the PDO exception error mode.
Rorm has no special error handling and does not catch thrown exceptions!

Unbuffered queries
------------------
You can use unbuffered queries with the findMany method, but you have to be aware that
no queries can be executed until the iteration is finished.

No special method are supplied for configure unbuffered queries. You may use the PDO attributes yourself.

Multiple database connections
-----------------------------

You can set multiple database connections to the Rorm config with the ```setDatabase($db, 'name')``` method.

Each model can have different database connection which can be configure with the ```$_connection``` property.

PostgreSQL caveats!
-------------------
The PostgreSQL part is not that good tested as the MySQL/SQLite part!

You MUST ensure to use the correct data type when setting data to a PostgreSQL Model.
Because the ORM does not check the real table column type, it just uses the data type supplied to is.
If you are setting an empty string to an boolean column the built query will error because of the wrong type!

I may drop PostgreSQL support because of the complexity it added to Rorm!
A better merge implementation would help.
