<?php
/**
 *
 * @author Rémy M. Böhler <code@rrelmy.ch>
 */

namespace Rorm;

use PDOStatement;

/**
 * Class QueryIterator
 * @package Rorm
 */
class QueryIterator extends \IteratorIterator
{
    /**
     * @var Query
     */
    protected $caller;

    /** @var bool */
    protected $used = false;

    /**
     * @param PDOStatement $iterator
     * @param Query $caller
     */
    public function __construct(PDOStatement $iterator, $caller)
    {
        $this->caller = $caller;
        parent::__construct($iterator);
    }

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

    /**
     * Transform plain object to the desired model
     *
     * @return mixed
     */
    public function current()
    {
        return $this->caller->instanceFromObject(parent::current());
    }
}
