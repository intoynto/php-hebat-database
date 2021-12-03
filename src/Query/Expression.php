<?php

namespace Intoy\HebatDatabase\Query;

class Expression
{
    /**
     * Value
     * @var mixed
     */
    protected $value;

    /**
     * Construct
     * @param mixed
     * @return void
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Get Value
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Get the value of the expression
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getValue();
    }
}