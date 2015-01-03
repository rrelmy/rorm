<?php
/**
 * @author Rémy M. Böhler <code@rrelmy.ch>
 */

namespace Rorm;

use IteratorIterator;

/**
 * Class QueryIterator
 * @package Rorm
 */
class QueryIterator extends IteratorIterator
{
    /** @var bool */
    protected $used = false;

    public function rewind()
    {
        if ($this->used) {
            throw new QueryException('Cannot traverse an already closed query');
        }
        parent::rewind();
    }

    public function next()
    {
        $this->used = true;
        parent::next();
    }
}
