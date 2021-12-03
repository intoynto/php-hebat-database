<?php
declare (strict_types=1);

namespace Intoy\HebatDatabase;

use PDO;
use Closure;
use Exception;
use DateTimeInterface;
use LogicException;

use Intoy\HebatDatabase\Query\Builder;
use Intoy\HebatDatabase\Query\Grammars\{Grammar as QueryGrammar};
use Intoy\HebatDatabase\Query\Processors\Processor;
use Intoy\HebatDatabase\QueryException;


class Connection 
{
    
    /**
     * @var \PDO|\Closure
     */
    protected $pdo;


    /**
     * The name of the connected database.
     *
     * @var string
     */
    protected $database;


    /**
     * The table prefix for the connection.
     *
     * @var string
     */
    protected $tablePrefix = '';


    /**
     * The database connection configuration options.
     *
     * @var array
     */
    protected $config = [];
    

    /**
     * @var QueryGrammar
     */
    protected $grammar;


    /**
     * @var Processor
     */
    protected $processor;

    /**
     * The reconnector instance for the connection.
     *
     * @var callable
     */
    protected $reconnector;


    /**
     * The number of active transactions.
     *
     * @var int
     */
    protected $transactions = 0;


    /**
     * The connection resolvers.
     *
     * @var array
     */
    protected static $resolvers = [];

    /**
     * Constructor
     * @param PDO $pdo
     * @param QueryGrammar $grammar
     */

    public function __construct($pdo, $database = '', $tablePrefix = '', array $config = [])
    {
        $this->pdo=$pdo;
        // First we will setup the default properties. We keep track of the DB
        // name we are connected to since it is needed when some reflective
        // type commands are run such as checking whether a table exists.
        $this->database = $database;

        $this->tablePrefix = $tablePrefix;

        $this->config = $config;

        $this->useDefaultGrammar();
        $this->useDefaultProcessor();
    }


    /**
     * @return void
     */
    public function useDefaultGrammar()
    {
        $this->grammar=$this->getDefaultGrammar();
    }


    /**
     * @return void
     */
    protected function getDefaultGrammar()
    {
        return new QueryGrammar();
    }



    /**
     * Set the query post processor to the default implementation.
     *
     * @return void
     */
    public function useDefaultProcessor()
    {
        $this->processor = $this->getDefaultProcessor();
    }


    /**
     * Get the default post processor instance.
     *
     * @return Processor
     */
    protected function getDefaultProcessor()
    {
        return new Processor;
    }


    /**
     * Get the connection resolver for the given driver.
     *
     * @param  string  $driver
     * @return mixed
     */
    public static function getResolver($driver)
    {
        return static::$resolvers[$driver] ?? null;
    }


    /**
     * Reconnect to the database.
     *
     * @return void
     *
     * @throws \LogicException
     */
    public function reconnect()
    {
        if (is_callable($this->reconnector)) {
            $this->doctrineConnection = null;

            return call_user_func($this->reconnector, $this);
        }

        throw new LogicException('Lost connection and no reconnector available.');
    }

    /**
     * Reconnect to the database if a PDO connection is missing.
     *
     * @return void
     */
    protected function reconnectIfMissingConnection()
    {
        if (is_null($this->pdo)) {
            $this->reconnect();
        }
    }

    /**
     * @return QueryGrammar
     */
    public function getQueryGrammar()
    {
        return $this->grammar;
    }

    /**
     * Get the current PDO connection.
     *
     * @return \PDO
     */
    public function getPdo()
    {
        if ($this->pdo instanceof Closure) {
            return $this->pdo = call_user_func($this->pdo);
        }

        return $this->pdo;
    }

    /**
     * Get the current PDO connection parameter without executing any reconnect logic.
     *
     * @return \PDO|\Closure|null
     */
    public function getRawPdo()
    {
        return $this->pdo;
    }


    /**
     * Set the reconnect instance on the connection.
     *
     * @param  callable  $reconnector
     * @return $this
     */
    public function setReconnector(callable $reconnector)
    {
        $this->reconnector = $reconnector;

        return $this;
    }

    /**
     * Begin fluent query
     * @param Builder|string $table
     * @param string|null $as
     * @return Builder
     */
    public function table($table, $as = null)
    {
        return $this->query()->from($table,$as);
    }

    /**
     * Get new query builder
     * @return Builder
     */
    public function query()
    {
        return new Builder($this,$this->grammar,$this->processor);
    }


    /**
     * Run a select statement and return a single result.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @param  bool  $useReadPdo
     * @return mixed
     */
    public function selectOne($query, $bindings = [], $useReadPdo = true)
    {
        $records = $this->select($query, $bindings, $useReadPdo);

        return array_shift($records);
    }


