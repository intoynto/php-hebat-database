<?php
declare (strict_types=1);

namespace Intoy\HebatDatabase\Connections;

use PDO;
use Intoy\HebatDatabase\Connection;
use Intoy\HebatDatabase\Query\Grammars\PostgresGrammar as QueryGrammar;
use Intoy\HebatDatabase\Schema\Grammars\PostgresGrammar as SchemaGrammar;
use Intoy\HebatDatabase\Query\Processors\PostgresProcessor;
use Intoy\HebatDatabase\Schema\PostgresBuilder;

class PostgresConnection extends Connection
{
    /**
     * Escape a binary value for safe SQL embedding.
     *
     * @param  string  $value
     * @return string
     */
    protected function escapeBinary($value)
    {
        $hex = bin2hex($value);

        return "'\x{$hex}'::bytea";
    }

    /**
     * Escape a bool value for safe SQL embedding.
     *
     * @param  bool  $value
     * @return string
     */
    protected function escapeBool($value)
    {
        return $value ? 'true' : 'false';
    }

    /**
     * Determine if the given database exception was caused by a unique constraint violation.
     *
     * @param  \Exception  $exception
     * @return bool
     */
    protected function isUniqueConstraintError(\Exception $exception)
    {
        return '23505' === $exception->getCode();
    }

    /**
     * Get the default query grammar instance.
     *
     * @return \Intoy\HebatDatabase\Query\Grammars\PostgresGrammar
     */
    protected function getDefaultQueryGrammar()
    {
        ($grammar = new QueryGrammar)->setConnection($this);

        return $this->withTablePrefix($grammar);
    }

    /**
     * Get a schema builder instance for the connection.
     *
     * @return \Intoy\HebatDatabase\Schema\PostgresBuilder
     */
    public function getSchemaBuilder()
    {
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }

        return new PostgresBuilder($this);
    }

     /**
     * Get the default schema grammar instance.
     *
     * @return \Intoy\HebatDatabase\Schema\Grammars\Grammar
     */
    protected function getDefaultSchemaGrammar()
    {
        return $this->withTablePrefix(new SchemaGrammar);
    }

    /**
     * Get the schema state for the connection.
     *
     * @param  mixed $files
     * @param  callable|null  $processFactory
     * @return mixed
     */
    //public function getSchemaState(Filesystem $files = null, callable $processFactory = null)
    //{
    //    return new PostgresSchemaState($this, $files, $processFactory);
    //}


    /**
     * @return PostgresProcessor
     */
    protected function getDefaultPostProcessor()
    {
        return new PostgresProcessor();
    }


    /**
     * Bind values to their parameters in the given statement.
     *
     * @param  \PDOStatement  $statement
     * @param  array  $bindings
     * @return void
     */
    /*
    public function bindValues($statement, $bindings)
    {
        foreach ($bindings as $key => $value) {
            if (is_int($value)) {
                $pdoParam = PDO::PARAM_INT;
            } elseif (is_resource($value)) {
                $pdoParam = PDO::PARAM_LOB;
            } else {
                $pdoParam = PDO::PARAM_STR;
            }

            $statement->bindValue(
                is_string($key) ? $key : $key + 1,
                $value,
                $pdoParam
            );
        }
    }
    */
}