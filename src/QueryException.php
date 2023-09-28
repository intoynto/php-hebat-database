<?php
declare (strict_types=1);

namespace Intoy\HebatDatabase;

use PDOException;
use Throwable;
use Intoy\HebatSupport\Str;

class QueryException extends PDOException
{
    /**
     * The database connection name.
     *
     * @var string
     */
    public $connectionName;

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
     * @param string $connectionName
     * @param string $sql
     * @param array $bindings
     * @param \Throwable $previous
     * @return void
     */
    public function __construct($connectionName, $sql, array $bindings, Throwable $previous)
    {
        parent::__construct('',0,$previous);

        $this->connectionName = $connectionName;
        $this->sql=$sql;
        $this->bindings=$bindings;
        $this->code=$previous->getCode();
        $this->naturalMessage=$previous->getMessage();
        $this->message=$this->formatMessage($this->connectionName,$sql,$bindings,$previous);

        if ($previous instanceof PDOException) {
            $this->errorInfo = $previous->errorInfo;
        }

    }

    /**
     * Format SQL Error
     */
    protected function formatMessage($connectionName,$sql,$bindings,Throwable $previous)
    {
        return $previous->getMessage().' (Connection: '.$connectionName.', SQL: '.Str::replaceArray('?', $bindings, $sql).')';
    }

    /**
     * Get the connection name for the query.
     *
     * @return string
     */
    public function getConnectionName()
    {
        return $this->connectionName;
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