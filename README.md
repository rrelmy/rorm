Rorm
====
 - Author: Rémy M. Böhler <code@rrelmy.ch>
 - License: GPL v3
 - Version: 0.1

Requirements
------------
 - PHP 5.3

PHP 5.3 requires a JsonSerializable polyfill for the Model.

With PHP 5.5 we could ditch the QueryIterator and use yield.
Yield seems to have a little bit bigger memory footprint.

Goals
-----
 - Easy to use, easy to extend
 - Minimalistic ORM
 - Fast and low memory footprint
 - compound key support
 - 100% test code coverage

TODO
----
 - Documentation
 - Support other database drivers such as PostgreSQL
 - Multiple database connections

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
Rorm has no special error handling and does not catch thrown excpetions!

Unbuffered queries
------------------
You can use unbuffered queries with the findMany method, but you have to be aware that
no queries can be executed until the iteration is finished.

No special method are supplied for configure unbuffered queries. You may use the PDO Attributes yourself.