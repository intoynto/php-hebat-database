<?php
declare (strict_types=1);

namespace Intoy\HebatDatabase;

use PDOException;
use Throwable;
use Intoy\HebatSupport\Str;

class QueryException extends PDOException
{
    /**
     * @var string $sql
     */
    protected $sql;

    /**
     * Bindings
     * @var array
     */
    protected $bindings;

    /**
     * NaturalMessage / origin Message
     * @var string $naturalMessage
     */
    protected $naturalMessage;

    /**
     * Constructor
     * @param string $sql
     * @param array $bindings
     * @param \Throwable $previous
     * @return void
     */
    public function __construct($sql, array $bindings, Throwable $previous)
    {
        parent::__construct('',0,$previous);
        $this->sql=$sql;
        $this->bindings=$bindings;
        $this->code=$previous->getCode();
        $this->naturalMessage=$previous->getMessage();
        $this->message=$this->formatMessage($sql,$bindings,$previous);

    }

    /**
     * Format SQL Error
     */
    protected function formatMessage($sql,$bindings,Throwable $previous)
    {
        return $previous->getMessage().' (SQL: '.Str::replaceArray('?', $bindings, $sql).')';
    }


    /**
     * Get Origin message
     * @return $string
     */
    public function getOriginMessage()
    {
        return $this->naturalMessage;
    }

    /**
     * Get SQL
     * @return string
     */
    public function getSql(){
        return $this->sql;
    }

    /**
     * Get Bindings
     * @return array
     */
    public function getBindings()
    {
        return $this->bindings;
    }
}