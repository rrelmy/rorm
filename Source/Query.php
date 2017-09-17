<?php
/**
 * @author Rémy M. Böhler <code@rrelmy.ch>
 */
declare(strict_types=1);

namespace Rorm;

use PDO;

class Query
{
    /** @var PDO */
    protected $dbh;

    /** @var string */
    protected $class;

    /** @var string */
    protected $query;

    /** @var array */
    protected $params;

    /** @var \PDOStatement */
    protected $statement;

    public function __construct(string $class = 'stdClass', PDO $dbh = null)
    {
        $this->class = $class;
        $this->dbh = $dbh ?: Rorm::getDatabase();
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function setQuery(string $query): void
    {
        $this->query = $query;
    }

    public function getQuery(): ?string
    {
        return $this->query;
    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function getParams(): ?array
    {
        return $this->params;
    }

    protected function execute(): bool
    {
        $this->statement = $this->dbh->prepare($this->query);
        // set fetchMode to assoc, it is easier to copy data from an array than an object
        $this->statement->setFetchMode(PDO::FETCH_ASSOC);
        return $this->statement->execute($this->params);
    }

    public function fetch()
    {
        $data = $this->statement->fetch();
        if ($data !== false) {
            return $this->instanceFromObject($data);
        }
        return null;
    }

    public function instanceFromObject(array $data)
    {
        $instance = new $this->class;
        if ($instance instanceof Model) {
            $instance->setData($data);
        } else {
            foreach ($data as $key => $value) {
                $instance->$key = $value;
            }
        }

        return $instance;
    }

    public function findColumn()
    {
        if ($this->execute()) {
            return $this->statement->fetchColumn();
        }
        return null; // @codeCoverageIgnore
    }

    /**
     * Return one object
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
     */
    public function findMany(): QueryIterator
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
     */
    public function findAll(): array
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
     */
    public function count(): int
    {
        return count($this->findAll());
    }
}
