<?php
/**
 * @author Rémy M. Böhler <code@rrelmy.ch>
 */
declare(strict_types=1);

namespace Rorm;

interface ConnectionResolver
{
    public function connection(string $name): \PDO;

    public function defaultConnection(): \PDO;
}
