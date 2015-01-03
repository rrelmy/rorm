<?php
/**
 * @author Rémy M. Böhler <code@rrelmy.ch>
 */
namespace Rorm;

use PDO;

/**
 * Class Query
 */
class Query
{
    /** @var PDO */
    protected $dbh;

    /** @var string */
    protected $class;

    /** @var bool */
    protected $classIsOrmModel;

    /** @var string */
    protected $query;

    /** @var array */
    protected $params;

    /** @var \PDOStatement */
    protected $statement;

    /**
     * @param string $class
     * @param PDO $dbh if null the default database connection is used
     */
    public function __construct($class = 'stdClass', PDO $dbh = null)
    {
        $this->class = $class;
        $this->classIsOrmModel = is_subclass_of($this->class, '\\Rorm\\Model');
        $this->dbh = $dbh ? $dbh : Rorm::getDatabase();
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @param string $query
     */
    public function setQuery($query)
    {
        $this->query = $query;
    }

    /**
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param array $params
     */
    public function setParams(array $params)
    {
        $this->params = $params;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @return bool
     *
     * @todo probably we can unset query an params to free up memory
     */
    protected function execute()
    {
        $this->statement = $this->dbh->prepare($this->query);
        // set fetchMode to assoc, it is easier to copy data from an array than an object
        $this->statement->setFetchMode(PDO::FETCH_CLASS, $this->class);
        return $this->statement->execute($this->params);
    }

    /**
     * @return mixed|null
     */
    public function fetch()
    {
        return $this->statement->fetch() ?: null;
    }

    /**
     * @return mixed
     */
    public function findColumn()
    {
        if ($this->execute()) {
            return $this->statement->fetchColumn();
        }
        return null;
    }

    /**
     * Return one object
     *
     * @return mixed
     */
    public function findOne()
    {
        // DO NOT use rowCount to check if something was found because not all drivers support it
        if ($this->execute()) {
            return $this->fetch();
        }
        return null;
    }

    /**
     * Return a iterator to iterate over which returns one object at a time
     * the objects are lazy loaded and not kept on memory
     *
     * because the results are not buffered you can only iterate once over it!
     * If you need to iterate multiple times over the result you should use the findAll method
     *
     * Note for PHP 5.5
     * yield could be used
     *
     * @return QueryIterator
     */
    public function findMany()
    {
        $this->execute();
        return new QueryIterator($this->statement);
        // PHP 5.5 yield version for future use
        /*while ($object = $this->statement->fetchObject()) {
            yield $this->instanceFromObject($object);
        }*/
    }

    /**
     * Return an array with all objects, this can lead to heavy memory consumption
     *
     * @return array
     */
    public function findAll()
    {
        $result = array();

        foreach ($this->findMany() as $object) {
            $result[] = $object;
        }

        return $result;
    }

    /**
     * This operation is very expensive.
     *
     * PDOStatement::rowCount does not work on all drivers!
     *
     * @return int
     */
    public function count()
    {
        return count($this->findAll());
    }
}
