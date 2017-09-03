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
     * @param PDO|null $dbh if null the default database connection is used
     */
    public function __construct($class = 'stdClass', PDO $dbh = null)
    {
        $this->class = $class;
        $this->classIsOrmModel = is_subclass_of($this->class, Model::class);
        $this->dbh = $dbh ?: Rorm::getDatabase();
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
        $this->statement->setFetchMode(PDO::FETCH_ASSOC);
        return $this->statement->execute($this->params);
    }

    /**
     * @return object|null
     */
    public function fetch()
    {
        $data = $this->statement->fetch();
        if ($data !== false) {
            return $this->instanceFromObject($data);
        }
        return null;
    }

    /**
     * @param array $data
     * @return object
     */
    public function instanceFromObject(array $data)
    {
        $instance = new $this->class;
        if ($this->classIsOrmModel) {
            /** @var \Rorm\Model $instance */
            $instance->setData($data);
        } else {
            foreach ($data as $key => $value) {
                $instance->$key = $value;
            }
        }

        return $instance;
    }

    /**
     * @return string|null
     */
    public function findColumn()
    {
        if ($this->execute()) {
            return $this->statement->fetchColumn();
        }
        return null; // @codeCoverageIgnore
    }

    /**
     * Return one object
     *
     * @return object|null
     */
    public function findOne()
    {
        // DO NOT use rowCount to check if something was found because not all drivers support it
        if ($this->execute()) {
            return $this->fetch();
        }
        return null; // @codeCoverageIgnore
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
        return new QueryIterator($this->statement, $this);
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
        $result = [];

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
