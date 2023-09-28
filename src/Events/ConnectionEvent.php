<?php

namespace Intoy\HebatDatabase\Events;

abstract class ConnectionEvent
{
    /**
     * The name of the connection.
     *
     * @var string
     */
    public $connectionName;

    /**
     * The database connection instance.
     *
     * @var \Intoy\HebatDatabase\Connection
     */
    public $connection;

    /**
     * Create a new event instance.
     *
     * @param  \Intoy\HebatDatabase\Connection  $connection
     * @return void
     */
    public function __construct($connection)
    {
        $this->connection = $connection;
        $this->connectionName = $connection->getName();
    }
}