    /**
     * Run a select statement against the database.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return array
     */
    public function selectFromWriteConnection($query, $bindings = [])
    {
        return $this->select($query, $bindings, false);
    }
    
    /**
     * Run Select statement
     * @param string $query
     * @param array $bindings
     * 
     */
    public function select($query,$bindings=[])
    {
        return $this->run($query,$bindings,function($query,$bindings){
            $statement=$this->getPdo()->prepare($query);
            $this->bindValues($statement,$this->prepareBindings($bindings));
            $statement->execute();
            return $statement->fetchAll();
        });
    }


    /**
     * Run a SQL statement 
     * @param string $query
     * @param array $bindings
     * @param \Closure $callback
     * @return mixed
     * @throws QueryException
     */
    protected function run($query,$bindings,Closure $callback)
    {
        // reconnect if mising connection
        $start=microtime(true);
        try {
            return $this->runQueryCallback($query,$bindings,$callback);
        }
        catch(QueryException $e)
        {
            throw $e;
        }
    }

    /**
     * Run Query with callback
     * @param string $query
     * @param array $bindings
     * @param \Closure $callback
     * @return mixed
     * @throws QueryException
     */
    protected function runQueryCallback($query, $bindings, Closure $callback)
    {
        try {
            return $callback($query,$bindings);
        }
        catch(Exception $e)
        {
            throw new QueryException($query,$bindings,$e);
        }
    }


    /**
     * Bind values to their parameters in the given statement.
     *
     * @param  \PDOStatement  $statement
     * @param  array  $bindings
     * @return void
     */
    public function bindValues($statement, $bindings)
    {
        foreach ($bindings as $key => $value) {
            $statement->bindValue(
                is_string($key) ? $key : $key + 1,
                $value,
                is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR
            );
        }
    }


    /**
     * Prepare the query bindings for execution.
     *
     * @param  array  $bindings
     * @return array
     */
    public function prepareBindings(array $bindings)
    {
        $grammar = $this->getQueryGrammar();

        foreach ($bindings as $key => $value) {
            // We need to transform all instances of DateTimeInterface into the actual
            // date string. Each query grammar maintains its own date string format
            // so we'll just ask the grammar for the format to get from the date.
            if ($value instanceof DateTimeInterface) {
                $bindings[$key] = $value->format($grammar->getDateFormat());
            } elseif (is_bool($value)) {
                $bindings[$key] = (int) $value;
            }
        }

        return $bindings;
    }


    /**
     * Run an insert statement against the database.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return bool
     */
    public function insert($query, $bindings = [])
    {
        return $this->statement($query, $bindings);
    }


    /**
     * Run an update statement against the database.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return int
     */
    public function update($query, $bindings = [])
    {
        return $this->affectingStatement($query, $bindings);
    }

    /**
     * Run a delete statement against the database.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return int
     */
    public function delete($query, $bindings = [])
    {
        return $this->affectingStatement($query, $bindings);
    }


    /**
     * Execute an SQL statement and return the boolean result.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return bool
     */
    public function statement($query, $bindings = [])
    {
        return $this->run($query, $bindings, function ($query, $bindings) 
        {
            $statement = $this->getPdo()->prepare($query);

            $this->bindValues($statement, $this->prepareBindings($bindings));
            
            return $statement->execute();
        });
    }


    /**
     * Run an SQL statement and get the number of rows affected.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return int
     */
    public function affectingStatement($query, $bindings = [])
    {
        return $this->run($query, $bindings, function ($query, $bindings) {

            // For update or delete statements, we want to get the number of rows affected
            // by the statement and return that back to the developer. We'll first need
            // to execute the statement and then we'll use PDO to fetch the affected.
            $statement = $this->getPdo()->prepare($query);

            $this->bindValues($statement, $this->prepareBindings($bindings));

            return $statement->execute();
        });
    }


    /**
     * Get the table prefix for the connection.
     *
     * @return string
     */
    public function getTablePrefix()
    {
        return $this->tablePrefix;
    }


    /**
     * Set the table prefix in use by the connection.
     *
     * @param  string  $prefix
     * @return $this
     */
    public function setTablePrefix($prefix)
    {
        $this->tablePrefix = $prefix;

        $this->getQueryGrammar()->setTablePrefix($prefix);

        return $this;
    }


    /**
     * Set the table prefix and return the grammar.
     *
     * @param  QueryGrammar  $grammar
     * @return QueryGrammar
     */
    public function withTablePrefix(QueryGrammar $grammar)
    {
        $grammar->setTablePrefix($this->tablePrefix);

        return $grammar;
    }
}