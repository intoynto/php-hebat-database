<?php
declare (strict_types=1);

namespace Intoy\HebatDatabase\Connections;

use PDO;
use Intoy\HebatDatabase\Connection;
use Intoy\HebatDatabase\Query\Grammars\PostgresGrammar as Grammar;
use Intoy\HebatDatabase\Schema\Grammars\PostgresGrammar as SchemaGrammar;
use Intoy\HebatDatabase\Query\Processors\PostgresProcessor;
use Intoy\HebatDatabase\Schema\PostgresBuilder;

class PostgresConnection extends Connection
{
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

    /**
     * @return Grammar
     */
    protected function getDefaultGrammar()
    {
        return $this->withTablePrefix(new Grammar());
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
     * @return PostgresProcessor
     */
    protected function getDefaultProcessor()
    {
        return new PostgresProcessor();
    }
}