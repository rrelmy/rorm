<?php
/**
 * @author Rémy M. Böhler <code@rrelmy.ch>
 */
declare(strict_types=1);

namespace Rorm;

use PDO;

class Rorm implements ConnectionResolver
{
    private $defaultConnectionName = 'default';

    /** @var PDO[] */
    protected $connections = [];

    public function __construct(\PDO $defaultConnection)
    {
        $this->setConnection($this->defaultConnectionName, $defaultConnection);
    }

    public function register()
    {
        Model::setConnectionResolver($this);
    }

    public function setConnection(string $name, PDO $connection): void
    {
        $this->connections[$name] = $connection;
    }

    public function connection(string $name): \PDO
    {
        if (array_key_exists($name, $this->connections)) {
            return $this->connections[$name];
        }

        throw new ConnectionNotFoundException('Database connection not found!');
    }

    public function defaultConnection(): \PDO
    {
        return $this->connection($this->defaultConnectionName);
    }
}
