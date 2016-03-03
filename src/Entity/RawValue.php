<?php

namespace Mini\Entity;

/**
 * Raw value wrapper to be used with SQL builds. Example:
 * $connection->insert('lala', ['created_at' => new Raw('NOW()')])
 */
class RawValue
{
    public $value;

    /**
     * Wrapper for SQL expressions
     */
    public function __construct($value)
    {
        $this->value = $value;
    }
}
