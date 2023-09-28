<?php

namespace Intoy\HebatDatabase\Events;

class StatementPrepared
{
    /**
     * The database connection instance.
     *
     * @var \Intoy\HebatDatabase\Connection
     */
    public $connection;

    /**
     * The PDO statement.
     *
     * @var \PDOStatement
     */
    public $statement;

    /**
     * Create a new event instance.
     *
     * @param  \Intoy\HebatDatabase\Connection  $connection
     * @param  \PDOStatement  $statement
     * @return void
     */
    public function __construct($connection, $statement)
    {
        $this->statement = $statement;
        $this->connection = $connection;
    }
}
