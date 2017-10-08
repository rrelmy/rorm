<?php
/**
 * @author Rémy M. Böhler <code@rrelmy.ch>
 */
declare(strict_types=1);

namespace Rorm;

class ModelBuilder
{
    public function build(string $className)
    {
        return new $className;
    }
}
