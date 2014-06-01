<?php
/**
 * @author Rémy M. Böhler <code@rrelmy.ch>
 */
namespace Rorm;

use PDO;
use PDOStatement;

/**
 * Class Query
 */
class Query
{
    /** @var string */
    protected $class;

    /** @var bool */
    protected $classIsOrmModel;

    /** @var string */
    protected $query;

    /** @var array */
    protected $params;

    /** @var PDOStatement */
    protected $statement;

    /**
     * @param string $class
     */
    public function __construct($class = 'stdClass')
    {
        $this->class = $class;
        $this->classIsOrmModel = is_subclass_of($this->class, '\\Rorm\\Model');
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
     * @param string $params
     */
    public function setParams($params)
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
        $this->statement = Rorm::$db->prepare($this->query);
        $this->statement->setFetchMode(PDO::FETCH_ASSOC);
        return $this->statement->execute($this->params);
    }

    /**
     * @return mixed|null
     */
    public function fetch()
    {
        $data = $this->statement->fetch(PDO::FETCH_ASSOC);
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
     * Return one object
     *
     * @return mixed
     */
    public function findOne()
    {
        // DO NOT use rowCount to check if something was found because SQLite does not support it
        if ($this->execute()) {
            return $this->fetch();
        }
        // @codeCoverageIgnoreStart
        return null;
        // @codeCoverageIgnoreEnd
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
        /*while ($object = $this->statement->fetchObject()) {
            yield $this->instanceFromObject($object);
        }*/
    }

    /**
     * Return an array with all objects, this could lead to heavy memory consumption
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
}
