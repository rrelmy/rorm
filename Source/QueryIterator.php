<?php
/**
 * @author Rémy M. Böhler <code@rrelmy.ch>
 */
declare(strict_types=1);

namespace Rorm;

class QueryIterator extends \IteratorIterator
{
    /** @var Query */
    protected $caller;

    /** @var bool */
    protected $used = false;

    public function __construct(\Traversable $iterator, Query $caller)
    {
        parent::__construct($iterator);
        $this->caller = $caller;
    }

    public function rewind(): void
    {
        if ($this->used) {
            throw new QueryException('Cannot traverse an already closed query');
        }
        parent::rewind();
    }

    public function next(): void
    {
        $this->used = true;
        parent::next();
    }

    /**
     * Transform plain object returned by PDOStatement to the desired model
     */
    public function current()
    {
        return $this->caller->instanceFromObject(parent::current());
    }
}
